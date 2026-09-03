<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Settings2, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import WorkspaceIconPicker from '@/components/WorkspaceIconPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useOnline } from '@/composables/useOnline';
import { update as updateLocale } from '@/routes/locale';
import { update as updatePreferences } from '@/routes/preferences';
import { destroy as destroyWorkspace } from '@/routes/workspaces';
import type { WorkspaceIconName } from '@/lib/workspace-icons';

const { t } = useI18n();
const page = usePage();
const online = useOnline();
const isOwner = computed(() => page.props.workspace?.role === 'owner');
const canDeleteWorkspace = computed(
    () => isOwner.value && page.props.workspaces.length > 1,
);
const deleteOpen = ref(false);
const workspaceForm = useForm({
    workspace_name: page.props.workspace?.name ?? '',
    icon: (page.props.workspace?.icon ?? 'house') as WorkspaceIconName,
});
const languageForm = useForm({ locale: page.props.locale });
const deleteForm = useForm({ confirmation: '' });

watch(deleteOpen, (open) => {
    if (!open) deleteForm.reset();
    deleteForm.clearErrors();
});

function saveWorkspace(): void {
    if (!isOwner.value || !online.value || workspaceForm.processing) return;
    workspaceForm.patch(updatePreferences.url(), { preserveScroll: true });
}

function saveLanguage(): void {
    if (!online.value || languageForm.processing) return;
    languageForm.patch(updateLocale.url(), { preserveScroll: true });
}

function deleteWorkspace(): void {
    if (
        !canDeleteWorkspace.value ||
        !online.value ||
        deleteForm.processing ||
        deleteForm.confirmation !== page.props.workspace?.name
    ) {
        return;
    }

    deleteForm.delete(destroyWorkspace.url(), {
        preserveScroll: false,
        preserveState: false,
        replace: true,
    });
}
</script>

