import assert from 'node:assert/strict';
import test from 'node:test';
import { randomColor } from '../../resources/js/lib/colors.ts';

test('new forms receive a valid hex color without immediately repeating it', (t) => {
    t.mock.method(Math, 'random', () => 0);
    const first = randomColor();
    assert.match(first, /^#[0-9A-F]{6}$/u);
    assert.notEqual(randomColor(first.toLowerCase()), first);
});

test('the full random range yields colors with readable white labels', (t) => {
    const random = t.mock.method(Math, 'random');
    const generated = new Set();

    for (let index = 0; index < 10; index++) {
        random.mock.mockImplementation(() => index / 10);
        const color = randomColor();
        generated.add(color);
        const channels = color
            .slice(1)
            .match(/.{2}/gu)
            .map((hex) => {
                const channel = parseInt(hex, 16) / 255;
                return channel <= 0.04045
                    ? channel / 12.92
                    : ((channel + 0.055) / 1.055) ** 2.4;
            });
        const luminance =
            channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
        assert.ok(
            1.05 / (luminance + 0.05) >= 4.5,
            `${color} must contrast with white text`,
        );
    }

    assert.equal(generated.size, 10);
});
