<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('auth.confirm.title')" />
    <div class="text-center">
        <h1 class="text-2xl font-extrabold">{{ t('auth.confirm.title') }}</h1>
        <p class="text-muted-foreground mt-2 text-sm">
            {{ t('auth.confirm.description') }}
        </p>
    </div>
    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
        class="grid gap-5"
    >
        <div class="grid gap-2">
            <Label for="password">{{ t('auth.fields.password') }}</Label
            ><PasswordInput
                id="password"
                name="password"
                required
                autofocus
                autocomplete="current-password"
            /><InputError :message="errors.password" />
        </div>
        <Button type="submit" :disabled="processing"
            ><Spinner v-if="processing" />{{ t('common.continue') }}</Button
        >
    </Form>
</template>
