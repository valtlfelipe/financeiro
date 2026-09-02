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
    <img
        src="/icons/financeiro-192.png"
        alt=""
        width="48"
        height="48"
        :class="['block shrink-0 rounded-[25%]', className]"
        v-bind="$attrs"
    />
</template>
