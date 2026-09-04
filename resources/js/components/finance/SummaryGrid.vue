<script setup lang="ts">
import { ChevronDown, Equal, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import { minorIsNegative } from '@/lib/minor-amount';
import { accountBalanceAccessibleLabel } from '@/lib/monthly-summary';
import type { MonthlyAccountBalance, MonthlySummary } from '@/types';

const props = defineProps<{ summary: MonthlySummary }>();
const { t } = useI18n();
const { formatMoney } = useFinanceFormat();
const detailsOpen = ref(false);

const realizedLabel = computed(() =>
    t(`finance.overview.realizedBalance.${props.summary.period}`),
);
const realizedHint = computed(() =>
    t(`finance.overview.realizedBalanceHint.${props.summary.period}`),
);
const realizedTone = computed(() =>
    minorIsNegative(props.summary.realized_balance_minor)
        ? 'text-expense'
        : 'text-income',
);
const forecastTone = computed(() =>
    minorIsNegative(props.summary.forecast_balance_minor)
        ? 'text-expense'
        : 'text-forecast',
);
const showAccountForecast = computed(() => props.summary.period !== 'past');
const accountRealizedLabel = computed(() =>
    props.summary.period === 'past'
        ? realizedLabel.value
        : t('finance.overview.accountBalanceNow'),
);

function accountLabel(account: MonthlyAccountBalance): string {
    return accountBalanceAccessibleLabel(account, formatMoney, {
        realized: accountRealizedLabel.value,
        forecast: showAccountForecast.value
            ? t('finance.overview.accountBalanceEndOfMonth')
            : undefined,
        archived: t('finance.overview.archivedAccount'),
    });
}
</script>

<template>
    <Collapsible v-model:open="detailsOpen">
        <section
            class="border-border/80 bg-card overflow-hidden rounded-3xl border"
            aria-labelledby="current_balance_label"
            aria-describedby="current_balance_hint"
        >
            <div
                class="grid grid-cols-[minmax(0,1fr)_auto] items-start gap-x-3 p-4 sm:p-5"
            >
                <div class="min-w-0">
                    <p
                        id="current_balance_label"
                        class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                    >
                        {{ realizedLabel }}
                    </p>
                    <p
                        class="font-data mt-2 text-xl font-semibold tracking-tight sm:text-2xl"
                        :class="realizedTone"
                    >
                        {{ formatMoney(summary.realized_balance_minor) }}
                    </p>
                </div>

                <CollapsibleTrigger as-child>
                    <Button
                        variant="ghost"
                        class="text-muted-foreground hover:text-foreground -mr-2 min-h-11 shrink-0 gap-2 rounded-xl px-3"
                    >
                        {{
                            detailsOpen
                                ? t('finance.overview.hideBalanceDetails')
                                : t('finance.overview.showBalanceDetails')
                        }}
                        <ChevronDown
                            class="size-4 transition-transform duration-200 motion-reduce:transition-none"
                            :class="{ 'rotate-180': detailsOpen }"
                            aria-hidden="true"
                        />
                    </Button>
                </CollapsibleTrigger>

                <p
                    id="current_balance_hint"
                    class="text-muted-foreground col-span-2 mt-1.5 max-w-2xl text-sm leading-relaxed"
                >
                    {{ realizedHint }}
                </p>
            </div>

            <CollapsibleContent
                class="data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:slide-out-to-top-1 data-[state=open]:animate-in data-[state=open]:fade-in-0 data-[state=open]:slide-in-from-top-1 duration-200 motion-reduce:animate-none"
            >
                <div class="border-border/70 bg-muted/25 border-t p-4 sm:p-5">
                    <div
                        class="grid gap-4 lg:grid-cols-[minmax(16rem,0.75fr)_minmax(0,1.25fr)]"
                    >
                        <div
                            class="border-border/70 bg-card overflow-hidden rounded-2xl border"
                        >
                            <div class="bg-forecast/10 p-4">
                                <p
                                    class="text-forecast text-xs font-bold tracking-wider uppercase"
                                >
                                    {{ t('finance.overview.forecastBalance') }}
                                </p>
                                <p
                                    class="font-data mt-2 text-xl font-semibold tracking-tight"
                                    :class="forecastTone"
                                >
                                    {{
                                        formatMoney(
                                            summary.forecast_balance_minor,
                                        )
                                    }}
                                </p>
                                <p
                                    class="text-muted-foreground mt-1.5 text-xs leading-relaxed"
                                >
                                    {{ t('finance.overview.balanceFlowHint') }}
                                </p>
                            </div>

                            <div class="border-border/60 border-t p-4">
                                <h3
                                    class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                                >
                                    {{
                                        t('finance.overview.balanceComposition')
                                    }}
                                </h3>
                                <dl class="mt-3 grid grid-cols-2 gap-3">
                                    <div>
                                        <dt
                                            class="text-muted-foreground text-xs"
                                        >
                                            {{
                                                t(
                                                    'finance.overview.plannedIncome',
                                                )
                                            }}
                                        </dt>
                                        <dd
                                            class="font-data text-income mt-1 text-sm font-medium"
                                        >
                                            +
                                            {{
                                                formatMoney(
                                                    summary.planned_income_minor,
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt
                                            class="text-muted-foreground text-xs"
                                        >
                                            {{
                                                t(
                                                    'finance.overview.plannedExpense',
                                                )
                                            }}
                                        </dt>
                                        <dd
                                            class="font-data text-expense mt-1 text-sm font-medium"
                                        >
                                            −
                                            {{
                                                formatMoney(
                                                    summary.planned_expense_minor,
                                                )
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <section
                            class="border-border/70 bg-card rounded-2xl border p-4"
                            aria-labelledby="account_balances_heading"
                        >
                            <div
                                class="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-3"
                                :class="{
                                    'sm:grid-cols-[minmax(0,1fr)_auto_auto]':
                                        showAccountForecast,
                                }"
                            >
                                <h3
                                    id="account_balances_heading"
                                    class="text-sm font-extrabold"
                                >
                                    {{
                                        t(
                                            'finance.overview.accountBalancesTitle',
                                        )
                                    }}
                                </h3>
                                <span
                                    class="text-muted-foreground hidden text-right text-[11px] font-bold tracking-wider uppercase sm:block"
                                >
                                    {{ accountRealizedLabel }}
                                </span>
                                <span
                                    v-if="showAccountForecast"
                                    class="text-muted-foreground hidden text-right text-[11px] font-bold tracking-wider uppercase sm:block"
                                >
                                    {{
                                        t(
                                            'finance.overview.accountBalanceEndOfMonth',
                                        )
                                    }}
                                </span>
                            </div>

                            <ul class="mt-2">
                                <li
                                    v-for="account in summary.account_balances"
                                    :key="account.id"
                                    class="border-border/60 grid grid-cols-2 items-center gap-x-3 gap-y-2 border-b py-3 last:border-0"
                                    :class="{
                                        'sm:grid-cols-[minmax(0,1fr)_auto_auto]':
                                            showAccountForecast,
                                    }"
                                    :aria-label="accountLabel(account)"
                                >
                                    <span
                                        class="col-span-2 flex min-w-0 items-center gap-2 text-sm font-semibold sm:col-span-1"
                                    >
                                        <i
                                            class="size-2.5 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor: account.color,
                                            }"
                                            aria-hidden="true"
                                        />
                                        <span class="truncate">
                                            {{ account.name }}
                                        </span>
                                        <span
                                            v-if="account.is_archived"
                                            class="text-muted-foreground hidden text-[11px] font-medium sm:inline"
                                        >
                                            ·
                                            {{
                                                t(
                                                    'finance.overview.archivedAccount',
                                                )
                                            }}
                                        </span>
                                    </span>

                                    <span
                                        class="font-data text-left text-xs whitespace-nowrap sm:text-right sm:text-sm"
                                        :class="{
                                            'text-expense': minorIsNegative(
                                                account.realized_balance_minor,
                                            ),
                                        }"
                                    >
                                        <span
                                            class="text-muted-foreground mb-0.5 block text-[10px] sm:hidden"
                                        >
                                            {{ accountRealizedLabel }}
                                        </span>
                                        {{
                                            formatMoney(
                                                account.realized_balance_minor,
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-if="showAccountForecast"
                                        class="font-data text-right text-xs whitespace-nowrap sm:text-sm"
                                        :class="
                                            minorIsNegative(
                                                account.forecast_balance_minor,
                                            )
                                                ? 'text-expense'
                                                : 'text-forecast'
                                        "
                                    >
                                        <span
                                            class="text-muted-foreground mb-0.5 block text-[10px] sm:hidden"
                                        >
                                            {{
                                                t(
                                                    'finance.overview.accountBalanceEndOfMonth',
                                                )
                                            }}
                                        </span>
                                        {{
                                            formatMoney(
                                                account.forecast_balance_minor,
                                            )
                                        }}
                                    </span>
                                </li>
                            </ul>
                        </section>
                    </div>

                    <section
                        class="border-border/70 bg-card mt-4 rounded-2xl border p-4"
                        aria-labelledby="balance_calculation_heading"
                    >
                        <h3
                            id="balance_calculation_heading"
                            class="text-sm font-extrabold"
                        >
                            {{ t('finance.overview.balanceCalculation') }}
                        </h3>
                        <div
                            class="mt-3 grid items-center gap-2 sm:grid-cols-[minmax(0,1fr)_2rem_minmax(0,1fr)_2rem_minmax(0,1fr)]"
                        >
                            <div>
                                <p class="text-muted-foreground text-xs">
                                    {{ t('finance.overview.openingBalance') }}
                                </p>
                                <p class="font-data mt-1 text-sm font-medium">
                                    {{
                                        formatMoney(
                                            summary.opening_balance_minor,
                                        )
                                    }}
                                </p>
                            </div>
                            <Plus
                                class="text-muted-foreground hidden size-4 place-self-center sm:block"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="text-muted-foreground text-xs">
                                    <span class="sm:hidden" aria-hidden="true"
                                        >+
                                    </span>
                                    {{ t('finance.overview.forecastMovement') }}
                                </p>
                                <p class="font-data mt-1 text-sm font-medium">
                                    {{
                                        formatMoney(
                                            summary.forecast_change_minor,
                                        )
                                    }}
                                </p>
                            </div>
                            <Equal
                                class="text-muted-foreground hidden size-4 place-self-center sm:block"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="text-muted-foreground text-xs">
                                    <span class="sm:hidden" aria-hidden="true"
                                        >=
                                    </span>
                                    {{ t('finance.overview.forecastBalance') }}
                                </p>
                                <p
                                    class="font-data mt-1 text-sm font-semibold"
                                    :class="forecastTone"
                                >
                                    {{
                                        formatMoney(
                                            summary.forecast_balance_minor,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </CollapsibleContent>
        </section>
    </Collapsible>
</template>
