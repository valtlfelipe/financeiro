<script setup lang="ts">
import {
    ArrowDownLeft,
    ArrowUpRight,
    CheckCircle2,
    Telescope,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import type { MonthlySummary } from '@/types';

const props = defineProps<{ summary: MonthlySummary }>();
const { t } = useI18n();
const { formatMoney } = useFinanceFormat();
const items = computed(() => [
    {
        label: t('finance.overview.plannedIncome'),
        value: props.summary.planned_income_minor,
        icon: ArrowDownLeft,
        tone: 'text-income',
        surface: 'bg-primary/10',
    },
    {
        label: t('finance.overview.plannedExpense'),
        value: props.summary.planned_expense_minor,
        icon: ArrowUpRight,
        tone: 'text-expense',
        surface: 'bg-expense/10',
    },
    {
        label: t('finance.overview.realizedBalance'),
        value: props.summary.realized_balance_minor,
        icon: CheckCircle2,
        tone: 'text-income',
        surface: 'bg-primary/10',
    },
    {
        label: t('finance.overview.forecastBalance'),
        value: props.summary.forecast_balance_minor,
        icon: Telescope,
        tone: 'text-forecast',
        surface: 'bg-forecast/10',
    },
]);
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <article
            v-for="item in items"
            :key="item.label"
            class="border-border/80 rounded-2xl border bg-white p-4 sm:p-5"
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
        </article>
    </div>
</template>
