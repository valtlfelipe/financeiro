<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Archive, Plus, WalletCards, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import MoneyInput from '@/components/finance/MoneyInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import { useOnline } from '@/composables/useOnline';
import { randomColor } from '@/lib/colors';
import { formatMinorForInput, parseMoneyInputToMinor } from '@/lib/money-input';
import { destroy, store } from '@/routes/accounts';
import type { Account } from '@/types';

defineProps<{ accounts: Account[] }>();
const page = usePage();
const { t } = useI18n();
const { formatMoney } = useFinanceFormat();
const showForm = ref(false);
const online = useOnline();
const archiveForm = useForm({});

function archive(account: Account): void {
    if (archiveForm.processing || !online.value) return;
    archiveForm.delete(destroy.url(account.id), { preserveScroll: true });
}
const amount = ref(formatMinorForInput(0, page.props.locale));
const form = useForm({
    name: '',
    type: 'checking',
    initial_balance_minor: 0,
    balance_date: new Date().toISOString().slice(0, 10),
    color: '#148A62',
    icon: 'wallet-cards',
});

watch(showForm, (open) => {
    if (open) form.color = randomColor(form.color);
});

function submit(): void {
    form.initial_balance_minor = parseMoneyInputToMinor(
        amount.value,
        page.props.locale,
    );
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            amount.value = formatMinorForInput(0, page.props.locale);
            showForm.value = false;
        },
    });
}
</script>

<template>
    <section
        class="border-border/80 bg-card overflow-hidden rounded-3xl border"
    >
        <Head :title="t('finance.accounts.title')" />
        <header
            class="border-border/70 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-4 border-b p-5 sm:flex sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 shrink-0 place-items-center rounded-2xl"
                ><WalletCards class="size-5"
            /></span>
            <div class="mr-auto min-w-0">
                <h2 class="text-xl font-extrabold">
                    {{ t('finance.accounts.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('finance.accounts.description') }}
                </p>
            </div>
            <Button
                class="col-span-2 min-h-11 gap-2"
                :variant="showForm ? 'outline' : 'default'"
                :aria-expanded="showForm"
                aria-controls="account_form"
                @click="showForm = !showForm"
                ><X v-if="showForm" class="size-4" aria-hidden="true" /><Plus
                    v-else
                    class="size-4"
                    aria-hidden="true"
                />{{
                    showForm ? t('common.cancel') : t('finance.accounts.new')
                }}</Button
            >
        </header>

        <form
            v-if="showForm"
            id="account_form"
            class="border-border bg-muted/50 grid gap-4 border-b p-5 sm:grid-cols-2 sm:p-6"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <Label for="account_name">{{
                    t('finance.accounts.name')
                }}</Label
                ><Input
                    id="account_name"
                    v-model="form.name"
                    required
                /><InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="account_type">{{
                    t('finance.accounts.type')
                }}</Label
                ><select
                    id="account_type"
                    v-model="form.type"
                    class="border-input bg-card h-11 rounded-xl border px-3 text-sm"
                >
                    <option
                        v-for="type in ['checking', 'savings', 'cash', 'other']"
                        :key="type"
                        :value="type"
                    >
                        {{ t(`finance.accounts.types.${type}`) }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="initial_balance">{{
                    t('finance.accounts.initialBalance')
                }}</Label
                ><MoneyInput
                    id="initial_balance"
                    v-model="amount"
                    class="font-data"
                /><InputError :message="form.errors.initial_balance_minor" />
            </div>
            <div class="grid gap-2">
                <Label for="balance_date">{{
                    t('finance.accounts.balanceDate')
                }}</Label
                ><Input
                    id="balance_date"
                    v-model="form.balance_date"
                    type="date"
                    required
                />
            </div>
            <div class="grid gap-2">
                <Label for="account_color">{{
                    t('finance.accounts.color')
                }}</Label
                ><Input
                    id="account_color"
                    v-model="form.color"
                    type="color"
                    class="h-11 p-1"
                />
            </div>
            <div class="flex items-end">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                    >{{ t('common.save') }}</Button
                >
            </div>
        </form>

        <div v-if="accounts.length" class="divide-border/70 divide-y">
            <article
                v-for="account in accounts"
                :key="account.id"
                class="flex items-center gap-4 px-5 py-4 sm:px-6"
            >
                <span
                    class="grid size-10 place-items-center rounded-2xl text-white"
                    :style="{ backgroundColor: account.color }"
                    ><WalletCards class="size-4"
                /></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">{{ account.name }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        {{ t(`finance.accounts.types.${account.type}`) }}
                    </p>
                </div>
                <span class="font-data hidden text-sm sm:block">{{
                    formatMoney(account.initialBalanceMinor)
                }}</span>
                <ConfirmationDialog
                    v-if="!account.isArchived"
                    :title="t('finance.accounts.archiveTitle')"
                    :resource-name="account.name"
                    :description="
                        t('finance.accounts.archiveDescription', {
                            name: account.name,
                        })
                    "
                    :confirm-label="t('finance.accounts.archiveAction')"
                    :processing="archiveForm.processing"
                    :disabled="!online"
                    :error="
                        Object.values(archiveForm.errors).find(
                            (message): message is string =>
                                typeof message === 'string',
                        )
                    "
                    @confirm="archive(account)"
                >
                    <template #trigger>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-11 shrink-0"
                            :disabled="!online || archiveForm.processing"
                            :aria-label="
                                t('finance.accounts.archiveLabel', {
                                    name: account.name,
                                })
                            "
                            @click="archiveForm.clearErrors()"
                        >
                            <Archive class="size-4" aria-hidden="true" />
                        </Button>
                    </template>
                </ConfirmationDialog>
                <span
                    v-else
                    class="bg-muted text-muted-foreground rounded-full px-3 py-1 text-xs font-bold"
                    >{{ t('common.archived') }}</span
                >
            </article>
        </div>
        <p v-else class="text-muted-foreground p-10 text-center text-sm">
            {{ t('common.empty') }}
        </p>
    </section>
</template>
