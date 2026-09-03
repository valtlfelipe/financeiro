<script setup lang="ts">
import { Form, Head, useForm } from '@inertiajs/vue3';
import { Check, Copy, MailPlus, UserMinus, Users, X } from '@lucide/vue';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ConfirmationDialog from '@/components/ConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import UserAvatar from '@/components/UserAvatar.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useOnline } from '@/composables/useOnline';
import { destroy as cancelInvitation, store } from '@/routes/invitations';
import { destroy as removeMember } from '@/routes/members';

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
const online = useOnline();
const cancelForm = useForm({});
const removeForm = useForm({});

function cancel(invitationId: number): void {
    if (cancelForm.processing || !online.value) return;
    cancelForm.delete(cancelInvitation.url(invitationId), {
        preserveScroll: true,
    });
}

function remove(memberId: number): void {
    if (removeForm.processing || !online.value) return;
    removeForm.delete(removeMember.url(memberId), { preserveScroll: true });
}

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
            class="border-border bg-muted/50 grid gap-x-3 gap-y-2 border-b p-5 sm:grid-cols-[1fr_auto] sm:p-6"
        >
            <Label for="invite_email" class="sm:col-span-2">{{
                t('settings.members.email')
            }}</Label>
            <Input
                id="invite_email"
                name="email"
                type="email"
                required
                :placeholder="t('auth.fields.emailPlaceholder')"
            />
            <InputError
                v-if="errors.email"
                :message="errors.email"
                class="sm:col-start-1"
            />
            <div class="flex sm:col-start-2 sm:row-start-2">
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
                <ConfirmationDialog
                    v-if="member.role !== 'owner'"
                    :title="t('settings.members.removeTitle')"
                    :resource-name="member.name"
                    :description="
                        t('settings.members.removeDescription', {
                            name: member.name,
                        })
                    "
                    :confirm-label="t('settings.members.removeAction')"
                    :processing="removeForm.processing"
                    :disabled="!online"
                    :error="
                        Object.values(removeForm.errors).find(
                            (message): message is string =>
                                typeof message === 'string',
                        )
                    "
                    destructive
                    @confirm="remove(member.id)"
                >
                    <template #trigger>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-destructive size-11 shrink-0"
                            :disabled="!online || removeForm.processing"
                            :aria-label="
                                t('settings.members.removeLabel', {
                                    name: member.name,
                                })
                            "
                            @click="removeForm.clearErrors()"
                        >
                            <UserMinus class="size-4" aria-hidden="true" />
                        </Button>
                    </template>
                </ConfirmationDialog>
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
            <div
                v-for="invitation in pendingInvitations"
                :key="invitation.id"
                class="mt-3 flex items-center gap-3"
            >
                <p class="min-w-0 flex-1 truncate text-sm font-semibold">
                    {{ invitation.email }}
                </p>
                <ConfirmationDialog
                    :title="t('settings.members.cancelTitle')"
                    :resource-name="invitation.email"
                    :description="
                        t('settings.members.cancelDescription', {
                            email: invitation.email,
                        })
                    "
                    :confirm-label="t('settings.members.cancelAction')"
                    :processing="cancelForm.processing"
                    :disabled="!online"
                    :error="
                        Object.values(cancelForm.errors).find(
                            (message): message is string =>
                                typeof message === 'string',
                        )
                    "
                    destructive
                    @confirm="cancel(invitation.id)"
                >
                    <template #trigger>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="text-destructive size-11 shrink-0"
                            :disabled="!online || cancelForm.processing"
                            :aria-label="
                                t('settings.members.cancelLabel', {
                                    email: invitation.email,
                                })
                            "
                            @click="cancelForm.clearErrors()"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </Button>
                    </template>
                </ConfirmationDialog>
            </div>
        </div>
    </section>
</template>
