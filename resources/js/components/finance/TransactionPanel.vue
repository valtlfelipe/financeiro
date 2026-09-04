<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Copy, Pencil, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useFinanceFormat } from '@/composables/useFinanceFormat';
import { useOnline } from '@/composables/useOnline';
import { destroy } from '@/routes/transactions';
import type { Account, Category, Transaction } from '@/types';
import SettlementButton from './SettlementButton.vue';
import TransactionForm from './TransactionForm.vue';
import TransactionScopeDialog from './TransactionScopeDialog.vue';

type PanelMode = 'create' | 'detail' | 'edit' | 'copy';
const props = withDefaults(
    defineProps<{
        open: boolean;
        mode: PanelMode;
        transaction?: Transaction | null;
        accounts: Account[];
        categories: Category[];
        defaultDueOn?: string;
        online?: boolean;
    }>(),
    {
        online: true,
    },
);
const emit = defineEmits<{
    'update:open': [open: boolean];
    'update:mode': [mode: PanelMode];
    transactionUpdate: [transaction: Transaction];
}>();
const { t } = useI18n();
const { formatDate, formatMoney } = useFinanceFormat();
const deleteOpen = ref(false);
const deleteForm = useForm({ scope: 'single' as 'single' | 'future' });
const networkOnline = useOnline();
const formAccounts = computed(() => {
    const accounts = new Map(
        props.accounts.map((account) => [account.id, account]),
    );

    if (props.mode === 'edit' && props.transaction) {
        accounts.set(props.transaction.account.id, props.transaction.account);

        if (props.transaction.destinationAccount) {
            accounts.set(
                props.transaction.destinationAccount.id,
                props.transaction.destinationAccount,
            );
        }
    }

    return [...accounts.values()];
});

watch(
    () => props.transaction?.id,
    () => {
        deleteOpen.value = false;
        deleteForm.clearErrors();
    },
);

