<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        action: 'update' | 'delete';
        processing?: boolean;
        disabled?: boolean;
        error?: string;
    }>(),
    { processing: false, disabled: false },
);
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ select: [scope: 'single' | 'future'] }>();
const { t } = useI18n();
const selectedScope = ref<'single' | 'future' | null>(null);

watch(open, (isOpen) => {
    if (!isOpen) selectedScope.value = null;
});

function setOpen(value: boolean): void {
    if (!props.processing) open.value = value;
}

function select(scope: 'single' | 'future'): void {
    if (!props.processing && !props.disabled) {
        selectedScope.value = scope;
        emit('select', scope);
    }
}
</script>

<template>
    <AlertDialog :open="open" @update:open="setOpen">
        <AlertDialogContent
            class="bg-card text-card-foreground max-h-[calc(100dvh-2rem)] gap-5 overflow-y-auto rounded-3xl motion-reduce:animate-none sm:max-w-md"
            :aria-busy="processing"
            @escape-key-down="processing && $event.preventDefault()"
        >
            <AlertDialogHeader class="text-left">
                <AlertDialogTitle class="text-lg font-extrabold">
                    {{ t(`finance.transactions.scope.${action}.title`) }}
                </AlertDialogTitle>
                <AlertDialogDescription
                    class="text-muted-foreground text-sm leading-relaxed"
                >
                    {{ t(`finance.transactions.scope.${action}.description`) }}
                </AlertDialogDescription>
            </AlertDialogHeader>

            <div class="grid gap-3">
                <Button
                    type="button"
                    variant="outline"
                    class="h-auto min-h-16 justify-start px-4 py-3 text-left whitespace-normal"
                    :disabled="processing || disabled"
                    @click="select('single')"
                >
                    <Spinner
                        v-if="processing && selectedScope === 'single'"
                        aria-hidden="true"
                    />
                    <span class="grid gap-1">
                        <span class="font-bold">{{
                            t(
                                `finance.transactions.scope.${action}.singleAction`,
                            )
                        }}</span>
                        <span class="text-muted-foreground text-xs font-normal">
                            {{
                                t(
                                    `finance.transactions.scope.${action}.singleHint`,
                                )
                            }}
                        </span>
                    </span>
                </Button>
                <Button
                    type="button"
                    :variant="action === 'delete' ? 'destructive' : 'default'"
                    class="h-auto min-h-16 justify-start px-4 py-3 text-left whitespace-normal"
                    :disabled="processing || disabled"
                    @click="select('future')"
                >
                    <Spinner
                        v-if="processing && selectedScope === 'future'"
                        aria-hidden="true"
                    />
                    <span class="grid gap-1">
                        <span class="font-bold">{{
                            t(
                                `finance.transactions.scope.${action}.futureAction`,
                            )
                        }}</span>
                        <span
                            class="text-xs font-normal"
                            :class="
                                action === 'delete'
                                    ? 'text-destructive-foreground/80'
                                    : 'text-primary-foreground/80'
                            "
                        >
                            {{
                                t(
                                    `finance.transactions.scope.${action}.futureHint`,
                                )
                            }}
                        </span>
                    </span>
                </Button>
            </div>

            <p v-if="error" role="alert" class="text-destructive text-sm">
                {{ error }}
            </p>

            <AlertDialogFooter>
                <AlertDialogCancel class="mt-0 min-h-11" :disabled="processing">
                    {{ t('common.cancel') }}
                </AlertDialogCancel>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
