<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Filter, Plus, Search } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import SummaryGrid from '@/components/finance/SummaryGrid.vue';
import TransactionPanel from '@/components/finance/TransactionPanel.vue';
import TransactionRow from '@/components/finance/TransactionRow.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import { index } from '@/routes/transactions';
import type { Account, Category, MonthlySummary, Transaction } from '@/types';

const props = defineProps<{
    month: string;
    summary: MonthlySummary;
    transactions: Transaction[];
    accounts: Account[];
    categories: Category[];
    filters: Record<string, string | null>;
}>();
const { t } = useI18n();
const { formatDate, formatMonth } = useFinanceFormat();
const items = ref([...props.transactions]);
const summaryState = ref(props.summary);
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
const selected = computed(
    () => items.value.find((item) => item.id === selectedId.value) ?? null,
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
    const today = new Date();
    const [year, month] = props.month.split('-').map(Number);
    const lastDay = new Date(year, month, 0).getDate();
    const day = String(Math.min(today.getDate(), lastDay)).padStart(2, '0');

    return `${props.month}-${day}`;
});

watch(
    () => props.transactions,
    (next) => {
        items.value = [...next];
    },
);
watch(
    () => props.summary,
    (next) => {
        summaryState.value = next;
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

function updateTransaction(
    transaction: Transaction,
    summary?: MonthlySummary,
): void {
    items.value = items.value.map((item) =>
        item.id === transaction.id ? transaction : item,
    );
    if (summary && 'planned_income_minor' in summary)
        summaryState.value = summary;
}
</script>

<template>
    <Head :title="t('finance.transactions.title')" />

    <section class="grid gap-6">
        <header class="flex flex-wrap items-center gap-3">
            <div class="mr-auto">
                <h1 class="text-3xl font-extrabold tracking-tight">
                    {{ t('finance.transactions.title') }}
                </h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('finance.transactions.subtitle') }}
                </p>
            </div>
            <Button size="lg" class="gap-2" @click="openCreate"
                ><Plus class="size-4" />{{
                    t('finance.transactions.new')
                }}</Button
            >
        </header>

        <SummaryGrid :summary="summaryState" />

        <section
            class="border-border/80 overflow-hidden rounded-3xl border bg-white"
        >
            <header class="border-border/70 grid gap-4 border-b p-4 sm:p-5">
                <div
                    class="grid grid-cols-[2.75rem_1fr_2.75rem] items-center gap-2 sm:mx-auto sm:w-96"
                >
                    <Link
                        :href="
                            index({
                                query: {
                                    month: shiftedMonth(-1),
                                    ...filterState,
                                },
                            })
                        "
                        class="hover:bg-muted grid size-11 place-items-center rounded-xl"
                        :aria-label="t('finance.transactions.previousMonth')"
                        ><ChevronLeft class="size-5"
                    /></Link>
                    <h2 class="text-center text-base font-extrabold capitalize">
                        {{ formatMonth(month) }}
                    </h2>
                    <Link
                        :href="
                            index({
                                query: {
                                    month: shiftedMonth(1),
                                    ...filterState,
                                },
                            })
                        "
                        class="hover:bg-muted grid size-11 place-items-center rounded-xl"
                        :aria-label="t('finance.transactions.nextMonth')"
                        ><ChevronRight class="size-5"
                    /></Link>
                </div>

                <form
                    class="grid gap-3 sm:grid-cols-[1fr_auto]"
                    @submit.prevent="applyFilters"
                >
                    <button type="submit" class="sr-only">
                        {{ t('common.search') }}
                    </button>
                    <div class="relative">
                        <Search
                            class="text-muted-foreground absolute top-1/2 left-3 size-4 -translate-y-1/2"
                            aria-hidden="true"
                        />
                        <Input
                            v-model="filterState.search"
                            class="bg-muted h-11 rounded-xl pl-10"
                            :placeholder="
                                t('finance.transactions.searchPlaceholder')
                            "
                            @keydown.enter.prevent="applyFilters"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        class="h-11 gap-2"
                        @click="filtersOpen = !filtersOpen"
                        ><Filter class="size-4" />{{
                            t('common.filters')
                        }}</Button
                    >
                    <div
                        v-if="filtersOpen"
                        class="grid gap-2 sm:col-span-2 sm:grid-cols-4"
                    >
                        <select
                            v-model="filterState.type"
                            class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                        >
                            <option value="">
                                {{ t('finance.transactions.filters.allTypes') }}
                            </option>
                            <option
                                v-for="type in [
                                    'income',
                                    'expense',
                                    'transfer',
                                ]"
                                :key="type"
                                :value="type"
                            >
                                {{ t(`finance.transactions.type.${type}`) }}
                            </option>
                        </select>
                        <select
                            v-model="filterState.status"
                            class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                        >
                            <option value="">
                                {{
                                    t(
                                        'finance.transactions.filters.allStatuses',
                                    )
                                }}
                            </option>
                            <option value="pending">
                                {{ t('finance.transactions.status.pending') }}
                            </option>
                            <option value="settled">
                                {{ t('finance.transactions.status.settled') }}
                            </option>
                        </select>
                        <select
                            v-model="filterState.account_id"
                            class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                        >
                            <option value="">
                                {{
                                    t(
                                        'finance.transactions.filters.allAccounts',
                                    )
                                }}
                            </option>
                            <option
                                v-for="account in accounts"
                                :key="account.id"
                                :value="String(account.id)"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                        <select
                            v-model="filterState.category_id"
                            class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                        >
                            <option value="">
                                {{
                                    t(
                                        'finance.transactions.filters.allCategories',
                                    )
                                }}
                            </option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                        <Button
                            type="submit"
                            class="sm:col-span-4 sm:justify-self-end"
                            >{{
                                t('finance.transactions.filters.apply')
                            }}</Button
                        >
                    </div>
                </form>
            </header>

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
                        @open="openDetail"
                        @update="updateTransaction"
                    />
                </section>
            </div>
            <div v-else class="grid place-items-center px-6 py-20 text-center">
                <div class="max-w-sm">
                    <span
                        class="bg-primary/10 text-primary mx-auto grid size-14 place-items-center rounded-2xl"
                        ><Plus class="size-6"
                    /></span>
                    <h2 class="mt-5 text-xl font-extrabold">
                        {{ t('finance.transactions.emptyTitle') }}
                    </h2>
                    <p class="text-muted-foreground mt-2 text-sm leading-6">
                        {{ t('finance.transactions.emptyDescription') }}
                    </p>
                    <Button class="mt-5" @click="openCreate">{{
                        t('finance.transactions.new')
                    }}</Button>
                </div>
            </div>
        </section>
    </section>

    <TransactionPanel
        v-model:open="panelOpen"
        v-model:mode="panelMode"
        :transaction="selected"
        :accounts="accounts"
        :categories="categories"
        :default-due-on="defaultDueOn"
        @transaction-update="updateTransaction"
    />
</template>
