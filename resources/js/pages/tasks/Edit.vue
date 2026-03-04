<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import TaskForm, { type TaskFormData } from './Form.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const props = defineProps<{ task: TaskFormData & { id: number } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('tasks.title'), href: '/tasks' },
    { title: t('tasks.editTask'), href: `/tasks/${props.task.id}/edit` },
];
</script>

<template>
    <Head :title="t('tasks.editTask')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-4">
            <TaskForm :task="task" />
        </div>
    </AppLayout>
</template>
