<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    CircleAlert,
    Filter,
    Plus,
    Search,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SummaryGrid from '@/components/finance/SummaryGrid.vue';
import TransactionPanel from '@/components/finance/TransactionPanel.vue';
import TransactionRow from '@/components/finance/TransactionRow.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import { index } from '@/routes/transactions';
import type {
    Account,
    Category,
    MonthlySummary,
    Transaction,
    TransactionSubtotal,
} from '@/types';

const props = defineProps<{
    month: string;
    summary: MonthlySummary;
    subtotal: TransactionSubtotal;
    transactions: Transaction[];
    overdueTransactions: Transaction[];
    overdueTransactionsCount: number;
    overdueOpen: boolean;
    accounts: Account[];
    filterAccounts: Account[];
    categories: Category[];
    filterCategories: Category[];
    filters: Record<string, string | null>;
}>();
const { t } = useI18n();
const page = usePage();
const { formatDate, formatMoney, formatMonth } = useFinanceFormat();
const items = ref([...props.transactions]);
const overdueItems = ref([...props.overdueTransactions]);
const summaryState = ref(props.summary);
const subtotalState = ref(props.subtotal);
const overdueOpen = ref(props.overdueOpen);
const panelOpen = ref(false);
const panelMode = ref<'create' | 'detail' | 'edit' | 'copy'>('create');
const selectedId = ref<number | null>(null);
const filtersOpen = ref(false);
const filterState = reactive({
    search: props.filters.search ?? '',
    account_id: props.filters.account_id ?? '',
    category_id: props.filters.category_id ?? '',
    type: props.filters.type ?? '',
    status: props.filters.status ?? '',
});
const hasFilters = computed(() => Object.values(props.filters).some(Boolean));
const showSubtotal = computed(() => hasFilters.value);
const activeFilterCount = computed(
    () =>
        Object.entries(props.filters).filter(
            ([key, value]) => key !== 'search' && value,
        ).length,
);
const filterOptions = computed(() => [
    {
        key: 'type' as const,
        label: t('finance.transactions.filters.type'),
        all: t('finance.transactions.filters.allTypes'),
        options: ['income', 'expense', 'transfer'].map((value) => ({
            value,
            label: t(`finance.transactions.type.${value}`),
        })),
    },
    {
        key: 'status' as const,
        label: t('finance.transactions.filters.status'),
        all: t('finance.transactions.filters.allStatuses'),
        options: ['pending', 'settled'].map((value) => ({
            value,
            label: t(`finance.transactions.status.${value}`),
        })),
    },
    {
        key: 'account_id' as const,
        label: t('finance.transactions.filters.account'),
        all: t('finance.transactions.filters.allAccounts'),
        options: props.filterAccounts.map((account) => ({
            value: String(account.id),
            label: account.isArchived
                ? `${account.name} · ${t('common.archived')}`
                : account.name,
        })),
    },
    {
        key: 'category_id' as const,
        label: t('finance.transactions.filters.category'),
        all: t('finance.transactions.filters.allCategories'),
        options: props.filterCategories.map((category) => ({
            value: String(category.id),
            label: category.isArchived
                ? `${category.name} · ${t('common.archived')}`
                : category.name,
        })),
    },
]);

watch(
    () => props.filters,
    (filters) => {
        Object.assign(filterState, {
            search: filters.search ?? '',
            account_id: filters.account_id ?? '',
            category_id: filters.category_id ?? '',
            type: filters.type ?? '',
            status: filters.status ?? '',
        });
    },
);
const selected = computed(
    () =>
        items.value.find((item) => item.id === selectedId.value) ??
        overdueItems.value.find((item) => item.id === selectedId.value) ??
        null,
);
const grouped = computed(() =>
    Object.entries(
        items.value.reduce<Record<string, Transaction[]>>(
            (groups, transaction) => {
                (groups[transaction.dueOn] ??= []).push(transaction);
                return groups;
            },
            {},
        ),
    ),
);
const defaultDueOn = computed(() => {
    const today = page.props.workspace?.today ?? `${props.month}-01`;
    const todayDay = Number(today.slice(-2));
    const [year, month] = props.month.split('-').map(Number);
    const lastDay = new Date(year, month, 0).getDate();
    const day = String(Math.min(todayDay, lastDay)).padStart(2, '0');

    return `${props.month}-${day}`;
});

