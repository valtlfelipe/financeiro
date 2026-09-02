import assert from 'node:assert/strict';
import test from 'node:test';
import { userAvatar } from '../../resources/js/lib/avatar.ts';

test('each user receives a stable avatar without relying on name or email', () => {
    const source = userAvatar(42);

    assert.equal(source, userAvatar(42));
    assert.notEqual(source, userAvatar(43));
    assert.ok(source.startsWith('data:image/svg+xml;charset=utf-8,'));
});

test('avatars are standalone images that work without an external service', (t) => {
    const fetch = t.mock.method(globalThis, 'fetch', () => {
        throw new Error('Avatar generation must stay offline');
    });

    const source = userAvatar(42);
    const svg = decodeURIComponent(source.slice(source.indexOf(',') + 1));

    assert.match(svg, /<svg\b/);
    assert.match(svg, /width="96"/);
    assert.match(svg, /fill="#(?:148a62|3f67c7|a66c2b|7b61a8|287b87|af5866)"/);
    assert.doesNotMatch(
        svg,
        /<script|<image|<foreignObject|\bhref=["'](?!#)|url\((?!["']?#)/i,
    );
    assert.doesNotMatch(svg, /financeiro:user:42/);
    assert.equal(fetch.mock.callCount(), 0);
});

test('avatar shapes reference definitions inside the same SVG image', () => {
    const source = userAvatar(42);
    const svg = decodeURIComponent(source.slice(source.indexOf(',') + 1));
    const ids = new Set(
        [...svg.matchAll(/\bid="([^"]+)"/g)].map((match) => match[1]),
    );
    const references = [...svg.matchAll(/\bhref="#([^"]+)"|url\(#([^)]+)\)/g)];

    assert.ok(references.length > 0);

    for (const reference of references) {
        const target = reference[1] ?? reference[2];

        assert.ok(ids.has(target), `Missing SVG definition: ${target}`);
    }
});
