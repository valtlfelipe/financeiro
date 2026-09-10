import assert from 'node:assert/strict';
import test from 'node:test';
import { isTransactionOverdue } from '../../resources/js/lib/transaction-status.ts';

test('marks only pending transactions before today as overdue', () => {
    assert.equal(
        isTransactionOverdue(
            { dueOn: '2026-09-09', settledAt: null },
            '2026-09-10',
        ),
        true,
    );
    assert.equal(
        isTransactionOverdue(
            { dueOn: '2026-09-10', settledAt: null },
            '2026-09-10',
        ),
        false,
    );
    assert.equal(
        isTransactionOverdue(
            { dueOn: '2026-09-11', settledAt: null },
            '2026-09-10',
        ),
        false,
    );
    assert.equal(
        isTransactionOverdue(
            { dueOn: '2026-09-09', settledAt: '2026-09-10T12:00:00Z' },
            '2026-09-10',
        ),
        false,
    );
});

test('does not infer an overdue state without the workspace date', () => {
    assert.equal(
        isTransactionOverdue({ dueOn: '2026-09-09', settledAt: null }),
        false,
    );
});
