import assert from 'node:assert/strict';
import test from 'node:test';
import { formatPageTitle } from '../../resources/js/lib/product.ts';

test('page titles always use Financeiro without a build-time app name', () => {
    assert.equal(formatPageTitle('Resumo'), 'Resumo - Financeiro');
    assert.equal(formatPageTitle('Lançamentos'), 'Lançamentos - Financeiro');
    assert.equal(formatPageTitle(''), 'Financeiro');
});
