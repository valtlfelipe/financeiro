<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        confirmLabel: string;
        processing?: boolean;
        disabled?: boolean;
        destructive?: boolean;
        error?: string;
    }>(),
    { processing: false, disabled: false, destructive: false },
);
const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ confirm: [] }>();
const { t } = useI18n();

function setOpen(value: boolean): void {
    if (!props.processing) open.value = value;
}

function confirm(): void {
    if (!props.processing && !props.disabled) emit('confirm');
}
</script>

<template>
    <AlertDialog :open="open" @update:open="setOpen">
        <AlertDialogTrigger as-child
            ><slot name="trigger"
        /></AlertDialogTrigger>
        <AlertDialogContent
            class="bg-card text-card-foreground max-h-[calc(100dvh-2rem)] gap-5 overflow-y-auto rounded-3xl motion-reduce:animate-none sm:max-w-md"
            :aria-busy="processing"
            @escape-key-down="processing && $event.preventDefault()"
        >
            <AlertDialogHeader class="text-left">
                <AlertDialogTitle class="text-lg font-extrabold break-words">{{
                    title
                }}</AlertDialogTitle>
                <AlertDialogDescription
                    class="text-muted-foreground text-sm leading-relaxed break-words"
                    >{{ description }}</AlertDialogDescription
                >
            </AlertDialogHeader>
            <slot />
            <p v-if="error" role="alert" class="text-destructive text-sm">
                {{ error }}
            </p>
            <AlertDialogFooter>
                <AlertDialogCancel
                    class="mt-0 min-h-11"
                    :disabled="processing"
                    >{{ t('common.cancel') }}</AlertDialogCancel
                >
                <Button
                    :variant="destructive ? 'destructive' : 'default'"
                    class="min-h-11"
                    :disabled="processing || disabled"
                    @click="confirm"
                >
                    <Spinner v-if="processing" aria-hidden="true" />
                    {{ processing ? t('common.processing') : confirmLabel }}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
