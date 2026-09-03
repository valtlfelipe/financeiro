<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import { nextTick, useTemplateRef } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useOnline } from '@/composables/useOnline';
import { update } from '@/routes/user-password';

defineProps<{ passwordRules: string }>();

const { t } = useI18n();
const online = useOnline();
const currentPasswordInput = useTemplateRef('currentPasswordInput');
const newPasswordInput = useTemplateRef('newPasswordInput');
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submit(): void {
    if (!online.value || form.processing) return;

    form.put(update.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: async (errors) => {
            if (errors.current_password) {
                form.reset('current_password');
                await nextTick();
                currentPasswordInput.value?.focus();
            } else if (errors.password) {
                form.reset('password', 'password_confirmation');
                await nextTick();
                newPasswordInput.value?.focus();
            }
        },
    });
}
</script>

<template>
    <section
        class="border-border/80 bg-card overflow-hidden rounded-3xl border"
        aria-labelledby="password_title"
    >
        <header
            class="border-border/70 flex items-center gap-4 border-b p-5 sm:p-6"
        >
            <div
                class="bg-primary/10 text-primary flex size-12 shrink-0 items-center justify-center rounded-2xl"
            >
                <LockKeyhole class="size-5" aria-hidden="true" />
            </div>
            <div>
                <h2 id="password_title" class="text-xl font-extrabold">
                    {{ t('settings.password.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('settings.password.description') }}
                </p>
            </div>
        </header>
        <form class="grid max-w-xl gap-5 p-5 sm:p-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="current_password">{{
                    t('settings.password.current')
                }}</Label>
                <PasswordInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    name="current_password"
                    autocomplete="current-password"
                    required
                    :aria-invalid="!!form.errors.current_password"
                    aria-describedby="current_password_error"
                />
                <InputError
                    id="current_password_error"
                    :message="form.errors.current_password"
                />
            </div>
            <div class="grid gap-2">
                <Label for="new_password">{{
                    t('settings.password.new')
                }}</Label>
                <PasswordInput
                    id="new_password"
                    ref="newPasswordInput"
                    v-model="form.password"
                    name="password"
                    autocomplete="new-password"
                    :passwordrules="passwordRules"
                    required
                    :aria-invalid="!!form.errors.password"
                    aria-describedby="new_password_hint new_password_error"
                />
                <p
                    id="new_password_hint"
                    class="text-muted-foreground text-xs leading-relaxed"
                >
                    {{ t('settings.password.hint') }}
                </p>
                <InputError
                    id="new_password_error"
                    :message="form.errors.password"
                />
            </div>
            <div class="grid gap-2">
                <Label for="password_confirmation">{{
                    t('settings.password.confirm')
                }}</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    required
                    :aria-invalid="!!form.errors.password_confirmation"
                    aria-describedby="password_confirmation_error"
                />
                <InputError
                    id="password_confirmation_error"
                    :message="form.errors.password_confirmation"
                />
            </div>
            <Button
                type="submit"
                class="min-h-11 w-fit"
                :disabled="form.processing || !online"
            >
                <Spinner v-if="form.processing" />
                {{
                    form.processing
                        ? t('common.saving')
                        : t('settings.password.save')
                }}
            </Button>
        </form>
    </section>
</template>
