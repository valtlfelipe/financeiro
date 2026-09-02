import { onBeforeUnmount, onMounted, readonly, ref } from 'vue';

const online = ref(true);

export function useOnline() {
    const checkConnection = async (): Promise<void> => {
        try {
            // navigator.onLine can be false while a local Laravel server is
            // still reachable. Probe the app itself so local development and
            // self-hosted installations use the same connectivity rule.
            const response = await fetch('/up', {
                method: 'GET',
                cache: 'no-store',
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });

            online.value = response.ok;
        } catch {
            online.value = false;
        }
    };

    onMounted(() => {
        void checkConnection();
        window.addEventListener('online', checkConnection);
        window.addEventListener('offline', checkConnection);
    });
    onBeforeUnmount(() => {
        window.removeEventListener('online', checkConnection);
        window.removeEventListener('offline', checkConnection);
    });

    return readonly(online);
}
