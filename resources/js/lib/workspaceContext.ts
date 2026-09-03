import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import type { Page } from '@inertiajs/core';

let activeWorkspaceId: number | null = null;
let initialized = false;
let pendingRequests = 0;

export const workspaceNavigationBusy = ref(false);
export const workspacePageDirty = ref(false);

export function workspaceHeaders(): Record<string, string> {
    return activeWorkspaceId === null
        ? {}
        : { 'X-Workspace-Id': String(activeWorkspaceId) };
}

export function beginWorkspaceRequest(): void {
    pendingRequests += 1;
    workspaceNavigationBusy.value = true;
}

export function finishWorkspaceRequest(): void {
    pendingRequests = Math.max(0, pendingRequests - 1);
    workspaceNavigationBusy.value = pendingRequests > 0;
}

export function initializeWorkspaceContext(page: Page): void {
    updateWorkspace(page);

    if (initialized) return;
    initialized = true;

    document.addEventListener('input', markDirty);
    document.addEventListener('change', markDirty);
    router.on('start', beginWorkspaceRequest);
    router.on('finish', finishWorkspaceRequest);
    router.on('navigate', (event) => {
        updateWorkspace(event.detail.page);
        workspacePageDirty.value = false;
    });
    router.on('httpException', (event) => {
        if (
            event.detail.response.headers['x-workspace-context-changed'] !==
            'true'
        ) {
            return;
        }

        toast.error('O espaço desta sessão mudou.', {
            description: 'Atualize a página antes de salvar.',
            duration: Infinity,
            action: {
                label: 'Atualizar',
                onClick: () => window.location.reload(),
            },
        });

        return false;
    });
}

function updateWorkspace(page: Page): void {
    const workspace = page.props.workspace;
    activeWorkspaceId =
        typeof workspace === 'object' &&
        workspace !== null &&
        'id' in workspace &&
        typeof workspace.id === 'number'
            ? workspace.id
            : null;
}

function markDirty(event: Event): void {
    const target = event.target;

    if (target instanceof Element && target.closest('form')) {
        workspacePageDirty.value = true;
    }
}
