<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { Archive, Plus, Tags, X } from '@lucide/vue';
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useOnline } from '@/composables/useOnline';
import { randomColor } from '@/lib/colors';
import { destroy, store } from '@/routes/categories';
import type { Category } from '@/types';

defineProps<{ categories: Category[] }>();
const { t } = useI18n();
const showForm = ref(false);
const online = useOnline();
const archiveForm = useForm({});

function archive(category: Category): void {
    if (archiveForm.processing || !online.value) return;
    archiveForm.delete(destroy.url(category.id), { preserveScroll: true });
}
const form = useForm({
    name: '',
    type: 'expense',
    color: '#C84D57',
    icon: 'tag',
});
watch(showForm, (open) => {
    if (open) form.color = randomColor(form.color);
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
    <section
        class="border-border/80 overflow-hidden rounded-3xl border bg-white"
    >
        <Head :title="t('finance.categories.title')" />
        <header
            class="border-border/70 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-4 border-b p-5 sm:flex sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 shrink-0 place-items-center rounded-2xl"
                ><Tags class="size-5"
            /></span>
            <div class="mr-auto min-w-0">
                <h2 class="text-xl font-extrabold">
                    {{ t('finance.categories.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('finance.categories.description') }}
                </p>
            </div>
            <Button
                class="col-span-2 min-h-11 gap-2"
                :variant="showForm ? 'outline' : 'default'"
                :aria-expanded="showForm"
                aria-controls="category_form"
                @click="showForm = !showForm"
                ><X v-if="showForm" class="size-4" aria-hidden="true" /><Plus
                    v-else
                    class="size-4"
                    aria-hidden="true"
                />{{
                    showForm ? t('common.cancel') : t('finance.categories.new')
                }}</Button
            >
        </header>
        <form
            v-if="showForm"
            id="category_form"
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
                <ConfirmationDialog
                    v-if="!category.isArchived"
                    :title="t('finance.categories.archiveTitle')"
                    :resource-name="category.name"
                    :description="
                        t('finance.categories.archiveDescription', {
                            name: category.name,
                        })
                    "
                    :confirm-label="t('finance.categories.archiveAction')"
                    :processing="archiveForm.processing"
                    :disabled="!online"
                    :error="
                        Object.values(archiveForm.errors).find(
                            (message): message is string =>
                                typeof message === 'string',
                        )
                    "
                    @confirm="archive(category)"
                >
                    <template #trigger>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="size-11 shrink-0"
                            :disabled="!online || archiveForm.processing"
                            :aria-label="
                                t('finance.categories.archiveLabel', {
                                    name: category.name,
                                })
                            "
                            @click="archiveForm.clearErrors()"
                        >
                            <Archive class="size-4" aria-hidden="true" />
                        </Button>
                    </template>
                </ConfirmationDialog>
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
