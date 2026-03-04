<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import TaskForm from './Form.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('tasks.title'), href: '/tasks' },
    { title: t('tasks.createTask'), href: '/tasks/create' },
];
</script>

<template>
    <Head :title="t('tasks.createTask')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-4">
            <TaskForm />
        </div>
    </AppLayout>
</template>
