import assert from 'node:assert/strict';
import test from 'node:test';

let moduleNumber = 0;

async function appearanceBrowser(
    t,
    { stored = null, dark = false, cookie = 'system', blocked = false } = {},
) {
    const classes = new Set();
    const listeners = new Map();
    const media = {
        matches: dark,
        addEventListener: (event, listener) =>
            listeners.set(`media:${event}`, listener),
    };
    const values = new Map(stored === null ? [] : [['appearance', stored]]);
    const document = {
        cookie: '',
        documentElement: {
            dataset: { appearance: cookie },
            classList: {
                toggle: (name, enabled) =>
                    enabled ? classes.add(name) : classes.delete(name),
            },
        },
    };
    const globals = {
        document,
        window: {
            matchMedia: () => media,
            addEventListener: (event, listener) =>
                listeners.set(event, listener),
        },
        localStorage: {
            getItem: (key) => {
                if (blocked) throw new Error('Storage unavailable');
                return values.get(key) ?? null;
            },
            setItem: (key, value) => {
                if (blocked) throw new Error('Storage unavailable');
                values.set(key, value);
            },
        },
    };
    const module = await import(
        `../../resources/js/composables/useAppearance.ts?case=${++moduleNumber}`
    );
    for (const [name, value] of Object.entries(globals)) {
        const descriptor = Object.getOwnPropertyDescriptor(globalThis, name);
        Object.defineProperty(globalThis, name, { configurable: true, value });
        t.after(() =>
            descriptor
                ? Object.defineProperty(globalThis, name, descriptor)
                : Reflect.deleteProperty(globalThis, name),
        );
    }
    module.initializeTheme();
    return {
        ...module.useAppearance(),
        document,
        classes,
        values,
        media,
        listeners,
    };
}

test('saved appearance survives reload and explicit choices override the system', async (t) => {
    const theme = await appearanceBrowser(t, { stored: 'dark', dark: false });
    assert.equal(theme.resolvedAppearance.value, 'dark');
    assert.ok(theme.classes.has('dark'));

    theme.updateAppearance('light');
    assert.equal(theme.values.get('appearance'), 'light');
    assert.match(theme.document.cookie, /^appearance=light;/);
    assert.equal(theme.classes.has('dark'), false);

    theme.media.matches = true;
    theme.listeners.get('media:change')();
    assert.equal(theme.resolvedAppearance.value, 'light');
    assert.equal(theme.classes.has('dark'), false);
});

test('system appearance reacts to operating system changes and other tabs', async (t) => {
    const theme = await appearanceBrowser(t);
    assert.equal(theme.resolvedAppearance.value, 'light');
    theme.media.matches = true;
    theme.listeners.get('media:change')();
    assert.equal(theme.resolvedAppearance.value, 'dark');
    assert.ok(theme.classes.has('dark'));

    theme.listeners.get('storage')({ key: 'appearance', newValue: 'light' });
    assert.equal(theme.appearance.value, 'light');
    assert.equal(theme.classes.has('dark'), false);
});

test('blocked storage falls back to the cookie and does not prevent switching', async (t) => {
    const theme = await appearanceBrowser(t, { cookie: 'dark', blocked: true });
    assert.equal(theme.appearance.value, 'dark');
    assert.doesNotThrow(() => theme.updateAppearance('light'));
    assert.equal(theme.resolvedAppearance.value, 'light');
});

test('invalid preferences fall back to system and invalid selections are ignored', async (t) => {
    const theme = await appearanceBrowser(t, {
        stored: 'invalid',
        cookie: 'invalid',
        dark: true,
    });
    assert.equal(theme.appearance.value, 'system');
    assert.equal(theme.resolvedAppearance.value, 'dark');
    theme.updateAppearance('unexpected');
    assert.equal(theme.appearance.value, 'system');
});
