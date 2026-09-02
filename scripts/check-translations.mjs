import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const root = new URL('../', import.meta.url).pathname;
const localesRoot = join(root, 'resources/js/i18n/locales');
const baseLocale = 'pt-BR';
const namespaces = ['common', 'auth', 'finance', 'settings'];

function flatten(value, prefix = '') {
    return Object.entries(value).flatMap(([key, child]) => {
        const path = prefix ? `${prefix}.${key}` : key;
        return child && typeof child === 'object' && !Array.isArray(child)
            ? flatten(child, path)
            : [path];
    });
}

function messages(locale) {
    return Object.fromEntries(
        namespaces.flatMap((namespace) => {
            const file = join(localesRoot, locale, `${namespace}.json`);
            return flatten(JSON.parse(readFileSync(file, 'utf8'))).map(
                (key) => [key, true],
            );
        }),
    );
}

function filesIn(directory) {
    return readdirSync(directory).flatMap((name) => {
        const path = join(directory, name);
        return statSync(path).isDirectory() ? filesIn(path) : [path];
    });
}

const base = messages(baseLocale);
const locales = readdirSync(localesRoot).filter((entry) =>
    statSync(join(localesRoot, entry)).isDirectory(),
);
const errors = [];

for (const locale of locales) {
    const translated = messages(locale);
    for (const key of Object.keys(base)) {
        if (!translated[key]) errors.push(`${locale}: chave ausente ${key}`);
    }
}

const vueFiles = filesIn(join(root, 'resources/js')).filter((file) =>
    file.endsWith('.vue'),
);
const keyPattern = /\bt\(\s*['"]([^'"]+)['"]/g;
for (const file of vueFiles) {
    const source = readFileSync(file, 'utf8');
    for (const match of source.matchAll(keyPattern)) {
        if (!base[match[1]])
            errors.push(
                `${relative(root, file)}: chave desconhecida ${match[1]}`,
            );
    }
}

if (errors.length) {
    console.error(errors.join('\n'));
    process.exit(1);
}

console.log(
    `${Object.keys(base).length} chaves verificadas em ${locales.length} catálogo(s).`,
);
