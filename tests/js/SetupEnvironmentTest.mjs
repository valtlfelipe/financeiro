import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import {
    copyFileSync,
    mkdirSync,
    mkdtempSync,
    readFileSync,
    rmSync,
    statSync,
    writeFileSync,
} from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import test from 'node:test';

function createSetupFixture(context) {
    const directory = mkdtempSync(join(tmpdir(), 'financeiro-setup-test-'));
    context.after(() => rmSync(directory, { recursive: true, force: true }));
    mkdirSync(join(directory, 'scripts'));
    copyFileSync(
        new URL('../../scripts/init-env.sh', import.meta.url),
        join(directory, 'scripts/init-env.sh'),
    );
    copyFileSync(
        new URL('../../.env.example', import.meta.url),
        join(directory, '.env.example'),
    );

    return directory;
}

function runSetup(directory) {
    const result = spawnSync('sh', ['scripts/init-env.sh'], {
        cwd: directory,
        encoding: 'utf8',
    });
    assert.equal(result.status, 0, result.stderr || result.stdout);

    return result.stdout;
}

test('setup generates only the required values without printing secrets', (context) => {
    const directory = createSetupFixture(context);

    const output = runSetup(directory);

    const content = readFileSync(join(directory, '.env'), 'utf8');
    const values = Object.fromEntries(
        [...content.matchAll(/^([A-Z_]+)=(.*)$/gm)].map((match) => [
            match[1],
            match[2],
        ]),
    );
    assert.deepEqual(Object.keys(values).sort(), [
        'APP_KEY',
        'APP_URL',
        'DB_PASSWORD',
    ]);
    assert.match(values.APP_KEY, /^base64:/);
    assert.equal(Buffer.from(values.APP_KEY.slice(7), 'base64').length, 32);
    assert.match(values.DB_PASSWORD, /^[a-f0-9]{48}$/);
    assert.equal(values.APP_URL, 'http://localhost:8080');
    assert.equal(output.includes(values.APP_KEY), false);
    assert.equal(output.includes(values.DB_PASSWORD), false);
    assert.equal(statSync(join(directory, '.env')).mode & 0o777, 0o600);
});

test('running setup again preserves generated credentials', (context) => {
    const directory = createSetupFixture(context);
    runSetup(directory);
    const before = readFileSync(join(directory, '.env'), 'utf8');

    runSetup(directory);

    assert.equal(readFileSync(join(directory, '.env'), 'utf8'), before);
});

test('setup preserves existing secrets and a customized URL', (context) => {
    const directory = createSetupFixture(context);
    const existing = [
        `APP_KEY=base64:${Buffer.alloc(32, 7).toString('base64')}`,
        'DB_PASSWORD=already-configured-secret',
        'APP_URL=https://financeiro.example.test',
        'APP_PORT=9090',
        '',
    ].join('\n');
    writeFileSync(join(directory, '.env'), existing);

    const output = runSetup(directory);

    assert.equal(readFileSync(join(directory, '.env'), 'utf8'), existing);
    assert.equal(output.includes('already-configured-secret'), false);
});
