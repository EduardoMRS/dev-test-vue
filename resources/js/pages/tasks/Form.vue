<script setup lang="ts">
import { computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';

export type TaskFormData = {
    id?: number;
    title?: string;
    description?: string | null;
    due_date?: string | null;
    notify_email?: boolean;
    notify_push?: boolean;
};

const props = withDefaults(
    defineProps<{ task?: TaskFormData }>(),
    { task: undefined },
);

const { t } = useI18n();

const isEditing = computed(() => !!props.task?.id);

function toDateInput(value: string | null | undefined): string {
    if (!value) return '';
    return value.substring(0, 10);
}

const form = useForm({
    title:        props.task?.title        ?? '',
    description:  props.task?.description  ?? '',
    due_date:     toDateInput(props.task?.due_date),
    notify_email: props.task?.notify_email ?? true,
    notify_push:  props.task?.notify_push  ?? true,
});

function submit() {
    if (isEditing.value) {
        form.put(`/tasks/${props.task!.id}`);
    } else {
        form.post('/tasks', { onSuccess: () => form.reset() });
    }
}
</script>

<template>
    <Heading :title="isEditing ? t('tasks.editTask') : t('tasks.createTask')" />

    <form @submit.prevent="submit" class="space-y-6">
        <!-- Title -->
        <div class="grid gap-2">
            <Label for="title">{{ t('tasks.taskTitle') }}</Label>
            <Input
                id="title"
                v-model="form.title"
                :placeholder="t('tasks.titlePlaceholder')"
                required
            />
            <InputError :message="form.errors.title" />
        </div>

        <!-- Description -->
        <div class="grid gap-2">
            <Label for="description">{{ t('tasks.description') }}</Label>
            <textarea
                id="description"
                v-model="form.description"
                :placeholder="t('tasks.descriptionPlaceholder')"
                rows="4"
                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            />
            <InputError :message="form.errors.description" />
        </div>

        <!-- Due date -->
        <div class="grid gap-2">
            <Label for="due_date">{{ t('tasks.dueDate') }}</Label>
            <Input
                id="due_date"
                type="date"
                v-model="form.due_date"
                class="max-w-[200px]"
            />
            <InputError :message="form.errors.due_date" />
        </div>

        <!-- Per-task notification toggles -->
        <div class="space-y-3">
            <Label>{{ t('tasks.notifications') }}</Label>
            <div class="flex items-center gap-3">
                <Checkbox
                    id="notify_email"
                    :checked="form.notify_email"
                    @update:checked="form.notify_email = $event"
                />
                <Label for="notify_email" class="font-normal">{{ t('tasks.notifyEmail') }}</Label>
            </div>
            <div class="flex items-center gap-3">
                <Checkbox
                    id="notify_push"
                    :checked="form.notify_push"
                    @update:checked="form.notify_push = $event"
                />
                <Label for="notify_push" class="font-normal">{{ t('tasks.notifyPush') }}</Label>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
            <Button type="button" variant="outline" @click="router.get('/tasks')">
                {{ t('app.cancel') }}
            </Button>
            <Button type="submit" :disabled="form.processing">
                {{ isEditing ? t('app.save') : t('tasks.createTask') }}
            </Button>
        </div>
    </form>
</template>
