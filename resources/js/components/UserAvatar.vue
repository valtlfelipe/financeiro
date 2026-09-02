<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { userAvatar } from '@/lib/avatar';
import type { User } from '@/types';

const props = defineProps<{ user: Pick<User, 'id' | 'name'> }>();
const source = computed(() => userAvatar(props.user.id));
</script>

<template>
    <Avatar class="border-border/50 size-9 border" aria-hidden="true">
        <AvatarImage :src="source" alt="" />
        <AvatarFallback
            class="bg-primary/10 text-primary text-xs font-extrabold"
        >
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>
</template>
