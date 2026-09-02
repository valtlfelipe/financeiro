<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    formatMinorForInput,
    formatMoneyInputOnBlur,
    formatMoneyInputWithCaret,
    parseMoneyInputToMinor,
} from '@/lib/money-input';
import { store, update } from '@/routes/transactions';
import { useOnline } from '@/composables/useOnline';
import type { Account, Category, Transaction } from '@/types';

const props = withDefaults(
    defineProps<{
        transaction?: Transaction | null;
        forceCreate?: boolean;
        accounts: Account[];
        categories: Category[];
        defaultDueOn?: string;
        online?: boolean;
    }>(),
    {
        online: true,
    },
);
const emit = defineEmits<{ saved: [] }>();
const { t } = useI18n();
const page = usePage();
const networkOnline = useOnline();

const amount = ref('');
const form = useForm({
    type: 'expense',
    amount_minor: 0,
    description: '',
    account_id: '',
    destination_account_id: '',
    category_id: '',
    due_on: props.defaultDueOn ?? new Date().toISOString().slice(0, 10),
    notes: '',
    settled: false,
    series_kind: '',
    frequency: 'monthly',
    ends_on: '',
    installments: 2,
    scope: 'single',
});

const editing = computed(
    () =>
        props.transaction !== null &&
        props.transaction !== undefined &&
        !props.forceCreate,
);
const availableCategories = computed(() =>
    props.categories.filter(
        (category) =>
            form.type === 'transfer' ||
            category.type === form.type ||
            category.type === 'both',
    ),
);

function hydrate(transaction?: Transaction | null): void {
    if (!transaction) {
        form.reset();
        amount.value = '';
        form.account_id = String(props.accounts[0]?.id ?? '');
        form.category_id = String(availableCategories.value[0]?.id ?? '');
        form.due_on =
            props.defaultDueOn ?? new Date().toISOString().slice(0, 10);
        return;
    }

    form.type = transaction.type;
    form.description = transaction.description;
    form.account_id = String(transaction.account.id);
    form.destination_account_id = String(
        transaction.destinationAccount?.id ?? '',
    );
    form.category_id = String(transaction.category?.id ?? '');
    form.due_on = transaction.dueOn;
    form.notes = transaction.notes ?? '';
    form.settled = transaction.settledAt !== null;
    form.series_kind = '';
    form.scope = 'single';
    amount.value = formatMinorForInput(
        transaction.amountMinor,
        page.props.locale,
    );
}

watch(() => props.transaction, hydrate, { immediate: true });
watch(
    () => form.type,
    () => {
        if (form.type === 'transfer') {
            form.category_id = '';
            form.destination_account_id = String(
                props.accounts.find(
                    (account) => String(account.id) !== form.account_id,
                )?.id ?? '',
            );
        } else if (
            !availableCategories.value.some(
                (category) => String(category.id) === form.category_id,
            )
        ) {
            form.category_id = String(availableCategories.value[0]?.id ?? '');
        }
    },
);
watch(
    () => form.account_id,
    () => {
        if (
            form.type === 'transfer' &&
            form.destination_account_id === form.account_id
        ) {
            form.destination_account_id = String(
                props.accounts.find(
                    (account) => String(account.id) !== form.account_id,
                )?.id ?? '',
            );
        }
    },
);

function amountMinor(): number {
    return parseMoneyInputToMinor(amount.value, page.props.locale);
}

async function handleAmountInput(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const formatted = formatMoneyInputWithCaret(
        input.value,
        input.selectionStart ?? input.value.length,
        page.props.locale,
    );

    amount.value = formatted.value;
    await nextTick();

    if (input.ownerDocument.activeElement === input) {
        input.setSelectionRange(formatted.caret, formatted.caret);
    }
}

function handleAmountBlur(): void {
    amount.value = formatMoneyInputOnBlur(amount.value, page.props.locale);
}

