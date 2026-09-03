<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import WorkspaceIconPicker from '@/components/WorkspaceIconPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/setup';
import type { WorkspaceIconName } from '@/lib/workspace-icons';

const { t } = useI18n();
const workspaceIcon = ref<WorkspaceIconName>('house');
</script>

<template>
    <Head :title="t('auth.setup.eyebrow')" />

    <div class="text-center">
        <p class="text-primary text-xs font-bold tracking-[0.2em] uppercase">
            {{ t('auth.setup.eyebrow') }}
        </p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight">
            {{ t('auth.setup.title') }}
        </h1>
        <p class="text-muted-foreground mt-3 text-sm leading-6">
            {{ t('auth.setup.description') }}
        </p>
    </div>

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="grid gap-5"
    >
        <div class="grid gap-2">
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
            <Label for="workspace_name">{{ t('auth.setup.workspace') }}</Label>
            <Input
                id="workspace_name"
                name="workspace_name"
                required
                :placeholder="t('auth.setup.workspacePlaceholder')"
            />
            <InputError :message="errors.workspace_name" />
        </div>
        <div class="grid gap-2">
            <Label id="setup_workspace_icon_label">{{
                t('common.workspace.icon')
            }}</Label>
            <WorkspaceIconPicker
                v-model="workspaceIcon"
                label-id="setup_workspace_icon_label"
                :disabled="processing"
            />
            <InputError :message="errors.icon" />
        </div>
        <div class="grid gap-2">
            <Label for="email">{{ t('auth.fields.email') }}</Label>
            <Input
                id="email"
                name="email"
                type="email"
                required
                autocomplete="email"
                :placeholder="t('auth.fields.emailPlaceholder')"
            />
            <InputError :message="errors.email" />
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="password">{{ t('auth.fields.password') }}</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                />
                <InputError :message="errors.password" />
            </div>
            <div class="grid gap-2">
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
        </div>
        <Button
            type="submit"
            size="lg"
            class="mt-2 w-full"
            :disabled="processing"
        >
            <Spinner v-if="processing" />
            {{ t('auth.setup.submit') }}
        </Button>
    </Form>
</template>
