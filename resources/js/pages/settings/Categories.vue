<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Archive, Plus, Tags } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, store } from '@/routes/categories';
import type { Category } from '@/types';

defineProps<{ categories: Category[] }>();
const { t } = useI18n();
const showForm = ref(false);
const form = useForm({
    name: '',
    type: 'expense',
    color: '#C84D57',
    icon: 'tag',
});
function submit(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showForm.value = false;
        },
    });
}
</script>

<template>
    <Head :title="t('finance.categories.title')" />
    <section
        class="border-border/80 overflow-hidden rounded-3xl border bg-white"
    >
        <header
            class="border-border/70 flex items-center gap-4 border-b p-5 sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 place-items-center rounded-2xl"
                ><Tags class="size-5"
            /></span>
            <div class="mr-auto">
                <h2 class="text-xl font-extrabold">
                    {{ t('finance.categories.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('finance.categories.description') }}
                </p>
            </div>
            <Button class="gap-2" @click="showForm = !showForm"
                ><Plus class="size-4" />{{
                    t('finance.categories.new')
                }}</Button
            >
        </header>
        <form
            v-if="showForm"
            class="border-border bg-muted/50 grid gap-4 border-b p-5 sm:grid-cols-2 sm:p-6"
            @submit.prevent="submit"
        >
            <div class="grid gap-2">
                <Label for="category_name">{{
                    t('finance.categories.name')
                }}</Label
                ><Input
                    id="category_name"
                    v-model="form.name"
                    required
                /><InputError :message="form.errors.name" />
            </div>
            <div class="grid gap-2">
                <Label for="category_type">{{
                    t('finance.categories.type')
                }}</Label
                ><select
                    id="category_type"
                    v-model="form.type"
                    class="border-input h-11 rounded-xl border bg-white px-3 text-sm"
                >
                    <option
                        v-for="type in ['income', 'expense', 'both']"
                        :key="type"
                        :value="type"
                    >
                        {{ t(`finance.categories.types.${type}`) }}
                    </option>
                </select>
            </div>
            <div class="grid gap-2">
                <Label for="category_color">{{
                    t('finance.categories.color')
                }}</Label
                ><Input
                    id="category_color"
                    v-model="form.color"
                    type="color"
                    class="h-11 p-1"
                />
            </div>
            <div class="flex items-end">
                <Button
                    type="submit"
                    class="w-full"
                    :disabled="form.processing"
                    >{{ t('common.save') }}</Button
                >
            </div>
        </form>
        <div v-if="categories.length" class="divide-border/70 divide-y">
            <article
                v-for="category in categories"
                :key="category.id"
                class="flex items-center gap-4 px-5 py-4 sm:px-6"
            >
                <span
                    class="grid size-10 place-items-center rounded-2xl text-sm font-extrabold text-white"
                    :style="{ backgroundColor: category.color }"
                    >{{ category.name.slice(0, 1).toUpperCase() }}</span
                >
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">
                        {{ category.name }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        {{ t(`finance.categories.types.${category.type}`) }}
                    </p>
                </div>
                <Button
                    v-if="!category.isArchived"
                    variant="ghost"
                    size="icon"
                    :aria-label="t('finance.categories.archive')"
                    @click="$inertia.delete(destroy.url(category.id))"
                    ><Archive class="size-4"
                /></Button>
                <span
                    v-else
                    class="bg-muted text-muted-foreground rounded-full px-3 py-1 text-xs font-bold"
                    >{{ t('common.archived') }}</span
                >
            </article>
        </div>
        <p v-else class="text-muted-foreground p-10 text-center text-sm">
            {{ t('common.empty') }}
        </p>
    </section>
</template>