<template>
    <section
        class="border-border/80 bg-card overflow-hidden rounded-3xl border"
    >
        <Head :title="t('settings.preferences.title')" />
        <header
            class="border-border/70 flex items-center gap-4 border-b p-5 sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 place-items-center rounded-2xl"
                ><Settings2 class="size-5" aria-hidden="true"
            /></span>
            <div>
                <h2 class="text-xl font-extrabold">
                    {{ t('settings.preferences.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('settings.preferences.description') }}
                </p>
            </div>
        </header>
        <form
            class="border-border/70 grid gap-5 border-b p-5 sm:p-6"
            @submit.prevent="saveWorkspace"
        >
            <div>
                <h3 class="text-sm font-bold">
                    {{ t('settings.preferences.workspace') }}
                </h3>
                <p
                    id="workspace_hint"
                    class="text-muted-foreground mt-1 text-sm"
                >
                    {{ t('settings.preferences.workspaceHint') }}
                </p>
            </div>
            <div class="grid max-w-md gap-2">
                <Label for="workspace_name">{{
                    t('settings.preferences.workspaceName')
                }}</Label>
                <Input
                    id="workspace_name"
                    v-model="workspaceForm.workspace_name"
                    required
                    maxlength="120"
                    :readonly="!isOwner"
                    :aria-invalid="!!workspaceForm.errors.workspace_name"
                    aria-describedby="workspace_hint workspace_error"
                />
                <InputError
                    id="workspace_error"
                    :message="workspaceForm.errors.workspace_name"
                />
                <p v-if="!isOwner" class="text-muted-foreground text-xs">
                    {{ t('settings.preferences.ownerOnly') }}
                </p>
            </div>
            <div class="grid max-w-md gap-2">
                <Label id="workspace_icon_label">{{
                    t('settings.preferences.workspaceIcon')
                }}</Label>
                <WorkspaceIconPicker
                    v-model="workspaceForm.icon"
                    label-id="workspace_icon_label"
                    :disabled="!isOwner || workspaceForm.processing || !online"
                />
                <InputError :message="workspaceForm.errors.icon" />
            </div>
            <Button
                v-if="isOwner"
                type="submit"
                class="min-h-11 w-fit"
                :disabled="workspaceForm.processing || !online"
            >
                {{
                    workspaceForm.processing
                        ? t('common.saving')
                        : t('settings.preferences.saveWorkspace')
                }}
            </Button>
        </form>
        <form
            class="border-border/70 grid gap-5 border-b p-5 sm:p-6"
            @submit.prevent="saveLanguage"
        >
            <div>
                <h3 class="text-sm font-bold">
                    {{ t('settings.language.title') }}
                </h3>
                <p
                    id="language_hint"
                    class="text-muted-foreground mt-1 text-sm"
                >
                    {{ t('settings.language.description') }}
                </p>
            </div>
            <div class="grid max-w-md gap-2">
                <Label for="locale">{{ t('settings.language.label') }}</Label>
                <Select
                    v-model="languageForm.locale"
                    :disabled="languageForm.processing || !online"
                >
                    <SelectTrigger
                        id="locale"
                        class="w-full"
                        aria-describedby="language_hint locale_error"
                        ><SelectValue
                    /></SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="locale in page.props.supportedLocales"
                            :key="locale.code"
                            :value="locale.code"
                            >{{ locale.name }}</SelectItem
                        >
                    </SelectContent>
                </Select>
                <InputError
                    id="locale_error"
                    :message="languageForm.errors.locale"
                />
            </div>
            <Button
                type="submit"
                class="min-h-11 w-fit"
                :disabled="languageForm.processing || !online"
                >{{
                    languageForm.processing
                        ? t('common.saving')
                        : t('settings.preferences.saveLanguage')
                }}</Button
            >
        </form>

        <section
            v-if="isOwner"
            class="bg-destructive/5 grid gap-5 p-5 sm:p-6"
            aria-labelledby="delete_workspace_title"
        >
            <div>
                <h3
                    id="delete_workspace_title"
                    class="text-destructive text-sm font-bold"
                >
                    {{ t('settings.preferences.deleteWorkspace.title') }}
                </h3>
                <p class="text-muted-foreground mt-1 max-w-2xl text-sm">
                    {{
                        t('settings.preferences.deleteWorkspace.description', {
                            name: page.props.workspace?.name,
                        })
                    }}
                </p>
            </div>

            <div>
                <ConfirmationDialog
                    v-model:open="deleteOpen"
                    :title="
                        t('settings.preferences.deleteWorkspace.confirmTitle')
                    "
                    :description="
                        t(
                            'settings.preferences.deleteWorkspace.confirmDescription',
                            { name: page.props.workspace?.name },
                        )
                    "
                    :resource-name="page.props.workspace?.name"
                    :confirm-label="
                        t('settings.preferences.deleteWorkspace.action')
                    "
                    :processing="deleteForm.processing"
                    :disabled="
                        !online ||
                        !canDeleteWorkspace ||
                        deleteForm.confirmation !== page.props.workspace?.name
                    "
                    :error="deleteForm.errors.confirmation"
                    destructive
                    @confirm="deleteWorkspace"
                >
                    <template #trigger>
                        <Button
                            type="button"
                            variant="destructive"
                            class="min-h-11 gap-2"
                            :disabled="
                                !online ||
                                !canDeleteWorkspace ||
                                deleteForm.processing
                            "
                        >
                            <Trash2 class="size-4" aria-hidden="true" />
                            {{
                                t('settings.preferences.deleteWorkspace.action')
                            }}
                        </Button>
                    </template>

                    <div class="grid gap-2">
                        <Label for="workspace_confirmation">
                            {{
                                t(
                                    'settings.preferences.deleteWorkspace.confirmLabel',
                                    { name: page.props.workspace?.name },
                                )
                            }}
                        </Label>
                        <Input
                            id="workspace_confirmation"
                            v-model="deleteForm.confirmation"
                            autocomplete="off"
                            :placeholder="page.props.workspace?.name"
                            :disabled="deleteForm.processing"
                            :aria-invalid="!!deleteForm.errors.confirmation"
                        />
                    </div>
                </ConfirmationDialog>
                <p
                    v-if="!canDeleteWorkspace"
                    class="text-muted-foreground mt-2 text-xs"
                >
                    {{
                        t('settings.preferences.deleteWorkspace.lastWorkspace')
                    }}
                </p>
            </div>
        </section>
    </section>
</template>
