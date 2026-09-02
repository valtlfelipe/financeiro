<script setup lang="ts">
import { ArrowRight, CalendarClock, Repeat2 } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import type { MonthlySummary, Transaction } from '@/types';
import SettlementButton from './SettlementButton.vue';

const props = withDefaults(
    defineProps<{ transaction: Transaction; online?: boolean }>(),
    { online: true },
);
const emit = defineEmits<{
    open: [transaction: Transaction];
    update: [transaction: Transaction, summary: MonthlySummary];
}>();
const { t } = useI18n();
const { formatMoney } = useFinanceFormat();
const amountClass = computed(() =>
    props.transaction.type === 'expense'
        ? 'text-expense'
        : props.transaction.type === 'income'
          ? 'text-income'
          : 'text-forecast',
);
</script>

<template>
    <article
        class="group border-border/60 hover:bg-muted/60 grid cursor-pointer grid-cols-[auto_1fr_auto] items-center gap-3 border-b px-3 py-3 transition-colors last:border-0 sm:grid-cols-[auto_minmax(0,1fr)_minmax(8rem,auto)_auto] sm:px-5"
        role="button"
        tabindex="0"
        :aria-label="transaction.description"
        @click="emit('open', transaction)"
        @keydown.enter="emit('open', transaction)"
        @keydown.space.prevent="emit('open', transaction)"
    >
        <span
            class="grid size-10 place-items-center rounded-2xl text-white"
            :style="{
                backgroundColor:
                    transaction.category?.color ?? transaction.account.color,
            }"
        >
            <ArrowRight
                v-if="transaction.type === 'transfer'"
                class="size-4"
                aria-hidden="true"
            />
            <span v-else class="text-sm font-extrabold">{{
                (transaction.category?.name ?? transaction.account.name)
                    .slice(0, 1)
                    .toUpperCase()
            }}</span>
        </span>

        <div class="min-w-0">
            <p class="text-foreground truncate text-sm font-bold">
                {{ transaction.description }}
            </p>
            <p
                class="text-muted-foreground mt-1 flex items-center gap-2 truncate text-xs"
            >
                <span>{{ transaction.account.name }}</span>
                <span v-if="transaction.destinationAccount"
                    >→ {{ transaction.destinationAccount.name }}</span
                >
                <span v-else-if="transaction.category"
                    >· {{ transaction.category.name }}</span
                >
                <Repeat2
                    v-if="transaction.series?.kind === 'recurring'"
                    class="size-3.5"
                    :aria-label="t('finance.transactions.series.recurring')"
                />
                <CalendarClock
                    v-if="transaction.installmentNumber"
                    class="size-3.5"
                    aria-hidden="true"
                />
                <span v-if="transaction.installmentNumber">{{
                    t('finance.transactions.series.installmentLabel', {
                        current: transaction.installmentNumber,
                        total: transaction.installmentTotal,
                    })
                }}</span>
            </p>
        </div>

        <p
            class="font-data col-start-2 row-start-2 text-sm font-medium sm:col-start-3 sm:row-start-1 sm:text-right"
            :class="amountClass"
        >
            {{
                transaction.type === 'expense'
                    ? '−'
                    : transaction.type === 'income'
                      ? '+'
                      : ''
            }}{{ formatMoney(transaction.amountMinor) }}
        </p>
        <SettlementButton
            class="col-start-3 row-span-2 row-start-1 sm:col-start-4 sm:row-span-1"
            :transaction="transaction"
            :online="online"
            @update="(item, summary) => emit('update', item, summary)"
        />
    </article>
</template>
