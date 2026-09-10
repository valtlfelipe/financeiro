import type { Transaction } from '@/types';

export function isTransactionOverdue(
    transaction: Pick<Transaction, 'dueOn' | 'settledAt'>,
    today?: string | null,
): boolean {
    return Boolean(
        today && transaction.settledAt === null && transaction.dueOn < today,
    );
}
