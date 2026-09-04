<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Landmark, Sparkles } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import TransactionRow from '@/components/finance/TransactionRow.vue';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import { index as transactions } from '@/routes/transactions';
import type { Transaction } from '@/types';

const props = defineProps<{
    month: string;
    accounts: Array<{
        id: number;
        name: string;
        color: string;
        balanceMinor: string;
    }>;
    recentTransactions: Transaction[];
}>();
const { t } = useI18n();
const page = usePage();
const { formatMoney, formatMonth } = useFinanceFormat();
const recent = ref([...props.recentTransactions]);

watch(
    () => props.recentTransactions,
    (transactions) => {
        recent.value = [...transactions];
    },
);

function updateTransaction(item: Transaction): void {
    recent.value = recent.value.map((transaction) =>
        transaction.id === item.id ? item : transaction,
    );
    router.reload({ only: ['accounts'] });
}
</script>

<template>
    <section class="grid gap-7">
        <Head :title="t('common.navigation.overview')" />
        <header
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
        >
            <div>
                <p
                    class="text-primary flex items-center gap-2 text-xs font-bold tracking-[0.18em] uppercase"
                >
                    <Sparkles class="size-4" />{{ formatMonth(month) }}
                </p>
                <h1
                    class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl"
                >
                    {{
                        t('finance.overview.greeting', {
                            name: page.props.auth.user.name.split(' ')[0],
                        })
                    }}
                </h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    {{ t('finance.overview.title') }}
                </p>
            </div>
            <Link
                :href="transactions({ query: { month } })"
                class="bg-primary text-primary-foreground inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold"
            >
                {{ t('finance.overview.viewAll') }}<ArrowRight class="size-4" />
            </Link>
        </header>

        <div
            class="grid gap-5 lg:grid-cols-[minmax(0,1.55fr)_minmax(17rem,0.65fr)]"
        >
            <section
                class="border-border/80 bg-card overflow-hidden rounded-3xl border"
            >
                <header class="border-border/70 border-b px-5 py-5">
                    <h2 class="text-lg font-extrabold tracking-tight">
                        {{ t('finance.overview.pendingTitle') }}
                    </h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        {{ t('finance.overview.pendingDescription') }}
                    </p>
                </header>
                <div v-if="recent.length">
                    <TransactionRow
                        v-for="item in recent"
                        :key="item.id"
                        :transaction="item"
                        show-date
                        @update="updateTransaction"
                    />
                </div>
                <div
                    v-else
                    class="text-muted-foreground px-5 py-14 text-center text-sm"
                >
                    {{ t('common.empty') }}
                </div>
            </section>

            <aside
                class="bg-foreground text-background dark:bg-card dark:text-card-foreground dark:border-border rounded-3xl border border-transparent p-6"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="grid size-10 place-items-center rounded-2xl bg-white/10"
                        ><Landmark class="size-5"
                    /></span>
                    <h2 class="text-lg font-extrabold">
                        {{ t('finance.overview.accountsTitle') }}
                    </h2>
                </div>
                <div class="mt-6 grid gap-1">
                    <div
                        v-for="account in accounts"
                        :key="account.id"
                        class="flex items-center justify-between gap-4 border-b border-white/10 py-4 last:border-0"
                    >
                        <span
                            class="flex min-w-0 items-center gap-2 text-sm font-semibold"
                            ><i
                                class="size-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: account.color }"
                            />{{ account.name }}</span
                        >
                        <span class="font-data text-sm">{{
                            formatMoney(account.balanceMinor)
                        }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</template>
