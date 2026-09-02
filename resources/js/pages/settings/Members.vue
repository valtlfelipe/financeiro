<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, Copy, MailPlus, Users } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import InputError from '@/components/InputError.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/invitations';

defineProps<{
    members: Array<{
        id: number;
        name: string;
        email: string;
        role: 'owner' | 'member';
    }>;
    pendingInvitations: Array<{ id: number; email: string; expiresAt: string }>;
    invitationUrl?: string | null;
}>();
const { t } = useI18n();
const copied = ref(false);
async function copyLink(url: string): Promise<void> {
    await navigator.clipboard.writeText(url);
    copied.value = true;
    window.setTimeout(() => {
        copied.value = false;
    }, 1800);
}
</script>

<template>
    <section
        class="border-border/80 bg-card overflow-hidden rounded-3xl border"
    >
        <Head :title="t('settings.members.title')" />
        <header
            class="border-border/70 flex items-center gap-4 border-b p-5 sm:p-6"
        >
            <span
                class="bg-primary/10 text-primary grid size-11 place-items-center rounded-2xl"
                ><Users class="size-5"
            /></span>
            <div>
                <h2 class="text-xl font-extrabold">
                    {{ t('settings.members.title') }}
                </h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ t('settings.members.description') }}
                </p>
            </div>
        </header>

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
            class="border-border bg-muted/50 grid gap-3 border-b p-5 sm:grid-cols-[1fr_auto] sm:p-6"
        >
            <div class="grid gap-2">
                <Label for="invite_email">{{
                    t('settings.members.email')
                }}</Label
                ><Input
                    id="invite_email"
                    name="email"
                    type="email"
                    required
                    :placeholder="t('auth.fields.emailPlaceholder')"
                /><InputError :message="errors.email" />
            </div>
            <div class="flex items-end">
                <Button type="submit" class="h-11 gap-2" :disabled="processing"
                    ><MailPlus class="size-4" />{{
                        t('settings.members.invite')
                    }}</Button
                >
            </div>
        </Form>

        <div
            v-if="invitationUrl"
            class="border-primary/30 bg-primary/5 m-5 rounded-2xl border p-4 sm:m-6"
        >
            <Label for="invitation_url">{{
                t('settings.members.inviteLink')
            }}</Label>
            <div class="mt-2 flex gap-2">
                <Input
                    id="invitation_url"
                    :model-value="invitationUrl"
                    readonly
                    class="font-data text-xs"
                /><Button
                    variant="outline"
                    class="shrink-0 gap-2"
                    @click="copyLink(invitationUrl)"
                    ><Check v-if="copied" class="size-4" /><Copy
                        v-else
                        class="size-4"
                    />{{
                        copied
                            ? t('settings.members.copied')
                            : t('settings.members.copyLink')
                    }}</Button
                >
            </div>
        </div>

        <div class="divide-border/70 divide-y">
            <article
                v-for="member in members"
                :key="member.id"
                class="flex items-center gap-4 px-5 py-4 sm:px-6"
            >
                <UserAvatar :user="member" class="size-10" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold">{{ member.name }}</p>
                    <p class="text-muted-foreground truncate text-xs">
                        {{ member.email }}
                    </p>
                </div>
                <span
                    class="bg-muted rounded-full px-3 py-1 text-xs font-bold"
                    >{{
                        t(
                            `settings.members.${member.role === 'owner' ? 'roleOwner' : 'roleMember'}`,
                        )
                    }}</span
                >
            </article>
        </div>

        <div
            v-if="pendingInvitations.length"
            class="border-border border-t p-5 sm:p-6"
        >
            <h3
                class="text-muted-foreground text-xs font-extrabold tracking-wider uppercase"
            >
                {{ t('settings.members.pending') }}
            </h3>
            <p
                v-for="invitation in pendingInvitations"
                :key="invitation.id"
                class="mt-3 text-sm font-semibold"
            >
                {{ invitation.email }}
            </p>
        </div>
    </section>
</template>
