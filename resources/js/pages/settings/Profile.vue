<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { UserRound } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useOnline } from '@/composables/useOnline';
import { update } from '@/routes/profile';

const { t } = useI18n();
const page = usePage();
const online = useOnline();
const form = useForm({
    name: page.props.auth.user.name,
    email: page.props.auth.user.email,
});

function submit(): void {
    if (!online.value || form.processing) return;
    form.patch(update.url(), { preserveScroll: true });
}
</script>

<template>
    <section
        class="border-border/80 bg-card overflow-hidden rounded-3xl border"
    >
        <Head :title="t('settings.profile.title')" />
        <header
            class="border-border/70 flex items-center gap-4 border-b p-5 sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 shrink-0 place-items-center rounded-2xl"
            >
                <UserRound class="size-5" aria-hidden="true" />
            </span>
            <div>
                <h2 class="text-xl font-extrabold">
                    {{ t('settings.profile.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('settings.profile.description') }}
                </p>
            </div>
        </header>
        <form class="grid max-w-xl gap-5 p-5 sm:p-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="profile_name">{{
                    t('settings.profile.name')
                }}</Label>
                <Input
                    id="profile_name"
                    v-model="form.name"
                    autocomplete="name"
                    required
                    maxlength="255"
                    :aria-invalid="!!form.errors.name"
                    aria-describedby="profile_name_error"
                />
                <InputError
                    id="profile_name_error"
                    :message="form.errors.name"
                />
            </div>
            <div class="grid gap-2">
                <Label for="profile_email">{{
                    t('settings.profile.email')
                }}</Label>
                <Input
                    id="profile_email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    required
                    maxlength="255"
                    :aria-invalid="!!form.errors.email"
                    aria-describedby="profile_email_error"
                />
                <InputError
                    id="profile_email_error"
                    :message="form.errors.email"
                />
            </div>
            <Button
                type="submit"
                class="min-h-11 w-fit"
                :disabled="form.processing || !online"
            >
                {{
                    form.processing
                        ? t('common.saving')
                        : t('settings.profile.save')
                }}
            </Button>
        </form>
    </section>
</template>
