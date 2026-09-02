<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { Input } from '@/components/ui/input';
import {
    formatMoneyInputOnBlur,
    formatMoneyInputWithCaret,
} from '@/lib/money-input';

const modelValue = defineModel<string>({ required: true });
const page = usePage();
const selectOnMouseUp = ref(false);

async function handleAmountInput(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const formatted = formatMoneyInputWithCaret(
        input.value,
        input.value.length,
        page.props.locale,
    );

    input.value = formatted.value;
    modelValue.value = formatted.value;
    selectOnMouseUp.value = false;
    await nextTick();

    if (input.ownerDocument.activeElement === input) {
        input.setSelectionRange(formatted.caret, formatted.caret);
    }
}

async function handleFocus(event: FocusEvent): Promise<void> {
    const input = event.target as HTMLInputElement;

    selectOnMouseUp.value = true;
    await nextTick();
    input.select();
}

function handleMouseUp(event: MouseEvent): void {
    if (!selectOnMouseUp.value) {
        return;
    }

    const input = event.target as HTMLInputElement;

    selectOnMouseUp.value = false;
    event.preventDefault();
    input.select();
}

function handleBlur(): void {
    selectOnMouseUp.value = false;
    modelValue.value = formatMoneyInputOnBlur(
        modelValue.value,
        page.props.locale,
    );
}
</script>

<template>
    <Input
        :model-value="modelValue"
        inputmode="numeric"
        autocomplete="off"
        spellcheck="false"
        @blur="handleBlur"
        @focus="handleFocus"
        @input.capture="handleAmountInput"
        @mouseup="handleMouseUp"
    />
</template>
