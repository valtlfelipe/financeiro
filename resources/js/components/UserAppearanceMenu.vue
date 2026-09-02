<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import {
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';

const { t } = useI18n();
const { appearance, updateAppearance } = useAppearance();
const options = [
    { value: 'light', icon: Sun },
    { value: 'dark', icon: Moon },
    { value: 'system', icon: Monitor },
] as const;
</script>

<template>
    <div>
        <DropdownMenuLabel
            class="text-muted-foreground px-3 pt-2 text-xs font-medium"
        >
            {{ t('common.appearance.title') }}
        </DropdownMenuLabel>
        <DropdownMenuRadioGroup
            :model-value="appearance"
            :aria-label="t('common.appearance.title')"
            @update:model-value="updateAppearance"
        >
            <DropdownMenuRadioItem
                v-for="option in options"
                :key="option.value"
                :value="option.value"
                class="min-h-11 rounded-xl"
            >
                <component
                    :is="option.icon"
                    class="size-4"
                    aria-hidden="true"
                />
                {{ t(`common.appearance.${option.value}`) }}
            </DropdownMenuRadioItem>
        </DropdownMenuRadioGroup>
    </div>
</template>
