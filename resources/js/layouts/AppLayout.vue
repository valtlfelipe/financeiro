<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChartNoAxesCombined,
    ChevronDown,
    ListChecks,
    LogOut,
    Settings2,
    UserRound,
    WifiOff,
} from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import UserAppearanceMenu from '@/components/UserAppearanceMenu.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { useAppearance } from '@/composables/useAppearance';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { useOnline } from '@/composables/useOnline';
import { dashboard, logout } from '@/routes';
import { index as settings } from '@/routes/accounts';
import { edit as profile } from '@/routes/profile';
import { index as transactions } from '@/routes/transactions';

const { t } = useI18n();
const page = usePage();
const { currentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const online = useOnline();
const { resolvedAppearance } = useAppearance();
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
            class="bg-foreground text-background px-4 py-2 text-center text-sm"
        >
            <WifiOff class="mr-2 inline size-4" aria-hidden="true" />
            {{ t('common.offline') }}
        </div>

        <header
            class="border-border/80 bg-card/95 sticky top-0 z-30 border-b backdrop-blur"
        >
            <div
                class="mx-auto flex h-16 max-w-7xl items-center gap-8 px-4 sm:px-6 lg:px-8"
            >
                <Link
                    :href="dashboard()"
                    class="flex min-w-0 items-center gap-2.5"
                    :aria-label="t('common.navigation.overview')"
                >
                    <AppLogoIcon class="size-9" />
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

                <div class="ml-auto shrink-0">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button
                                type="button"
                                class="hover:bg-muted flex min-h-11 items-center gap-2 rounded-full p-1 pr-2 transition-colors"
                                :aria-label="t('common.navigation.userMenu')"
                            >
                                <span
                                    class="bg-primary/10 text-primary grid size-9 place-items-center rounded-full text-xs font-extrabold"
                                    aria-hidden="true"
                                >
                                    {{ getInitials(page.props.auth.user.name) }}
                                </span>
                                <span
                                    class="hidden max-w-32 truncate text-sm font-semibold lg:block"
                                    >{{ page.props.auth.user.name }}</span
                                >
                                <ChevronDown
                                    class="text-muted-foreground size-4"
                                    aria-hidden="true"
                                />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent
                            align="end"
                            class="w-64 max-w-[calc(100vw-2rem)] rounded-2xl p-2"
                            :side-offset="8"
                        >
                            <DropdownMenuLabel class="px-3 py-2">
                                <p class="truncate text-sm font-bold">
                                    {{ page.props.auth.user.name }}
                                </p>
                                <p
                                    class="text-muted-foreground mt-1 truncate text-xs font-normal"
                                >
                                    {{ page.props.auth.user.email }}
                                </p>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                as-child
                                class="min-h-11 rounded-xl px-3"
                            >
                                <Link :href="profile()">
                                    <UserRound
                                        class="size-4"
                                        aria-hidden="true"
                                    />{{ t('common.navigation.profile') }}
                                </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <UserAppearanceMenu />
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                as-child
                                class="min-h-11 rounded-xl px-3"
                            >
                                <Link
                                    :href="logout()"
                                    method="post"
                                    as="button"
                                    class="w-full"
                                >
                                    <LogOut
                                        class="size-4"
                                        aria-hidden="true"
                                    />{{ t('common.navigation.logout') }}
                                </Link>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </header>

        <main
            class="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-8"
        >
            <slot :online="online" />
        </main>

        <nav
            class="border-border bg-card fixed inset-x-0 bottom-0 z-30 grid grid-cols-3 border-t px-2 pb-[env(safe-area-inset-bottom)] md:hidden"
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

        <Toaster position="top-right" rich-colors :theme="resolvedAppearance" />
    </div>
</template>
