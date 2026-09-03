<?php

use App\MembershipRole;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['cache.default' => 'array', 'financeiro.version' => 'v1.1.0']);
});

test('the about page shows installed project information without contacting GitHub', function () {
    [$user] = ownerWithWorkspace();

    $this->actingAs($user)->get(route('about.show'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/About')
            ->where('project.version', 'v1.1.0')
            ->where('project.author', 'Felipe Valtl de Mello')
            ->where('project.license', 'AGPL-3.0-only')
            ->where('project.repository', 'https://github.com/valtlfelipe/financeiro')
            ->where('project.sponsors', 'https://github.com/sponsors/valtlfelipe'));

    Http::assertNothingSent();
});

test('guests and users outside the workspace cannot access project information or update checks', function (string $routeName) {
    [$user, $workspace] = ownerWithWorkspace();
    $workspace->users()->detach($user);

    $this->get(route($routeName))->assertRedirect(route('login'));
    $this->actingAs($user)->get(route($routeName))->assertForbidden();

    Http::assertNothingSent();
})->with(['about.show', 'about.updates']);

test('members can see the about page and check updates', function () {
    [, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);
    Http::fake(['api.github.com/repos/valtlfelipe/financeiro/releases/latest' => Http::response([
        'tag_name' => 'v1.2.0', 'draft' => false, 'prerelease' => false,
    ])]);

    $this->actingAs($member)->get(route('about.show'))->assertInertia(fn (Assert $page) => $page->component('settings/About'));
    $this->getJson(route('about.updates'))->assertJsonPath('status', 'available');

    Http::assertSentCount(1);
});

test('update checks compare the installed version with the latest stable release', function (string $installed, string $latest, string $expected) {
    [$user] = ownerWithWorkspace();
    config(['financeiro.version' => $installed]);
    Http::fake(['api.github.com/repos/valtlfelipe/financeiro/releases/latest' => Http::response([
        'tag_name' => $latest, 'draft' => false, 'prerelease' => false,
        'html_url' => 'https://untrusted.example/release',
    ])]);

    $this->actingAs($user)->getJson(route('about.updates'))
        ->assertOk()
        ->assertJsonPath('status', $expected)
        ->assertJsonPath('releaseUrl', 'https://github.com/valtlfelipe/financeiro/releases/tag/'.$latest)
        ->assertHeader('Cache-Control', 'no-store, private');

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && $request->url() === 'https://api.github.com/repos/valtlfelipe/financeiro/releases/latest'
        && $request->data() === []);
})->with([
    'upgrade' => ['v1.1.0', 'v1.2.0', 'available'],
    'equal' => ['1.1.0', 'v1.1.0', 'current'],
    'ahead' => ['v2.0.0', 'v1.1.0', 'current'],
    'numeric ordering' => ['v1.9.0', 'v1.10.0', 'available'],
    'release candidate' => ['v1.1.0-rc.1', 'v1.1.0', 'available'],
    'build metadata' => ['v1.1.0+build.5', 'v1.1.0', 'current'],
    'development' => ['dev', 'v1.1.0', 'development'],
]);

test('unavailable or invalid release responses never claim the installation is up to date', function (array|string $body, int $status) {
    [$user] = ownerWithWorkspace();
    Http::fake(['api.github.com/repos/valtlfelipe/financeiro/releases/latest' => Http::response($body, $status)]);

    $this->actingAs($user)->getJson(route('about.updates'))
        ->assertOk()
        ->assertJsonPath('status', 'unavailable')
        ->assertJsonPath('latestVersion', null)
        ->assertJsonPath('releaseUrl', null);

    Http::assertSentCount(1);
})->with([
    'rate limited' => [['message' => 'API rate limit exceeded'], 403],
    'server error' => ['Bad Gateway', 502],
    'no release' => [[], 404],
    'malformed JSON' => ['not json', 200],
    'missing metadata' => [['tag_name' => 'v1.2.0'], 200],
    'invalid tag' => [['tag_name' => 'latest', 'draft' => false, 'prerelease' => false], 200],
    'non-string tag' => [['tag_name' => ['v1.2.0'], 'draft' => false, 'prerelease' => false], 200],
    'draft' => [['tag_name' => 'v1.2.0', 'draft' => true, 'prerelease' => false], 200],
    'prerelease' => [['tag_name' => 'v1.2.0-beta.1', 'draft' => false, 'prerelease' => true], 200],
]);

test('connection failures give feedback and may be retried after one minute', function () {
    [$user] = ownerWithWorkspace();
    $this->freezeTime();
    Http::fake(['api.github.com/repos/valtlfelipe/financeiro/releases/latest' => Http::sequence()
        ->pushFailedConnection()
        ->push(['tag_name' => 'v1.2.0', 'draft' => false, 'prerelease' => false])]);
    $this->actingAs($user)->getJson(route('about.updates'))->assertJsonPath('status', 'unavailable');
    $this->getJson(route('about.updates'))->assertJsonPath('status', 'unavailable');
    Http::assertSentCount(1);

    $this->travel(61)->seconds();
    $this->getJson(route('about.updates'))->assertJsonPath('status', 'available');

    Http::assertSentCount(2);
});

test('stable releases are shared across users for one hour and compared against the current installation', function () {
    [$owner, $workspace] = ownerWithWorkspace();
    $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
    $workspace->users()->attach($member, ['role' => MembershipRole::Member->value]);
    $this->freezeTime();
    Http::fake(['api.github.com/repos/valtlfelipe/financeiro/releases/latest' => Http::sequence()
        ->push(['tag_name' => 'v1.2.0', 'draft' => false, 'prerelease' => false])
        ->push(['tag_name' => 'v1.3.0', 'draft' => false, 'prerelease' => false])]);
    $this->actingAs($owner)->getJson(route('about.updates'))->assertJsonPath('status', 'available');
    config(['financeiro.version' => 'v1.2.0']);
    $this->actingAs($member)->getJson(route('about.updates'))->assertJsonPath('status', 'current');
    Http::assertSentCount(1);

    $this->travel(61)->minutes();
    $this->getJson(route('about.updates'))->assertJsonPath('status', 'available')->assertJsonPath('latestVersion', '1.3.0');

    Http::assertSentCount(2);
});
