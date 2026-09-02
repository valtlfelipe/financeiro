<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Settings2, Tags, UserRound, Users, WalletCards } from '@lucide/vue';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { index as accounts } from '@/routes/accounts';
import { index as categories } from '@/routes/categories';
import { index as members } from '@/routes/invitations';
import { edit as preferences } from '@/routes/preferences';
import { edit as profile } from '@/routes/profile';

const { t } = useI18n();
const page = usePage();
const { isCurrentUrl } = useCurrentUrl();
const navigation = computed(() =>
    [
        {
            label: t('settings.sections.profile'),
            href: profile(),
            icon: UserRound,
            visible: true,
        },
        {
            label: t('settings.sections.accounts'),
            href: accounts(),
            icon: WalletCards,
            visible: true,
        },
        {
            label: t('settings.sections.categories'),
            href: categories(),
            icon: Tags,
            visible: true,
        },
        {
            label: t('settings.sections.members'),
            href: members(),
            icon: Users,
            visible: page.props.workspace?.role === 'owner',
        },
        {
            label: t('settings.sections.preferences'),
            href: preferences(),
            icon: Settings2,
            visible: true,
        },
    ].filter((item) => item.visible),
);
</script>

<template>
    <section class="grid gap-6">
        <header>
            <h1 class="text-3xl font-extrabold tracking-tight">
                {{ t('settings.title') }}
            </h1>
            <p class="text-muted-foreground mt-2 text-sm">
                {{ t('settings.description') }}
            </p>
        </header>

        <div class="grid gap-6 lg:grid-cols-[13rem_minmax(0,1fr)]">
            <nav
                class="flex gap-2 overflow-x-auto pb-1 lg:flex-col"
                :aria-label="t('settings.navigationLabel')"
            >
                <Link
                    v-for="item in navigation"
                    :key="item.label"
                    :href="item.href"
                    class="text-muted-foreground hover:text-foreground hover:bg-card flex min-h-11 shrink-0 items-center gap-2 rounded-xl px-3 text-sm font-bold transition-colors"
                    :class="{
                        'text-primary bg-card shadow-sm': isCurrentUrl(
                            item.href,
                        ),
                    }"
                >
                    <component
                        :is="item.icon"
                        class="size-4"
                        aria-hidden="true"
                    />{{ item.label }}
                </Link>
            </nav>
            <div class="min-w-0"><slot /></div>
        </div>
    </section>
</template>