watch(
    () => props.transactions,
    (next) => {
        items.value = [...next];

        if (
            selectedId.value !== null &&
            !next.some((item) => item.id === selectedId.value) &&
            !overdueItems.value.some((item) => item.id === selectedId.value)
        ) {
            panelOpen.value = false;
            selectedId.value = null;
        }
    },
);
watch(
    () => props.overdueTransactions,
    (next) => {
        overdueItems.value = [...next];

        if (
            selectedId.value !== null &&
            !items.value.some((item) => item.id === selectedId.value) &&
            !next.some((item) => item.id === selectedId.value)
        ) {
            panelOpen.value = false;
            selectedId.value = null;
        }
    },
);
watch(
    () => props.summary,
    (next) => {
        summaryState.value = next;
    },
);
watch(
    () => props.subtotal,
    (next) => {
        subtotalState.value = next;
    },
);

function shiftedMonth(offset: number): string {
    const date = new Date(`${props.month}-01T12:00:00`);
    date.setMonth(date.getMonth() + offset);
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

function applyFilters(): void {
    router.get(
        index.url(),
        { month: props.month, ...filterState },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function clearFilters(): void {
    Object.assign(filterState, {
        search: '',
        account_id: '',
        category_id: '',
        type: '',
        status: '',
    });
    router.get(
        index.url(),
        { month: props.month },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function openCreate(): void {
    selectedId.value = null;
    panelMode.value = 'create';
    panelOpen.value = true;
}

function openDetail(transaction: Transaction): void {
    selectedId.value = transaction.id;
    panelMode.value = 'detail';
    panelOpen.value = true;
}

function updateTransaction(transaction: Transaction): void {
    items.value = items.value.map((item) =>
        item.id === transaction.id ? transaction : item,
    );
    overdueItems.value = overdueItems.value.map((item) =>
        item.id === transaction.id ? transaction : item,
    );
}

const transactionReloadProps = [
    'transactions',
    'subtotal',
    'summary',
    'overdueTransactions',
    'overdueTransactionsCount',
];
</script>

<template>
    <section class="grid gap-6">
        <Head :title="t('finance.transactions.title')" />
        <header class="flex flex-wrap items-center gap-3">
            <div class="mr-auto">
                <h1 class="text-3xl font-extrabold tracking-tight">
                    {{ t('finance.transactions.title') }}
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('finance.transactions.subtitle') }}
                </p>
            </div>
            <Button size="lg" class="min-h-11 gap-2" @click="openCreate">
                <Plus class="size-4" aria-hidden="true" />{{
                    t('finance.transactions.new')
                }}
            </Button>
        </header>

        <Collapsible
            v-if="overdueTransactionsCount > 0"
            v-model:open="overdueOpen"
        >
            <section
                class="border-expense/25 bg-card overflow-hidden rounded-3xl border"
                aria-labelledby="overdue_transactions_heading"
            >
                <div
                    class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-3 p-4 sm:p-5"
                >
                    <div class="flex min-w-0 gap-3">
                        <span
                            class="bg-expense/12 text-expense grid size-10 shrink-0 place-items-center rounded-2xl"
                        >
                            <CircleAlert class="size-5" aria-hidden="true" />
                        </span>
                        <div class="min-w-0">
                            <h2
                                id="overdue_transactions_heading"
                                class="text-base font-extrabold tracking-tight sm:text-lg"
                            >
                                {{
                                    t(
                                        overdueTransactionsCount === 1
                                            ? 'finance.transactions.overdue.title'
                                            : 'finance.transactions.overdue.titlePlural',
                                        { count: overdueTransactionsCount },
                                    )
                                }}
                            </h2>
                            <p
                                class="text-muted-foreground mt-1 text-sm leading-5"
                            >
                                {{
                                    t(
                                        'finance.transactions.overdue.description',
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <CollapsibleTrigger as-child>
                        <Button
                            variant="ghost"
                            class="text-expense hover:bg-expense/10 -mr-2 min-h-11 shrink-0 gap-2 rounded-xl px-3"
                        >
                            {{
                                overdueOpen
                                    ? t('finance.transactions.overdue.hide')
                                    : t('finance.transactions.overdue.show')
                            }}
                            <ChevronDown
                                class="size-4 transition-transform duration-200 motion-reduce:transition-none"
                                :class="{ 'rotate-180': overdueOpen }"
                                aria-hidden="true"
                            />
                        </Button>
                    </CollapsibleTrigger>
                </div>

                <CollapsibleContent
                    class="data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:slide-out-to-top-1 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:slide-in-from-top-1 duration-200 motion-reduce:animate-none"
                >
                    <div class="border-expense/20 border-t">
                        <TransactionRow
                            v-for="item in overdueItems"
                            :key="item.id"
                            :transaction="item"
                            :reload-props="transactionReloadProps"
                            show-date
                            @open="openDetail"
                            @update="updateTransaction"
                        />
                    </div>
                </CollapsibleContent>
            </section>
        </Collapsible>

        <section aria-labelledby="month_heading" class="grid gap-3">
            <header>
                <div
                    class="border-border/80 bg-card grid grid-cols-[2.75rem_1fr_2.75rem] items-center gap-1 rounded-2xl border p-1 sm:w-80"
                >
                    <Button variant="ghost" class="size-11 rounded-xl" as-child>
                        <Link
                            :href="
                                index({
                                    query: {
                                        ...props.filters,
                                        month: shiftedMonth(-1),
                                    },
                                })
                            "
                            preserve-state
                            :aria-label="
                                t('finance.transactions.previousMonth')
                            "
                        >
                            <ChevronLeft class="size-5" aria-hidden="true" />
                        </Link>
                    </Button>
                    <h2
                        id="month_heading"
                        class="text-center text-base font-extrabold capitalize"
                        aria-live="polite"
                    >
                        {{ formatMonth(month) }}
                    </h2>
                    <Button variant="ghost" class="size-11 rounded-xl" as-child>
                        <Link
                            :href="
                                index({
                                    query: {
                                        ...props.filters,
                                        month: shiftedMonth(1),
                                    },
                                })
                            "
                            preserve-state
                            :aria-label="t('finance.transactions.nextMonth')"
                        >
                            <ChevronRight class="size-5" aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </header>
            <SummaryGrid :summary="summaryState" />
        </section>

        <section
            class="border-border/80 bg-card overflow-hidden rounded-3xl border"
            aria-labelledby="transaction_list_heading"
        >
            <header class="border-border/70 grid gap-4 border-b p-4 sm:p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h2
                        id="transaction_list_heading"
                        class="text-lg font-extrabold"
                    >
                        {{ t('finance.transactions.listTitle') }}
                    </h2>
                    <Button
                        v-if="hasFilters"
                        variant="ghost"
                        class="min-h-11"
                        @click="clearFilters"
                        >{{ t('finance.transactions.filters.clear') }}</Button
                    >
                </div>
                <form
                    class="grid gap-3 sm:grid-cols-[1fr_auto]"
                    role="search"
                    :aria-label="t('finance.transactions.listTitle')"
                    @submit.prevent="applyFilters"
                >
                    <div class="flex gap-2">
                        <div class="relative min-w-0 flex-1">
                            <Label for="transaction_search" class="sr-only">{{
                                t('common.search')
                            }}</Label>
                            <Input
                                id="transaction_search"
                                v-model="filterState.search"
                                type="search"
                                class="bg-muted h-11 rounded-xl"
                                :placeholder="
                                    t('finance.transactions.searchPlaceholder')
                                "
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="secondary"
                            size="icon"
                            class="size-11 shrink-0"
                            :aria-label="t('common.search')"
                            :title="t('common.search')"
                        >
                            <Search class="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 gap-2"
                        :aria-expanded="filtersOpen"
                        aria-controls="transaction_filters"
                        @click="filtersOpen = !filtersOpen"
                    >
                        <Filter class="size-4" aria-hidden="true" />{{
                            t('common.filters')
                        }}
                        <span
                            v-if="activeFilterCount"
                            class="bg-primary/10 text-primary grid size-5 place-items-center rounded-full text-xs"
                            >{{ activeFilterCount }}</span
                        >
                    </Button>
                    <div
                        v-if="filtersOpen"
                        id="transaction_filters"
                        class="border-border/70 grid gap-4 border-t pt-4 sm:col-span-2 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div
                            v-for="filter in filterOptions"
                            :key="filter.key"
                            class="grid min-w-0 gap-2"
                        >
                            <Label :for="`filter_${filter.key}`">{{
                                filter.label
                            }}</Label>
                            <Select
                                :model-value="filterState[filter.key] || 'all'"
                                @update:model-value="
                                    filterState[filter.key] =
                                        $event === 'all' ? '' : String($event)
                                "
                            >
                                <SelectTrigger
                                    :id="`filter_${filter.key}`"
                                    class="w-full min-w-0"
                                    ><SelectValue
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">{{
                                        filter.all
                                    }}</SelectItem>
                                    <SelectItem
                                        v-for="option in filter.options"
                                        :key="option.value"
                                        :value="option.value"
                                        >{{ option.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            type="submit"
                            class="min-h-11 sm:col-span-2 sm:justify-self-end lg:col-span-4"
                            >{{
                                t('finance.transactions.filters.apply')
                            }}</Button
                        >
                    </div>
                </form>
            </header>

            <section
                v-if="showSubtotal"
                class="border-border/70 bg-muted/25 grid gap-4 border-b p-4 sm:grid-cols-4 sm:p-5"
                :aria-label="t('finance.transactions.subtotal.title')"
                aria-live="polite"
            >
                <div>
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.transactions.subtotal.count') }}
                    </p>
                    <p class="font-data mt-1 text-base font-semibold">
                        {{ subtotalState.count }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.transactions.subtotal.income') }}
                    </p>
                    <p
                        class="font-data text-income mt-1 text-base font-semibold"
                    >
                        {{ formatMoney(subtotalState.income_minor) }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.transactions.subtotal.expense') }}
                    </p>
                    <p
                        class="font-data text-expense mt-1 text-base font-semibold"
                    >
                        {{ formatMoney(subtotalState.expense_minor) }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.transactions.subtotal.transfer') }}
                    </p>
                    <p
                        class="font-data text-forecast mt-1 text-base font-semibold"
                    >
                        {{ formatMoney(subtotalState.transfer_minor) }}
                    </p>
                </div>
            </section>

            <div v-if="grouped.length">
                <section v-for="[date, dayItems] in grouped" :key="date">
                    <h3
                        class="border-border/60 bg-muted/60 text-muted-foreground border-b px-5 py-2 text-[11px] font-extrabold tracking-[0.14em] uppercase"
                    >
                        {{
                            formatDate(date, {
                                weekday: 'long',
                                day: '2-digit',
                                month: 'long',
                            })
                        }}
                    </h3>
                    <TransactionRow
                        v-for="item in dayItems"
                        :key="item.id"
                        :transaction="item"
                        :reload-props="transactionReloadProps"
                        @open="openDetail"
                        @update="updateTransaction"
                    />
                </section>
            </div>
            <div v-else class="grid place-items-center px-6 py-16 text-center">
                <div class="max-w-sm">
                    <span
                        class="bg-primary/10 text-primary mx-auto grid size-14 place-items-center rounded-2xl"
                    >
                        <Search
                            v-if="hasFilters"
                            class="size-6"
                            aria-hidden="true"
                        />
                        <Plus v-else class="size-6" aria-hidden="true" />
                    </span>
                    <h3 class="mt-5 text-xl font-extrabold">
                        {{
                            t(
                                hasFilters
                                    ? 'finance.transactions.filters.emptyTitle'
                                    : 'finance.transactions.emptyTitle',
                            )
                        }}
                    </h3>
                    <p class="text-muted-foreground mt-2 text-sm leading-6">
                        {{
                            t(
                                hasFilters
                                    ? 'finance.transactions.filters.emptyDescription'
                                    : 'finance.transactions.emptyDescription',
                            )
                        }}
                    </p>
                    <Button
                        v-if="hasFilters"
                        variant="outline"
                        class="mt-5 min-h-11"
                        @click="clearFilters"
                        >{{ t('finance.transactions.filters.clear') }}</Button
                    >
                    <Button v-else class="mt-5 min-h-11" @click="openCreate">{{
                        t('finance.transactions.new')
                    }}</Button>
                </div>
            </div>
        </section>

        <TransactionPanel
            v-model:open="panelOpen"
            v-model:mode="panelMode"
            :transaction="selected"
            :accounts="accounts"
            :categories="categories"
            :default-due-on="defaultDueOn"
            :reload-props="transactionReloadProps"
            @transaction-update="updateTransaction"
        />
    </section>
</template>