function submit(): void {
    form.amount_minor = amountMinor();
    form.transform((data) => ({
        ...data,
        destination_account_id:
            data.type === 'transfer'
                ? Number(data.destination_account_id)
                : null,
        category_id: data.type === 'transfer' ? null : Number(data.category_id),
        account_id: Number(data.account_id),
        series_kind:
            editing.value || data.series_kind === '' ? null : data.series_kind,
        frequency: data.series_kind === 'recurring' ? data.frequency : null,
        ends_on:
            data.series_kind === 'recurring' && data.ends_on
                ? data.ends_on
                : null,
        installments:
            data.series_kind === 'installment' ? data.installments : null,
    }));

    const options = { preserveScroll: true, onSuccess: () => emit('saved') };
    if (editing.value && props.transaction)
        form.patch(update.url(props.transaction.id), options);
    else form.post(store.url(), options);
}
</script>

<template>
    <form class="grid gap-5" @submit.prevent="submit">
        <div
            class="grid grid-cols-3 gap-2"
            role="group"
            :aria-label="t('finance.transactions.filters.type')"
        >
            <button
                v-for="type in ['expense', 'income', 'transfer'] as const"
                :key="type"
                type="button"
                class="min-h-11 rounded-xl border px-2 text-xs font-bold transition-colors"
                :class="
                    form.type === type
                        ? 'border-primary bg-primary text-white'
                        : 'border-border text-muted-foreground bg-white'
                "
                :disabled="type === 'transfer' && accounts.length < 2"
                :title="
                    type === 'transfer' && accounts.length < 2
                        ? t(
                              'finance.transactions.form.transferRequiresTwoAccounts',
                          )
                        : undefined
                "
                @click="form.type = type"
            >
                {{ t(`finance.transactions.type.${type}`) }}
            </button>
        </div>

        <p
            v-if="accounts.length < 2"
            role="status"
            class="bg-forecast/10 text-forecast rounded-xl px-3 py-2 text-sm"
        >
            {{ t('finance.transactions.form.transferRequiresTwoAccounts') }}
        </p>

        <div class="grid gap-2">
            <Label for="description">{{
                t('finance.transactions.form.description')
            }}</Label>
            <Input
                id="description"
                v-model="form.description"
                required
                :placeholder="
                    t('finance.transactions.form.descriptionPlaceholder')
                "
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-2">
            <Label for="amount">{{
                t('finance.transactions.form.amount')
            }}</Label>
            <div class="relative">
                <span
                    class="text-muted-foreground absolute inset-y-0 left-3 flex items-center text-sm font-bold"
                    >{{ t('finance.transactions.form.currencySymbol') }}</span
                >
                <Input
                    id="amount"
                    :model-value="amount"
                    inputmode="decimal"
                    required
                    class="font-data h-12 pl-10 text-lg font-medium"
                    :placeholder="
                        t('finance.transactions.form.amountPlaceholder')
                    "
                    @blur="handleAmountBlur"
                    @input="handleAmountInput"
                />
            </div>
            <InputError :message="form.errors.amount_minor" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="account_id">{{
                    t('finance.transactions.form.account')
                }}</Label>
                <select
                    id="account_id"
                    v-model="form.account_id"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                    required
                >
                    <option
                        v-for="account in accounts"
                        :key="account.id"
                        :value="String(account.id)"
                    >
                        {{ account.name }}
                    </option>
                </select>
                <InputError :message="form.errors.account_id" />
            </div>
            <div v-if="form.type === 'transfer'" class="grid gap-2">
                <Label for="destination_account_id">{{
                    t('finance.transactions.form.destinationAccount')
                }}</Label>
                <select
                    id="destination_account_id"
                    v-model="form.destination_account_id"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                    required
                >
                    <option value="" disabled>{{ t('common.select') }}</option>
                    <option
                        v-for="account in accounts.filter(
                            (item) => String(item.id) !== form.account_id,
                        )"
                        :key="account.id"
                        :value="String(account.id)"
                    >
                        {{ account.name }}
                    </option>
                </select>
                <InputError :message="form.errors.destination_account_id" />
            </div>
            <div v-else class="grid gap-2">
                <Label for="category_id">{{
                    t('finance.transactions.form.category')
                }}</Label>
                <select
                    id="category_id"
                    v-model="form.category_id"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                    required
                >
                    <option
                        v-for="category in availableCategories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        {{ category.name }}
                    </option>
                </select>
                <InputError :message="form.errors.category_id" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="due_on">{{
                    t('finance.transactions.form.date')
                }}</Label>
                <Input id="due_on" v-model="form.due_on" type="date" required />
                <InputError :message="form.errors.due_on" />
            </div>
            <div v-if="!editing" class="grid gap-2">
                <Label for="series_kind">{{
                    t('finance.transactions.form.schedule')
                }}</Label>
                <select
                    id="series_kind"
                    v-model="form.series_kind"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                >
                    <option value="">
                        {{ t('finance.transactions.series.single') }}
                    </option>
                    <option value="recurring">
                        {{ t('finance.transactions.series.recurring') }}
                    </option>
                    <option value="installment">
                        {{ t('finance.transactions.series.installment') }}
                    </option>
                </select>
            </div>
        </div>

        <div
            v-if="!editing && form.series_kind === 'recurring'"
            class="bg-muted grid gap-4 rounded-2xl p-4 sm:grid-cols-2"
        >
            <div class="grid gap-2">
                <Label for="frequency">{{
                    t('finance.transactions.series.frequency')
                }}</Label>
                <select
                    id="frequency"
                    v-model="form.frequency"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                >
                    <option value="weekly">
                        {{ t('finance.transactions.series.weekly') }}
                    </option>
                    <option value="monthly">
                        {{ t('finance.transactions.series.monthly') }}
                    </option>
                    <option value="yearly">
                        {{ t('finance.transactions.series.yearly') }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="ends_on">{{
                    t('finance.transactions.series.endsOn')
                }}</Label>
                <Input id="ends_on" v-model="form.ends_on" type="date" />
            </div>
        </div>

        <div
            v-if="!editing && form.series_kind === 'installment'"
            class="bg-muted grid gap-2 rounded-2xl p-4"
        >
            <Label for="installments">{{
                t('finance.transactions.series.installments')
            }}</Label>
            <Input
                id="installments"
                v-model="form.installments"
                type="number"
                min="2"
                max="120"
                required
            />
            <InputError :message="form.errors.installments" />
        </div>

        <label
            class="border-border flex min-h-11 items-center gap-3 rounded-xl border px-3 text-sm font-semibold"
        >
            <input
                v-model="form.settled"
                type="checkbox"
                class="accent-primary size-4"
            />
            {{ t('finance.transactions.form.settled') }}
        </label>

        <div v-if="editing && transaction?.series" class="grid gap-2">
            <Label for="scope">{{
                t('finance.transactions.series.changeScope')
            }}</Label>
            <select
                id="scope"
                v-model="form.scope"
                class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
            >
                <option value="single">
                    {{ t('finance.transactions.series.onlyThis') }}
                </option>
                <option value="future">
                    {{ t('finance.transactions.series.thisAndFuture') }}
                </option>
            </select>
        </div>

        <div class="grid gap-2">
            <Label for="notes"
                >{{ t('finance.transactions.form.notes') }}
                <span class="text-muted-foreground font-normal"
                    >({{ t('common.optional') }})</span
                ></Label
            >
            <textarea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="border-input rounded-xl border bg-white px-3 py-2 text-sm"
                :placeholder="t('finance.transactions.form.notesPlaceholder')"
            />
            <InputError :message="form.errors.notes" />
        </div>

        <p
            v-if="!networkOnline"
            role="status"
            class="bg-destructive/10 text-destructive rounded-xl px-3 py-2 text-sm"
        >
            {{ t('common.offline') }}
        </p>

        <Button
            type="submit"
            size="lg"
            class="w-full"
            :disabled="
                form.processing ||
                online === false ||
                !networkOnline ||
                (form.type === 'transfer' && accounts.length < 2)
            "
        >
            <Spinner v-if="form.processing" />
            {{
                editing
                    ? t('finance.transactions.form.submitUpdate')
                    : t('finance.transactions.form.submitCreate')
            }}
        </Button>
    </form>
</template>
