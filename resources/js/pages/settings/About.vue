<script setup lang="ts">
import { Head, useHttp } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CircleCheck,
    CircleHelp,
    Code,
    Heart,
    LoaderCircle,
    RefreshCw,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { PRODUCT_NAME } from '@/lib/product';
import { updates } from '@/routes/about';

defineProps<{
    project: {
        version: string;
        author: string;
        repository: string;
        sponsors: string;
        license: string;
    };
}>();

type UpdateResult = {
    status: 'available' | 'current' | 'development' | 'unavailable';
    latestVersion: string | null;
    releaseUrl: string | null;
    checkedAt: string;
};

const { t, locale } = useI18n();
const request = useHttp<Record<string, never>, UpdateResult>({});
const failed = ref(false);
let mounted = true;
const status = computed(() =>
    failed.value ? 'unavailable' : request.response?.status,
);
const checking = computed(
    () => request.processing || (!status.value && !failed.value),
);
const checkedAt = computed(() =>
    request.response && !failed.value
        ? new Intl.DateTimeFormat(locale.value, {
              dateStyle: 'short',
              timeStyle: 'short',
          }).format(new Date(request.response.checkedAt))
        : null,
);

async function checkForUpdates(): Promise<void> {
    if (request.processing) return;
    failed.value = false;

    try {
        await request.get(updates.url());
    } catch {
        if (mounted) failed.value = true;
    }
}

onMounted(checkForUpdates);
onUnmounted(() => {
    mounted = false;
    request.cancel();
});
</script>

<template>
    <section
        class="border-border/80 bg-card overflow-hidden rounded-3xl border"
    >
        <Head :title="t('settings.sections.about')" />

        <header class="border-border/70 border-b p-6 sm:p-8">
            <div class="flex items-center gap-5">
                <AppLogoIcon class="size-16 sm:size-20" />
                <div>
                    <p class="text-muted-foreground text-xs font-bold">
                        {{ t('settings.sections.about') }}
                    </p>
                    <h2
                        class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl"
                    >
                        {{ PRODUCT_NAME }}
                    </h2>
                </div>
            </div>
            <p
                class="text-muted-foreground mt-5 max-w-xl text-sm leading-relaxed sm:text-base"
            >
                {{ t('settings.about.description') }}
            </p>
        </header>

        <div class="border-border/70 grid gap-5 border-b p-6 sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-bold">
                    {{ t('settings.about.installedVersion') }}
                </h3>
                <span class="bg-muted font-data rounded-lg px-3 py-1.5 text-sm">
                    {{
                        project.version === 'dev'
                            ? t('settings.about.developmentVersion')
                            : project.version
                    }}
                </span>
            </div>

            <div class="bg-muted/60 rounded-2xl p-4 sm:p-5">
                <div
                    role="status"
                    aria-live="polite"
                    :aria-busy="checking"
                    class="flex items-start gap-3"
                >
                    <LoaderCircle
                        v-if="checking"
                        class="text-muted-foreground mt-0.5 size-5 shrink-0 motion-safe:animate-spin"
                        aria-hidden="true"
                    />
                    <ArrowUpRight
                        v-else-if="status === 'available'"
                        class="text-primary mt-0.5 size-5 shrink-0"
                        aria-hidden="true"
                    />
                    <CircleCheck
                        v-else-if="status === 'current'"
                        class="text-primary mt-0.5 size-5 shrink-0"
                        aria-hidden="true"
                    />
                    <CircleHelp
                        v-else
                        class="text-muted-foreground mt-0.5 size-5 shrink-0"
                        aria-hidden="true"
                    />
                    <div>
                        <p class="text-sm font-bold">
                            {{
                                checking
                                    ? t('settings.about.checking')
                                    : t(`settings.about.status.${status}`, {
                                          version:
                                              request.response?.latestVersion,
                                      })
                            }}
                        </p>
                        <p
                            class="text-muted-foreground mt-1 text-sm leading-relaxed"
                        >
                            {{
                                checking
                                    ? t('settings.about.checkingHint')
                                    : t(`settings.about.hint.${status}`)
                            }}
                        </p>
                        <p
                            v-if="checkedAt && !checking"
                            class="text-muted-foreground mt-2 text-xs"
                        >
                            {{
                                t('settings.about.checkedAt', {
                                    date: checkedAt,
                                })
                            }}
                        </p>
                    </div>
                </div>
                <div v-if="!checking" class="mt-4 flex flex-wrap gap-2">
                    <Button
                        v-if="
                            status === 'available' &&
                            request.response?.releaseUrl
                        "
                        as-child
                        class="min-h-11"
                    >
                        <a
                            :href="request.response.releaseUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ t('settings.about.viewRelease')
                            }}<ArrowUpRight class="size-4" aria-hidden="true" />
                        </a>
                    </Button>
                    <Button
                        variant="outline"
                        class="min-h-11"
                        @click="checkForUpdates"
                    >
                        <RefreshCw class="size-4" aria-hidden="true" />{{
                            t('settings.about.checkAgain')
                        }}
                    </Button>
                </div>
            </div>
        </div>

        <dl
            class="border-border/70 grid gap-5 border-b p-6 sm:grid-cols-2 sm:p-8"
        >
            <div>
                <dt class="text-muted-foreground text-xs">
                    {{ t('settings.about.developedBy') }}
                </dt>
                <dd class="mt-1.5 text-sm font-bold">{{ project.author }}</dd>
            </div>
            <div>
                <dt class="text-muted-foreground text-xs">
                    {{ t('settings.about.license') }}
                </dt>
                <dd class="mt-1.5 text-sm font-bold">
                    <a
                        :href="`${project.repository}/blob/main/LICENSE`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="hover:text-primary inline-flex items-center gap-1 underline decoration-current/30 underline-offset-4"
                    >
                        {{ project.license
                        }}<ArrowUpRight class="size-3.5" aria-hidden="true" />
                    </a>
                </dd>
            </div>
        </dl>

        <footer class="p-6 sm:p-8">
            <h3 class="text-sm font-bold">
                {{ t('settings.about.supportTitle') }}
            </h3>
            <p
                class="text-muted-foreground mt-2 max-w-xl text-sm leading-relaxed"
            >
                {{ t('settings.about.supportDescription') }}
            </p>
            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <Button as-child class="min-h-11">
                    <a
                        :href="project.sponsors"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Heart class="size-4" aria-hidden="true" />{{
                            t('settings.about.support')
                        }}<ArrowUpRight class="size-4" aria-hidden="true" />
                    </a>
                </Button>
                <Button as-child variant="outline" class="min-h-11">
                    <a
                        :href="project.repository"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Code class="size-4" aria-hidden="true" />{{
                            t('settings.about.repository')
                        }}<ArrowUpRight class="size-4" aria-hidden="true" />
                    </a>
                </Button>
            </div>
        </footer>
    </section>
</template>
