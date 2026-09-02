<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Languages } from '@lucide/vue';
import { useI18n } from 'vue-i18n';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/locale';

const { t } = useI18n();
const page = usePage();
</script>

<template>
    <Head :title="t('settings.language.title')" />
    <section
        class="border-border/80 overflow-hidden rounded-3xl border bg-white"
    >
        <header
            class="border-border/70 flex items-center gap-4 border-b p-5 sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 place-items-center rounded-2xl"
                ><Languages class="size-5"
            /></span>
            <div>
                <h2 class="text-xl font-extrabold">
                    {{ t('settings.language.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('settings.language.description') }}
                </p>
            </div>
        </header>
        <Form
            v-bind="update.form()"
            v-slot="{ processing }"
            class="grid gap-5 p-5 sm:p-6"
        >
            <div class="grid max-w-md gap-2">
                <Label for="locale">{{ t('settings.language.label') }}</Label>
                <select
                    id="locale"
                    name="locale"
                    :value="page.props.locale"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                >
                    <option
                        v-for="locale in page.props.supportedLocales"
                        :key="locale.code"
                        :value="locale.code"
                    >
                        {{ locale.name }}
                    </option>
                </select>
            </div>
            <Button type="submit" class="w-fit" :disabled="processing">{{
                t('common.save')
            }}</Button>
        </Form>
    </section>
</template>
