import assert from 'node:assert/strict';
import test from 'node:test';
import { userAvatar } from '../../resources/js/lib/avatar.ts';

test('each user receives a stable avatar without relying on name or email', () => {
    const source = userAvatar(42);

    assert.equal(source, userAvatar(42));
    assert.notEqual(source, userAvatar(43));
    assert.ok(source.startsWith('data:image/svg+xml;utf8,'));
});

test('avatars are standalone images that work without an external service', (t) => {
    const fetch = t.mock.method(globalThis, 'fetch', () => {
        throw new Error('Avatar generation must stay offline');
    });

    const source = userAvatar(42);
    const svg = decodeURIComponent(source.slice(source.indexOf(',') + 1));

    assert.match(svg, /<svg\b/);
    assert.match(svg, /width="96"/);
    assert.doesNotMatch(
        svg,
        /<script|<image|<foreignObject|\bhref=|url\(["']?(?!#)/i,
    );
    assert.doesNotMatch(svg, /financeiro:user:42/);
    assert.equal(fetch.mock.callCount(), 0);
});
