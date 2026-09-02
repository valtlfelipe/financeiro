<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const { t } = useI18n();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="bg-background flex min-h-svh flex-col items-center justify-center p-5 md:p-10"
    >
        <div
            class="border-border/80 bg-card w-full max-w-md rounded-3xl border p-6 shadow-[0_24px_70px_-40px_rgba(32,37,34,0.45)] sm:p-9"
        >
            <div class="flex flex-col gap-7">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <AppLogoIcon class="mb-1 size-12" />
                        <span class="text-sm font-extrabold tracking-tight">{{
                            t('common.appName')
                        }}</span>
                    </Link>
                    <div
                        v-if="title || description"
                        class="space-y-2 text-center"
                    >
                        <h1 class="text-2xl font-extrabold tracking-tight">
                            {{ title }}
                        </h1>
                        <p class="text-muted-foreground text-center text-sm">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
