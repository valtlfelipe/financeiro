<script setup lang="ts">
import { ThumbsDown, ThumbsUp } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { toast } from 'vue-sonner';
import { settlement } from '@/routes/transactions';
import { useOnline } from '@/composables/useOnline';
import type { MonthlySummary, Transaction } from '@/types';
import {
    beginWorkspaceRequest,
    finishWorkspaceRequest,
    workspaceHeaders,
} from '@/lib/workspaceContext';

const props = withDefaults(
    defineProps<{
        transaction: Transaction;
        online?: boolean;
    }>(),
    {
        online: true,
    },
);

const emit = defineEmits<{
    update: [transaction: Transaction, summary: MonthlySummary];
}>();

const { t } = useI18n();
const pending = ref(false);
const networkOnline = useOnline();
const settled = computed(() => props.transaction.settledAt !== null);
const actionLabel = computed(() => {
    if (settled.value) return t('finance.transactions.status.markPending');
    return props.transaction.type === 'income'
        ? t('finance.transactions.status.markIncomeSettled')
        : t('finance.transactions.status.markExpenseSettled');
});

function csrfToken(): string {
    const raw = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];
    return raw ? decodeURIComponent(raw) : '';
}

async function persist(nextSettled: boolean, showUndo: boolean): Promise<void> {
    if (pending.value || props.online === false || !networkOnline.value) return;

    pending.value = true;
    beginWorkspaceRequest();
    const previous = props.transaction;
    emit(
        'update',
        {
            ...previous,
            settledAt: nextSettled ? new Date().toISOString() : null,
        },
        {} as MonthlySummary,
    );

    try {
        const response = await fetch(settlement.url(previous.id), {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
                ...workspaceHeaders(),
            },
            body: JSON.stringify({ settled: nextSettled }),
        });

        if (!response.ok) throw new Error(String(response.status));
        const data = (await response.json()) as {
            transaction: Transaction;
            summary: MonthlySummary;
        };
        emit('update', data.transaction, data.summary);
        pending.value = false;

        if (showUndo) {
            toast.success(
                nextSettled
                    ? t('finance.transactions.toast.settled')
                    : t('finance.transactions.toast.pending'),
                {
                    action: {
                        label: t('common.undo'),
                        onClick: () => void persist(!nextSettled, false),
                    },
                },
            );
        }
    } catch {
        emit('update', previous, {} as MonthlySummary);
        toast.error(t('common.error'));
    } finally {
        pending.value = false;
        finishWorkspaceRequest();
    }
}
</script>

<template>
    <button
        type="button"
        class="grid size-11 shrink-0 place-items-center rounded-full transition-all disabled:cursor-not-allowed disabled:opacity-50"
        :class="
            settled
                ? 'bg-primary/12 text-primary hover:bg-primary/20'
                : 'bg-muted text-pending hover:bg-border'
        "
        :aria-label="actionLabel"
        :title="actionLabel"
        :disabled="pending || online === false || !networkOnline"
        @click.stop="persist(!settled, true)"
    >
        <ThumbsUp v-if="settled" class="size-5" aria-hidden="true" />
        <ThumbsDown v-else class="size-5" aria-hidden="true" />
    </button>
</template>
