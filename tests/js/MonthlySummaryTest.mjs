import assert from 'node:assert/strict';
import test from 'node:test';
import { formatCurrencyMinor } from '../../resources/js/lib/minor-amount.ts';
import { accountBalanceAccessibleLabel } from '../../resources/js/lib/monthly-summary.ts';

test('summarizes an account balance in reading order', () => {
    const label = accountBalanceAccessibleLabel(
        {
            id: 1,
            name: 'Conta principal',
            color: '#15936b',
            is_archived: false,
            realized_balance_minor: '125050',
            forecast_balance_minor: '97450',
        },
        (value) => formatCurrencyMinor(value, 'pt-BR', 'BRL'),
        {
            realized: 'Agora',
            forecast: 'Fim do mês',
            archived: 'Arquivada',
        },
    );

    assert.equal(
        label,
        'Conta principal. Agora: R$ 1.250,50. Fim do mês: R$ 974,50',
    );
});

test('omits the redundant forecast for a past account balance', () => {
    const label = accountBalanceAccessibleLabel(
        {
            id: 2,
            name: 'Conta antiga',
            color: '#64748b',
            is_archived: true,
            realized_balance_minor: '0',
            forecast_balance_minor: '0',
        },
        (value) => formatCurrencyMinor(value, 'pt-BR', 'BRL'),
        { realized: 'Realizado', archived: 'Arquivada' },
    );

    assert.equal(label, 'Conta antiga. Arquivada. Realizado: R$ 0,00');
});
