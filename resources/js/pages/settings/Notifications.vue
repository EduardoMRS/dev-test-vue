<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { usePush } from '@/composables/usePush';
import http from '@/utils/http';


const { subscribed, toggle } = usePush();

const { t } = useI18n();

type NotificationSetting = {
    id: number;
    days_before: number;
    email_enabled: boolean;
    push_enabled: boolean;
};

const props = defineProps<{ setting: NotificationSetting }>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: t('notifications.settings'), href: '/settings/notifications' },
];

const form = useForm({
    days_before: props.setting.days_before,
    email_enabled: props.setting.email_enabled,
    push_enabled: props.setting.push_enabled,
});

function submit() {
    form.put('/settings/notifications');
}

function persistBeforeLeave() {
    if (!form.isDirty) return;

    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    const payload = new URLSearchParams({
        days_before: String(form.days_before ?? ''),
        email_enabled: form.email_enabled ? '1' : '0',
        push_enabled: form.push_enabled ? '1' : '0',
    });

    http.put('/settings/notifications', payload);
}

function handlePageLeave() {
    persistBeforeLeave();
}

function togglePush() {
    toggle();
    form.push_enabled = subscribed.value;
}

onMounted(() => {
    window.addEventListener('beforeunload', handlePageLeave);
    window.addEventListener('pagehide', handlePageLeave);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', handlePageLeave);
    window.removeEventListener('pagehide', handlePageLeave);
    persistBeforeLeave();
});

const page = usePage();
const flash = computed(() => (page.props as any).flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('notifications.settings')" />

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="t('notifications.settings')"
                    :description="t('notifications.settingsDescription')"
                />

                <!-- Flash -->
                <div
                    v-if="flash?.success"
                    class="rounded-md bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-300"
                >
                    {{ flash.success }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Days before -->
                    <div class="grid gap-2">
                        <Label for="days_before">{{ t('notifications.daysBefore') }}</Label>
                        <Input
                            id="days_before"
                            type="number"
                            v-model.number="form.days_before"
                            min="1"
                            max="30"
                            class="max-w-[120px]"
                        />
                        <p class="text-sm text-muted-foreground">
                            {{ t('notifications.daysBeforeHelp') }}
                        </p>
                        <InputError :message="form.errors.days_before" />
                    </div>

                    <!-- Email toggle -->
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label for="email_enabled">{{ t('notifications.emailEnabled') }}</Label>
                            <p class="text-sm text-muted-foreground">
                                {{ t('notifications.emailEnabledHelp') }}
                            </p>
                        </div>
                        <Switch
                            id="email_enabled"
                            v-model="form.email_enabled"
                        />
                    </div>

                    <!-- Push toggle -->
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label for="push_enabled">{{ t('notifications.pushEnabled') }}</Label>
                            <p class="text-sm text-muted-foreground">
                                {{ t('notifications.pushEnabledHelp') }}
                            </p>
                        </div>
                        <Switch
                            id="push_enabled"
                            v-model="form.push_enabled"
                            @change="togglePush"
                        />
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
