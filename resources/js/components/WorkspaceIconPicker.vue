<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import {
    workspaceIcon,
    workspaceIconNames,
    type WorkspaceIconName,
} from '@/lib/workspace-icons';

withDefaults(
    defineProps<{
        labelId?: string;
        disabled?: boolean;
    }>(),
    { disabled: false },
);
const selected = defineModel<WorkspaceIconName>({ required: true });
const { t } = useI18n();
</script>

<template>
    <div>
        <input type="hidden" name="icon" :value="selected" />
        <div
            class="grid grid-cols-4 gap-2 sm:grid-cols-8"
            role="radiogroup"
            :aria-labelledby="labelId"
        >
            <button
                v-for="icon in workspaceIconNames"
                :key="icon"
                type="button"
                role="radio"
                class="hover:border-primary/50 hover:bg-primary/5 focus-visible:ring-ring grid size-11 place-items-center rounded-xl border transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                :class="
                    selected === icon
                        ? 'border-primary bg-primary/10 text-primary'
                        : 'border-border bg-card text-muted-foreground'
                "
                :aria-checked="selected === icon"
                :aria-label="t(`common.workspace.icons.${icon}`)"
                :title="t(`common.workspace.icons.${icon}`)"
                :disabled="disabled"
                @click="selected = icon"
            >
                <component :is="workspaceIcon(icon)" class="size-5" />
            </button>
        </div>
    </div>
</template>
