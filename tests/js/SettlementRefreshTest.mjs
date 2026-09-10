import assert from 'node:assert/strict';
import test from 'node:test';
import { settlementReloadOptions } from '../../resources/js/lib/settlement-refresh.ts';

test('reloads the requested page props after a settlement update', () => {
    assert.deepEqual(
        settlementReloadOptions([
            'accounts',
            'recentTransactions',
            'remainingTransactionsCount',
        ]),
        {
            only: [
                'accounts',
                'recentTransactions',
                'remainingTransactionsCount',
            ],
        },
    );
});

test('keeps settlement updates local when no reload props are configured', () => {
    assert.equal(settlementReloadOptions(), undefined);
    assert.equal(settlementReloadOptions([]), undefined);
});
