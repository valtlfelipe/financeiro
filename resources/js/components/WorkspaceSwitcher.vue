<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import WorkspaceIconPicker from '@/components/WorkspaceIconPicker.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useOnline } from '@/composables/useOnline';
import {
    workspaceNavigationBusy,
    workspacePageDirty,
} from '@/lib/workspaceContext';
import { workspaceIcon, type WorkspaceIconName } from '@/lib/workspace-icons';
import { store, switchMethod } from '@/routes/workspaces';
import type { WorkspaceOption } from '@/types';

const page = usePage();
const { t } = useI18n();
const online = useOnline();
const createOpen = ref(false);
const discardOpen = ref(false);
const pendingWorkspace = ref<WorkspaceOption | null>(null);
const currentWorkspace = computed(() => page.props.workspace);
const createForm = useForm({
    workspace_name: '',
    icon: 'house' as WorkspaceIconName,
});
const switchForm = useForm({ workspace_id: page.props.workspace?.id ?? 0 });

function switchWorkspace(workspace: WorkspaceOption): void {
    if (
        workspace.id === currentWorkspace.value?.id ||
        workspaceNavigationBusy.value ||
        !online.value
    ) {
        return;
    }

    if (workspacePageDirty.value) {
        pendingWorkspace.value = workspace;
        discardOpen.value = true;

        return;
    }

    performSwitch(workspace);
}

function performSwitch(workspace: WorkspaceOption): void {
    workspacePageDirty.value = false;
    switchForm.workspace_id = workspace.id;
    switchForm.patch(switchMethod.url(), {
        preserveScroll: false,
        preserveState: false,
        replace: true,
    });
}

function confirmWorkspaceSwitch(): void {
    const workspace = pendingWorkspace.value;

    if (workspace === null) return;

    discardOpen.value = false;
    pendingWorkspace.value = null;
    performSwitch(workspace);
}

function updateDiscardOpen(open: boolean): void {
    discardOpen.value = open;

    if (!open) pendingWorkspace.value = null;
}

function createWorkspace(): void {
    if (createForm.processing || !online.value) return;

    createForm.post(store.url(), {
        preserveScroll: false,
        preserveState: false,
        replace: true,
        onSuccess: () => {
            createForm.reset();
            createOpen.value = false;
            workspacePageDirty.value = false;
        },
    });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="hover:bg-muted flex max-w-48 min-w-0 items-center gap-1.5 rounded-xl px-2 py-1 text-left transition-colors sm:max-w-64"
                :aria-label="t('common.workspace.menu')"
            >
                <span
                    class="bg-primary/10 text-primary grid size-8 shrink-0 place-items-center rounded-lg"
                >
                    <component
                        :is="workspaceIcon(currentWorkspace?.icon)"
                        class="size-4"
                        aria-hidden="true"
                    />
                </span>
                <span class="min-w-0">
                    <span
                        class="block truncate text-sm font-extrabold tracking-tight"
                    >
                        {{ t('common.appName') }}
                    </span>
                    <span
                        class="text-muted-foreground block truncate text-[11px]"
                    >
                        {{ currentWorkspace?.name }}
                    </span>
                </span>
                <ChevronsUpDown
                    class="text-muted-foreground size-3.5 shrink-0"
                    aria-hidden="true"
                />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
            align="start"
            class="w-72 max-w-[calc(100vw-2rem)] rounded-2xl p-2"
            :side-offset="8"
        >
            <DropdownMenuLabel class="px-3 py-2 text-xs">
                {{ t('common.workspace.current') }}
            </DropdownMenuLabel>
            <DropdownMenuItem
                v-for="workspace in page.props.workspaces"
                :key="workspace.id"
                class="min-h-12 rounded-xl px-3"
                :disabled="workspaceNavigationBusy || !online"
                @select="switchWorkspace(workspace)"
            >
                <span
                    class="bg-muted text-muted-foreground grid size-8 shrink-0 place-items-center rounded-lg"
                >
                    <component
                        :is="workspaceIcon(workspace.icon)"
                        class="size-4"
                        aria-hidden="true"
                    />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-semibold">{{
                        workspace.name
                    }}</span>
                    <span class="text-muted-foreground block text-xs">
                        {{ t(`common.workspace.${workspace.role}`) }}
                    </span>
                </span>
                <Check
                    v-if="workspace.id === currentWorkspace?.id"
                    class="text-primary size-4 shrink-0"
                    aria-hidden="true"
                />
            </DropdownMenuItem>
            <template v-if="page.props.canCreateWorkspace">
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    class="min-h-11 rounded-xl px-3 font-semibold"
                    :disabled="workspaceNavigationBusy || !online"
                    @select="createOpen = true"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    {{ t('common.workspace.create') }}
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>

    <Dialog v-model:open="createOpen">
        <DialogContent class="rounded-3xl sm:max-w-md">
            <form class="grid gap-5" @submit.prevent="createWorkspace">
                <DialogHeader>
                    <DialogTitle>{{
                        t('common.workspace.createTitle')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ t('common.workspace.createDescription') }}
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="new_workspace_name">{{
                        t('common.workspace.name')
                    }}</Label>
                    <Input
                        id="new_workspace_name"
                        v-model="createForm.workspace_name"
                        required
                        maxlength="120"
                        autocomplete="organization"
                        :placeholder="t('common.workspace.namePlaceholder')"
                        :aria-invalid="!!createForm.errors.workspace_name"
                        autofocus
                    />
                    <InputError :message="createForm.errors.workspace_name" />
                </div>
                <div class="grid gap-2">
                    <Label id="new_workspace_icon_label">{{
                        t('common.workspace.icon')
                    }}</Label>
                    <WorkspaceIconPicker
                        v-model="createForm.icon"
                        label-id="new_workspace_icon_label"
                        :disabled="createForm.processing"
                    />
                    <InputError :message="createForm.errors.icon" />
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="createForm.processing"
                        @click="createOpen = false"
                    >
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        :disabled="createForm.processing || !online"
                    >
                        {{
                            createForm.processing
                                ? t('common.workspace.creating')
                                : t('common.workspace.create')
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmationDialog
        :open="discardOpen"
        :title="t('common.workspace.discardTitle')"
        :description="
            t('common.workspace.discardDescription', {
                workspace: pendingWorkspace?.name,
            })
        "
        :resource-name="pendingWorkspace?.name"
        :confirm-label="t('common.workspace.discardAction')"
        :disabled="workspaceNavigationBusy || !online"
        @update:open="updateDiscardOpen"
        @confirm="confirmWorkspaceSwitch"
    />
</template>
