<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Circle,
    Clock,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
    AlertTriangle,
    Bell,
    BellOff,
    Mail,
    MailX,
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const { t } = useI18n();

// ─────────────────────────── Types ───────────────────────────

type Task = {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    completed: boolean;
    completed_at: string | null;
    notify_email: boolean;
    notify_push: boolean;
    created_at: string;
    updated_at: string;
};

type PaginatedTasks = {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    tasks: PaginatedTasks;
    filters: { search?: string; status?: string };
};

const props = defineProps<Props>();

// ─────────────────────────── Breadcrumbs ─────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('tasks.title'), href: '/tasks' },
];

// ─────────────────────────── Search/Filter ───────────────────

const searchQuery = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch([searchQuery, statusFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/tasks',
            { search: searchQuery.value, status: statusFilter.value },
            { preserveState: true, replace: true },
        );
    }, 350);
});

// ─────────────────────────── Dialog state ────────────────────

const showDeleteConfirm = ref(false);
const selectedTask = ref<Task | null>(null);

// ─────────────────────────── Actions ─────────────────────────

function toggleComplete(task: Task) {
    router.patch(`/tasks/${task.id}/toggle`, {}, { preserveState: true });
}

function openDelete(task: Task) {
    selectedTask.value = task;
    showDeleteConfirm.value = true;
}

function confirmDelete() {
    if (!selectedTask.value) return;
    router.delete(`/tasks/${selectedTask.value.id}`, {
        onSuccess: () => {
            showDeleteConfirm.value = false;
        },
    });
}

function isOverdue(task: Task): boolean {
    if (task.completed || !task.due_date) return false;
    return new Date(task.due_date + 'T00:00:00') < new Date(new Date().toDateString());
}

function statusBadge(task: Task) {
    if (task.completed) return { label: t('tasks.completed'), variant: 'secondary' as const };
    if (isOverdue(task)) return { label: t('tasks.overdue'), variant: 'destructive' as const };
    return { label: t('tasks.pending'), variant: 'outline' as const };
}

function formatDate(date: string | null): string {
    if (!date) return '—';
    const _date = date.substring(0, 10);
    return new Date(_date + 'T00:00:00').toLocaleDateString('pt-BR');
}

// Flash message
const page = usePage();
const flash = computed(() => (page.props as any).flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head :title="t('tasks.myTasks')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading variant="small" :title="t('tasks.myTasks')" />
                <Button size="sm" @click="router.get('/tasks/create')">
                    <Plus class="mr-1 size-4" /> {{ t('tasks.newTask') }}
                </Button>
            </div>

            <!-- Flash -->
            <div v-if="flash?.success" class="rounded-md bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-300">
                {{ flash.success }}
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <Input
                    v-model="searchQuery"
                    :placeholder="t('tasks.searchPlaceholder')"
                    class="w-full max-w-xs"
                />
                <select
                    v-model="statusFilter"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                >
                    <option value="">{{ t('tasks.filterAll') }}</option>
                    <option value="pending">{{ t('tasks.filterPending') }}</option>
                    <option value="completed">{{ t('tasks.filterCompleted') }}</option>
                    <option value="overdue">{{ t('tasks.filterOverdue') }}</option>
                </select>
            </div>

            <!-- Tasks Table -->
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">{{ t('tasks.status') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">{{ t('tasks.taskTitle') }}</th>
                            <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground md:table-cell">{{ t('tasks.dueDate') }}</th>
                            <th class="hidden px-4 py-3 text-left font-medium text-muted-foreground sm:table-cell">{{ t('notifications.title') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">{{ t('app.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="task in tasks.data" :key="task.id" class="hover:bg-muted/20 transition-colors">
                            <td class="px-4 py-3">
                                <button
                                    @click="toggleComplete(task)"
                                    class="flex items-center justify-center transition-colors"
                                    :title="task.completed ? t('tasks.markPending') : t('tasks.markCompleted')"
                                >
                                    <CheckCircle2 v-if="task.completed" class="size-5 text-green-500" />
                                    <Circle v-else class="size-5 text-muted-foreground hover:text-primary" />
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    <span :class="['font-medium', { 'line-through text-muted-foreground': task.completed }]">
                                        {{ task.title }}
                                    </span>
                                    <span v-if="task.description" class="text-xs text-muted-foreground line-clamp-1">
                                        {{ task.description }}
                                    </span>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <div v-if="task.due_date" class="flex items-center gap-1.5">
                                    <AlertTriangle v-if="isOverdue(task)" class="size-3.5 text-destructive" />
                                    <CalendarDays v-else class="size-3.5 text-muted-foreground" />
                                    <span :class="[isOverdue(task) ? 'text-destructive font-medium' : 'text-muted-foreground']">
                                        {{ formatDate(task.due_date) }}
                                    </span>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <div class="flex gap-1">
                                    <Mail v-if="task.notify_email" class="size-4 text-blue-500" :title="t('tasks.notifyEmail')" />
                                    <MailX v-else class="size-4 text-muted-foreground/40" />
                                    <Bell v-if="task.notify_push" class="size-4 text-purple-500" :title="t('tasks.notifyPush')" />
                                    <BellOff v-else class="size-4 text-muted-foreground/40" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-48">
                                        <DropdownMenuItem @click="toggleComplete(task)">
                                            <CheckCircle2 class="mr-2 size-4" />
                                            {{ task.completed ? t('tasks.markPending') : t('tasks.markCompleted') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="router.get(`/tasks/${task.id}/edit`)">
                                            <Pencil class="mr-2 size-4" /> {{ t('app.edit') }}
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            class="text-destructive focus:text-destructive"
                                            @click="openDelete(task)"
                                        >
                                            <Trash2 class="mr-2 size-4" /> {{ t('app.delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                        <tr v-if="tasks.data.length === 0">
                            <td colspan="5" class="px-4 py-12 text-center text-muted-foreground">
                                <div class="flex flex-col items-center gap-2">
                                    <Clock class="size-8 text-muted-foreground/50" />
                                    <p>{{ t('tasks.noTasks') }}</p>
                                    <Button size="sm" variant="outline" @click="router.get('/tasks/create')">
                                        <Plus class="mr-1 size-4" /> {{ t('tasks.newTask') }}
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="tasks.total > 0" class="flex items-center justify-between text-sm text-muted-foreground">
                <span>{{ tasks.total }} {{ t('tasks.title').toLowerCase() }} &bull; {{ t('app.showing') }} {{ tasks.data.length }} {{ t('app.of') }} {{ tasks.total }}</span>
                <div class="flex gap-1">
                    <Button
                        variant="outline" size="icon"
                        :disabled="!tasks.prev_page_url"
                        @click="router.get(tasks.prev_page_url!)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline" size="icon"
                        :disabled="!tasks.next_page_url"
                        @click="router.get(tasks.next_page_url!)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- ─── Delete Confirm Dialog ─── -->
        <Dialog v-model:open="showDeleteConfirm">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ t('tasks.deleteTask') }}</DialogTitle>
                    <DialogDescription>{{ t('tasks.confirmDelete') }}</DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showDeleteConfirm = false">{{ t('app.cancel') }}</Button>
                    <Button variant="destructive" @click="confirmDelete">{{ t('app.delete') }}</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
