import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Appearance, ResolvedAppearance } from '@/types';

export type { Appearance, ResolvedAppearance };

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: unknown) => void;
};

const appearance = ref<Appearance>('system');
const systemPrefersDark = ref(false);
let initialized = false;

function isAppearance(value: unknown): value is Appearance {
    return value === 'light' || value === 'dark' || value === 'system';
}

const resolvedAppearance = computed<ResolvedAppearance>(() =>
    appearance.value === 'system'
        ? systemPrefersDark.value
            ? 'dark'
            : 'light'
        : appearance.value,
);

export function updateTheme(value: Appearance): void {
    if (typeof window === 'undefined') return;
    systemPrefersDark.value = window.matchMedia(
        '(prefers-color-scheme: dark)',
    ).matches;
    document.documentElement.classList.toggle(
        'dark',
        value === 'dark' || (value === 'system' && systemPrefersDark.value),
    );
}

function readAppearance(): Appearance {
    const fallback = document.documentElement.dataset.appearance;
    try {
        const saved = localStorage.getItem('appearance');
        if (isAppearance(saved)) return saved;
    } catch {
        // Keep the preference usable when browser storage is unavailable.
    }
    return isAppearance(fallback) ? fallback : 'system';
}

export function initializeTheme(): void {
    if (typeof window === 'undefined' || initialized) return;
    initialized = true;
    appearance.value = readAppearance();
    updateTheme(appearance.value);
    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            updateTheme(appearance.value);
        });
    window.addEventListener('storage', (event) => {
        if (event.key === 'appearance' || event.key === null) {
            appearance.value = isAppearance(event.newValue)
                ? event.newValue
                : 'system';
            updateTheme(appearance.value);
        }
    });
}

function updateAppearance(value: unknown): void {
    if (!isAppearance(value)) return;
    appearance.value = value;
    if (typeof window === 'undefined') return;
    try {
        localStorage.setItem('appearance', value);
    } catch {
        // A blocked localStorage must not prevent changing the theme.
    }
    document.cookie = `appearance=${value};path=/;max-age=31536000;SameSite=Lax`;
    updateTheme(value);
}

export function useAppearance(): UseAppearanceReturn {
    return { appearance, resolvedAppearance, updateAppearance };
}
