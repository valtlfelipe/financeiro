export type Workspace = {
    id: number;
    name: string;
    icon: string;
    currency: string;
    timezone: string;
    today: string;
    role: 'owner' | 'member';
};

export type WorkspaceOption = Pick<Workspace, 'id' | 'name' | 'icon' | 'role'>;

export type Account = {
    id: number;
    name: string;
    type: 'checking' | 'savings' | 'cash' | 'other';
    initialBalanceMinor: number;
    balanceDate: string;
    balanceMinor?: number;
    icon: string | null;
    color: string;
    isArchived: boolean;
};

export type Category = {
    id: number;
    name: string;
    type: 'income' | 'expense' | 'both';
    icon: string | null;
    color: string;
    isArchived: boolean;
};

export type Transaction = {
    id: number;
    type: 'income' | 'expense' | 'transfer';
    amountMinor: number;
    description: string;
    dueOn: string;
    settledAt: string | null;
    notes: string | null;
    installmentNumber: number | null;
    installmentTotal: number | null;
    account: Account;
    destinationAccount: Account | null;
    category: Category | null;
    series: {
        id: number;
        kind: 'recurring' | 'installment';
        frequency: 'weekly' | 'monthly' | 'yearly' | null;
    } | null;
};

export type MonthlySummary = {
    planned_income_minor: number;
    planned_expense_minor: number;
    opening_balance_minor: number;
    forecast_change_minor: number;
    realized_balance_minor: number;
    forecast_balance_minor: number;
    period: 'past' | 'current' | 'future';
};
