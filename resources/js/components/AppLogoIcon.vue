<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <circle cx="20" cy="20" r="17" fill="currentColor" opacity="0.22" />
        <path
            fill="currentColor"
            d="M20 3a17 17 0 0 1 0 34V25a5 5 0 0 0 0-10V3Z"
        />
        <circle cx="20" cy="20" r="5" fill="currentColor" />
    </svg>
</template>
