<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChartNoAxesCombined,
    ListChecks,
    LogOut,
    Settings2,
    WifiOff,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Toaster } from '@/components/ui/sonner';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useOnline } from '@/composables/useOnline';
import { dashboard, logout } from '@/routes';
import { index as settings } from '@/routes/accounts';
import { index as transactions } from '@/routes/transactions';

const { t } = useI18n();
const page = usePage();
const { currentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const online = useOnline();
const workspaceName = computed(
    () => page.props.workspace?.name ?? t('common.appName'),
);
const navigation = computed(() => [
    {
        label: t('common.navigation.overview'),
        href: dashboard(),
        icon: ChartNoAxesCombined,
        section: 'overview',
    },
    {
        label: t('common.navigation.transactions'),
        href: transactions(),
        icon: ListChecks,
        section: 'transactions',
    },
    {
        label: t('common.navigation.settings'),
        href: settings(),
        icon: Settings2,
        section: 'settings',
    },
]);

function isNavigationItemActive(
    item: (typeof navigation.value)[number],
): boolean {
    return item.section === 'settings'
        ? currentUrl.value.startsWith('/settings')
        : isCurrentOrParentUrl(item.href);
}
</script>

<template>
    <div class="bg-background min-h-dvh pb-20 md:pb-0">
        <div
            v-if="!online"
            role="status"
            class="bg-foreground px-4 py-2 text-center text-sm text-white"
        >
            <WifiOff class="mr-2 inline size-4" aria-hidden="true" />
            {{ t('common.offline') }}
        </div>

        <header
            class="border-border/80 sticky top-0 z-30 border-b bg-white/95 backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center gap-8 px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="dashboard()"
                    class="flex min-w-0 items-center gap-2.5"
                    :aria-label="t('common.navigation.overview')"
                >
                    <span
                        class="bg-primary flex size-9 shrink-0 items-center justify-center rounded-xl text-white"
                    >
                        <AppLogoIcon class="size-6" />
                    </span>
                    <span class="min-w-0">
                        <span
                            class="block truncate text-sm font-extrabold tracking-tight"
                            >{{ t('common.appName') }}</span
                        >
                        <span
                            class="text-muted-foreground block truncate text-[11px]"
                            >{{ workspaceName }}</span
                        >
                    </span>
                </Link>

                <nav
                    class="hidden h-full items-center gap-1 md:flex"
                    :aria-label="t('common.navigation.primary')"
                >
                    <Link
                        v-for="item in navigation"
                        :key="item.label"
                        :href="item.href"
                        class="text-muted-foreground hover:text-foreground relative flex h-full items-center gap-2 px-3 text-sm font-semibold transition-colors"
                        :class="{
                            'text-foreground after:bg-primary after:absolute after:inset-x-3 after:bottom-0 after:h-0.5':
                                isNavigationItemActive(item),
                        }"
                    >
                        <component
                            :is="item.icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden text-right sm:block">
                        <span class="block text-xs font-semibold">{{
                            page.props.auth.user.name
                        }}</span>
                        <span class="text-muted-foreground block text-[11px]">{{
                            page.props.auth.user.email
                        }}</span>
                    </span>
                    <Link
                        :href="logout()"
                        method="post"
                        as="button"
                        class="bg-muted text-muted-foreground hover:text-foreground grid size-11 place-items-center rounded-full"
                        :aria-label="t('common.navigation.logout')"
                    >
                        <LogOut class="size-4" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </header>

        <main
            class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8"
        >
            <slot :online="online" />
        </main>

        <nav
            class="border-border fixed inset-x-0 bottom-0 z-30 grid grid-cols-3 border-t bg-white px-2 pb-[env(safe-area-inset-bottom)] md:hidden"
            :aria-label="t('common.navigation.primary')"
        >
            <Link
                v-for="item in navigation"
                :key="item.label"
                :href="item.href"
                class="text-muted-foreground flex min-h-16 flex-col items-center justify-center gap-1 text-[11px] font-semibold"
                :class="{ 'text-primary': isNavigationItemActive(item) }"
            >
                <component :is="item.icon" class="size-5" aria-hidden="true" />
                {{ item.label }}
            </Link>
        </nav>

        <Toaster position="top-right" rich-colors />
    </div>
</template>
