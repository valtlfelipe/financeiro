import type { MonthlyAccountBalance } from '@/types';

type AccountBalanceLabels = {
    realized: string;
    forecast?: string;
    archived?: string;
};

export function accountBalanceAccessibleLabel(
    account: MonthlyAccountBalance,
    formatMoney: (value: string) => string,
    labels: AccountBalanceLabels,
): string {
    return [
        account.name,
        account.is_archived ? labels.archived : undefined,
        `${labels.realized}: ${formatMoney(account.realized_balance_minor)}`,
        labels.forecast
            ? `${labels.forecast}: ${formatMoney(account.forecast_balance_minor)}`
            : undefined,
    ]
        .filter((part): part is string => Boolean(part))
        .join('. ');
}
