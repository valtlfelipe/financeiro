<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { accept } from '@/routes/invitations';

const props = defineProps<{
    token: string;
    email: string;
    workspaceName: string;
    existingUser: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <Head :title="t('auth.invitation.title')" />
    <div class="text-center">
        <h1 class="text-3xl font-extrabold tracking-tight">
            {{ t('auth.invitation.title') }}
        </h1>
        <p class="text-muted-foreground mt-3 text-sm leading-6">
            {{ t('auth.invitation.description', { workspace: workspaceName }) }}
        </p>
    </div>

    <Form
        v-bind="accept.form(token)"
        v-slot="{ errors, processing }"
        class="grid gap-5"
    >
        <div v-if="!existingUser" class="grid gap-2">
            <Label for="name">{{ t('auth.fields.name') }}</Label>
            <Input
                id="name"
                name="name"
                required
                autofocus
                autocomplete="name"
            />
            <InputError :message="errors.name" />
        </div>
        <div class="grid gap-2">
            <Label for="email">{{ t('auth.fields.email') }}</Label>
            <Input id="email" :model-value="email" type="email" disabled />
        </div>
        <div class="grid gap-2">
            <Label for="password">{{ t('auth.fields.password') }}</Label>
            <PasswordInput
                id="password"
                name="password"
                required
                :autocomplete="
                    existingUser ? 'current-password' : 'new-password'
                "
            />
            <InputError :message="errors.password" />
        </div>
        <div v-if="!existingUser" class="grid gap-2">
            <Label for="password_confirmation">{{
                t('auth.fields.passwordConfirmation')
            }}</Label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
            />
        </div>
        <Button type="submit" size="lg" :disabled="processing">
            <Spinner v-if="processing" />
            {{ t('auth.invitation.submit') }}
        </Button>
    </Form>
</template>
