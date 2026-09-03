<script setup lang="ts">
import {
    ArrowDownLeft,
    ArrowUpRight,
    CheckCircle2,
    Equal,
    Plus,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import type { MonthlySummary } from '@/types';

const props = defineProps<{ summary: MonthlySummary }>();
const { t } = useI18n();
const { formatMoney } = useFinanceFormat();

const realizedLabel = computed(() =>
    t(`finance.overview.realizedBalance.${props.summary.period}`),
);
const realizedHint = computed(() =>
    t(`finance.overview.realizedBalanceHint.${props.summary.period}`),
);
const realizedTone = computed(() =>
    props.summary.realized_balance_minor < 0 ? 'text-expense' : 'text-income',
);
const forecastTone = computed(() =>
    props.summary.forecast_balance_minor < 0 ? 'text-expense' : 'text-forecast',
);
const movementTone = computed(() =>
    props.summary.forecast_change_minor < 0 ? 'text-expense' : 'text-income',
);
const items = computed(() => [
    {
        label: realizedLabel.value,
        description: realizedHint.value,
        value: props.summary.realized_balance_minor,
        icon: CheckCircle2,
        tone: realizedTone.value,
        surface: 'bg-primary/10',
    },
    {
        label: t('finance.overview.plannedIncome'),
        description: null,
        value: props.summary.planned_income_minor,
        icon: ArrowDownLeft,
        tone: 'text-income',
        surface: 'bg-primary/10',
    },
    {
        label: t('finance.overview.plannedExpense'),
        description: null,
        value: props.summary.planned_expense_minor,
        icon: ArrowUpRight,
        tone: 'text-expense',
        surface: 'bg-expense/10',
    },
]);
</script>

<template>
    <div class="grid gap-3">
        <section
            class="border-border/80 bg-card rounded-3xl border p-4 sm:p-5"
            :aria-label="`${formatMoney(summary.opening_balance_minor)} + ${formatMoney(summary.forecast_change_minor)} = ${formatMoney(summary.forecast_balance_minor)}`"
        >
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h3 class="text-sm font-extrabold">
                    {{ t('finance.overview.balanceFlowTitle') }}
                </h3>
                <p class="text-muted-foreground text-xs">
                    {{ t('finance.overview.balanceFlowHint') }}
                </p>
            </div>

            <div
                class="mt-4 grid items-stretch gap-2 sm:grid-cols-[minmax(0,1fr)_2.75rem_minmax(0,1fr)_2.75rem_minmax(0,1fr)]"
            >
                <div class="bg-muted/70 rounded-2xl p-4">
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.overview.openingBalance') }}
                    </p>
                    <p class="font-data mt-3 text-lg font-semibold">
                        {{ formatMoney(summary.opening_balance_minor) }}
                    </p>
                </div>
                <span
                    class="text-muted-foreground grid size-11 place-items-center place-self-center rounded-full"
                    aria-hidden="true"
                >
                    <Plus class="size-5" />
                </span>
                <div class="bg-muted/70 rounded-2xl p-4">
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.overview.forecastMovement') }}
                    </p>
                    <p
                        class="font-data mt-3 text-lg font-semibold"
                        :class="movementTone"
                    >
                        {{ formatMoney(summary.forecast_change_minor) }}
                    </p>
                </div>
                <span
                    class="text-muted-foreground grid size-11 place-items-center place-self-center rounded-full"
                    aria-hidden="true"
                >
                    <Equal class="size-5" />
                </span>
                <div class="bg-forecast/10 rounded-2xl p-4">
                    <p
                        class="text-forecast text-xs font-bold tracking-wider uppercase"
                    >
                        {{ t('finance.overview.forecastBalance') }}
                    </p>
                    <p
                        class="font-data mt-3 text-xl font-semibold tracking-tight"
                        :class="forecastTone"
                    >
                        {{ formatMoney(summary.forecast_balance_minor) }}
                    </p>
                </div>
            </div>
        </section>

        <div class="grid gap-3 sm:grid-cols-3">
            <article
                v-for="item in items"
                :key="item.label"
                class="border-border/80 bg-card rounded-2xl border p-4 sm:p-5"
            >
                <div class="flex items-center justify-between gap-3">
                    <p
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ item.label }}
                    </p>
                    <span
                        class="grid size-9 place-items-center rounded-xl"
                        :class="[item.surface, item.tone]"
                    >
                        <component
                            :is="item.icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </span>
                </div>
                <p
                    class="font-data mt-5 text-xl font-medium tracking-tight"
                    :class="item.tone"
                >
                    {{ formatMoney(item.value) }}
                </p>
                <p
                    v-if="item.description"
                    class="text-muted-foreground mt-2 text-xs leading-relaxed"
                >
                    {{ item.description }}
                </p>
            </article>
        </div>
    </div>
</template>