function remove(scope: 'single' | 'future' = 'single'): void {
    if (
        !props.transaction ||
        deleteForm.processing ||
        !networkOnline.value ||
        props.online === false
    )
        return;
    deleteForm.scope = scope;
    deleteForm.delete(destroy.url(props.transaction.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteOpen.value = false;
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="bg-card w-full overflow-y-auto p-0 sm:max-w-xl">
            <div class="border-border border-b px-5 py-5 sm:px-7">
                <SheetHeader class="pr-8 text-left">
                    <SheetTitle class="text-xl font-extrabold tracking-tight">
                        {{
                            mode === 'create' || mode === 'copy'
                                ? t('finance.transactions.form.titleNew')
                                : mode === 'edit'
                                  ? t('finance.transactions.form.titleEdit')
                                  : transaction?.description
                        }}
                    </SheetTitle>
                    <SheetDescription>
                        {{
                            mode === 'detail' && transaction
                                ? t(
                                      `finance.transactions.type.${transaction.type}`,
                                  )
                                : t('finance.transactions.form.helper')
                        }}
                    </SheetDescription>
                </SheetHeader>
            </div>

            <div
                v-if="mode === 'detail' && transaction"
                class="grid gap-6 p-5 sm:p-7"
            >
                <div
                    class="rounded-3xl p-6"
                    :class="
                        transaction.settledAt ? 'bg-primary/10' : 'bg-muted'
                    "
                >
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <span
                                class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                                >{{
                                    t(
                                        `finance.transactions.status.${transaction.settledAt ? 'settled' : 'pending'}`,
                                    )
                                }}</span
                            >
                            <p
                                class="font-data mt-2 text-3xl font-medium"
                                :class="
                                    transaction.type === 'expense'
                                        ? 'text-expense'
                                        : transaction.type === 'income'
                                          ? 'text-income'
                                          : 'text-forecast'
                                "
                            >
                                {{
                                    transaction.type === 'expense'
                                        ? '−'
                                        : transaction.type === 'income'
                                          ? '+'
                                          : ''
                                }}{{ formatMoney(transaction.amountMinor) }}
                            </p>
                        </div>
                        <SettlementButton
                            :transaction="transaction"
                            :online="online"
                            @update="emit('transactionUpdate', $event)"
                        />
                    </div>
                </div>

                <dl class="grid gap-1">
                    <div
                        v-for="item in [
                            [
                                t('finance.transactions.detail.account'),
                                transaction.account.name,
                            ],
                            [
                                t(
                                    'finance.transactions.detail.destinationAccount',
                                ),
                                transaction.destinationAccount?.name,
                            ],
                            [
                                t('finance.transactions.detail.category'),
                                transaction.category?.name,
                            ],
                            [
                                t('finance.transactions.detail.date'),
                                formatDate(transaction.dueOn, {
                                    dateStyle: 'long',
                                }),
                            ],
                            [
                                t('finance.transactions.detail.notes'),
                                transaction.notes,
                            ],
                        ].filter((item) => item[1])"
                        :key="String(item[0])"
                        class="border-border/70 grid grid-cols-[8rem_1fr] gap-3 border-b py-3 last:border-0"
                    >
                        <dt
                            class="text-muted-foreground text-xs font-bold tracking-wider uppercase"
                        >
                            {{ item[0] }}
                        </dt>
                        <dd class="text-sm font-semibold">{{ item[1] }}</dd>
                    </div>
                </dl>

                <div
                    v-if="transaction.installmentNumber"
                    class="border-border rounded-2xl border p-4 text-sm"
                >
                    {{
                        t('finance.transactions.series.installmentProgress', {
                            current: transaction.installmentNumber,
                            total: transaction.installmentTotal,
                        })
                    }}
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <Button
                        variant="secondary"
                        class="h-auto flex-col gap-1 py-3"
                        @click="emit('update:mode', 'edit')"
                    >
                        <Pencil class="size-4" aria-hidden="true" />{{
                            t('common.edit')
                        }}
                    </Button>
                    <Button
                        variant="secondary"
                        class="h-auto flex-col gap-1 py-3"
                        @click="emit('update:mode', 'copy')"
                    >
                        <Copy class="size-4" aria-hidden="true" />{{
                            t('common.copy')
                        }}
                    </Button>
                    <ConfirmationDialog
                        v-if="!transaction.series"
                        v-model:open="deleteOpen"
                        :title="t('finance.transactions.detail.deleteTitle')"
                        :resource-name="transaction.description"
                        :description="
                            t('finance.transactions.detail.deleteConfirm', {
                                name: transaction.description,
                            })
                        "
                        :confirm-label="t('common.delete')"
                        :processing="deleteForm.processing"
                        :disabled="online === false || !networkOnline"
                        :error="Object.values(deleteForm.errors)[0]"
                        destructive
                        @confirm="remove"
                    >
                        <template #trigger>
                            <Button
                                variant="outline"
                                class="text-destructive h-auto flex-col gap-1 py-3"
                                :disabled="online === false || !networkOnline"
                                @click="deleteForm.clearErrors()"
                            >
                                <Trash2 class="size-4" aria-hidden="true" />{{
                                    t('common.delete')
                                }}
                            </Button>
                        </template>
                    </ConfirmationDialog>
                    <Button
                        v-else
                        variant="outline"
                        class="text-destructive h-auto flex-col gap-1 py-3"
                        :disabled="online === false || !networkOnline"
                        @click="
                            deleteForm.clearErrors();
                            deleteOpen = true;
                        "
                    >
                        <Trash2 class="size-4" aria-hidden="true" />{{
                            t('common.delete')
                        }}
                    </Button>
                </div>

                <TransactionScopeDialog
                    v-if="transaction.series"
                    v-model:open="deleteOpen"
                    action="delete"
                    :processing="deleteForm.processing"
                    :disabled="online === false || !networkOnline"
                    :error="Object.values(deleteForm.errors)[0]"
                    @select="remove"
                />
            </div>

            <div v-else class="p-5 sm:p-7">
                <TransactionForm
                    :transaction="transaction"
                    :force-create="mode === 'copy'"
                    :accounts="formAccounts"
                    :categories="categories"
                    :default-due-on="defaultDueOn"
                    :online="online"
                    @saved="emit('update:open', false)"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>
