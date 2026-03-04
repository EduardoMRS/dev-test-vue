<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    Eye,
    EyeOff,
    KeyRound,
    Mail,
    MoreHorizontal,
    Plus,
    ShieldOff,
    Trash2,
    UserCog,
} from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { UserRole } from '@/types/auth';

// ─────────────────────────────────── Types ───────────────────────────────────

type AdminUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    active: boolean;
    two_factor_enabled: boolean;
    email_verified_at: string | null;
    created_at: string;
};

type PaginatedUsers = {
    data: AdminUser[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    users: PaginatedUsers;
    filters: { search?: string; role?: string };
    roles: UserRole[];
};

const props = defineProps<Props>();

// ─────────────────────────────────── Breadcrumbs ─────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin/users' },
    { title: 'Usuários', href: '/admin/users' },
];

// ─────────────────────────────────── Search/Filter ───────────────────────────

const searchQuery = ref(props.filters.search ?? '');
const roleFilter  = ref(props.filters.role ?? '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch([searchQuery, roleFilter], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/users',
            { search: searchQuery.value, role: roleFilter.value },
            { preserveState: true, replace: true },
        );
    }, 350);
});

// ─────────────────────────────────── Dialogs state ───────────────────────────

const showCreateDialog      = ref(false);
const showEditDialog        = ref(false);
const showPasswordDialog    = ref(false);
const showDeleteConfirm     = ref(false);
const selectedUser          = ref<AdminUser | null>(null);
const showNewPassword       = ref(false);
const showNewPasswordConfirm = ref(false);

// ─────────────────────────────────── Forms ───────────────────────────────────

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user' as UserRole,
});

const editForm = useForm({
    name: '',
    email: '',
    role: 'user' as UserRole,
    active: true,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

// ─────────────────────────────────── Helpers ─────────────────────────────────

const roleBadgeVariant = (role: UserRole) => {
    if (role === 'admin')     return 'destructive';
    if (role === 'moderator') return 'secondary';
    return 'outline';
};

const roleLabel = (role: UserRole) => {
    if (role === 'admin')     return 'Admin';
    if (role === 'moderator') return 'Moderador';
    return 'Usuário';
};

// ─────────────────────────────────── Actions ─────────────────────────────────

function openCreate() {
    createForm.reset();
    showCreateDialog.value = true;
}

function submitCreate() {
    createForm.post('/admin/users', {
        onSuccess: () => { showCreateDialog.value = false; createForm.reset(); },
    });
}

function openEdit(user: AdminUser) {
    selectedUser.value = user;
    editForm.name   = user.name;
    editForm.email  = user.email;
    editForm.role   = user.role;
    editForm.active = user.active;
    showEditDialog.value = true;
}

function submitEdit() {
    if (!selectedUser.value) return;
    editForm.patch(`/admin/users/${selectedUser.value.id}`, {
        onSuccess: () => { showEditDialog.value = false; },
    });
}

function openResetPassword(user: AdminUser) {
    selectedUser.value = user;
    passwordForm.reset();
    showPasswordDialog.value = true;
}

function submitResetPassword() {
    if (!selectedUser.value) return;
    passwordForm.post(`/admin/users/${selectedUser.value.id}/reset-password`, {
        onSuccess: () => { showPasswordDialog.value = false; passwordForm.reset(); },
    });
}

function sendPasswordResetLink(user: AdminUser) {
    router.post(`/admin/users/${user.id}/send-password-reset`);
}

function disableTwoFactor(user: AdminUser) {
    router.post(`/admin/users/${user.id}/disable-two-factor`);
}

function toggleActive(user: AdminUser) {
    router.post(`/admin/users/${user.id}/toggle-active`);
}

function openDelete(user: AdminUser) {
    selectedUser.value = user;
    showDeleteConfirm.value = true;
}

function confirmDelete() {
    if (!selectedUser.value) return;
    router.delete(`/admin/users/${selectedUser.value.id}`, {
        onSuccess: () => { showDeleteConfirm.value = false; },
    });
}

// Flash message
const page = usePage();
const flash = computed(() => (page.props as any).flash as { success?: string; error?: string } | undefined);
</script>

<template>
    <Head title="Admin – Usuários" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading variant="small" title="Gerenciar Usuários" description="Gerencie contas, papéis e acessos." />
                <Button @click="openCreate" size="sm">
                    <Plus class="mr-1 size-4" /> Novo Usuário
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
                    placeholder="Buscar por nome ou e-mail…"
                    class="w-full max-w-xs"
                />
                <select
                    v-model="roleFilter"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                >
                    <option value="">Todos os papéis</option>
                    <option v-for="r in props.roles" :key="r" :value="r">{{ roleLabel(r) }}</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-xl border border-border">
                <table class="min-w-full divide-y divide-border text-sm">
                    <thead class="bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nome</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">E-mail</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Papel</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">2FA</th>
                            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/20 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="roleBadgeVariant(user.role)">{{ roleLabel(user.role) }}</Badge>
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="user.active ? 'secondary' : 'outline'">
                                    {{ user.active ? 'Ativo' : 'Inativo' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <CheckCircle v-if="user.two_factor_enabled" class="size-4 text-green-500" />
                                <span v-else class="text-muted-foreground text-xs">—</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="icon">
                                            <MoreHorizontal class="size-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end" class="w-56">
                                        <DropdownMenuItem @click="openEdit(user)">
                                            <UserCog class="mr-2 size-4" /> Editar perfil / papel
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem @click="sendPasswordResetLink(user)">
                                            <Mail class="mr-2 size-4" /> Enviar link de reset
                                        </DropdownMenuItem>

                                        <DropdownMenuItem @click="openResetPassword(user)">
                                            <KeyRound class="mr-2 size-4" /> Redefinir senha agora
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="user.two_factor_enabled"
                                            @click="disableTwoFactor(user)"
                                        >
                                            <ShieldOff class="mr-2 size-4" /> Desativar 2FA
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem @click="toggleActive(user)">
                                            <Eye v-if="!user.active" class="mr-2 size-4" />
                                            <EyeOff v-else class="mr-2 size-4" />
                                            {{ user.active ? 'Desativar conta' : 'Ativar conta' }}
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem
                                            class="text-destructive focus:text-destructive"
                                            @click="openDelete(user)"
                                        >
                                            <Trash2 class="mr-2 size-4" /> Excluir usuário
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Nenhum usuário encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between text-sm text-muted-foreground">
                <span>{{ users.total }} usuário(s) • página {{ users.current_page }} de {{ users.last_page }}</span>
                <div class="flex gap-1">
                    <Button
                        variant="outline" size="icon"
                        :disabled="!users.prev_page_url"
                        @click="router.get(users.prev_page_url!)"
                    >
                        <ChevronLeft class="size-4" />
                    </Button>
                    <Button
                        variant="outline" size="icon"
                        :disabled="!users.next_page_url"
                        @click="router.get(users.next_page_url!)"
                    >
                        <ChevronRight class="size-4" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- ─── Create User Dialog ─── -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Criar novo usuário</DialogTitle>
                    <DialogDescription>Preencha os dados do novo usuário.</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="c_name">Nome</Label>
                        <Input id="c_name" v-model="createForm.name" placeholder="Nome completo" required />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="c_email">E-mail</Label>
                        <Input id="c_email" type="email" v-model="createForm.email" placeholder="email@dominio.com" required />
                        <InputError :message="createForm.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="c_role">Papel</Label>
                        <select id="c_role" v-model="createForm.role"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm">
                            <option v-for="r in props.roles" :key="r" :value="r">{{ roleLabel(r) }}</option>
                        </select>
                        <InputError :message="createForm.errors.role" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="c_password">Senha</Label>
                        <div class="relative">
                            <Input
                                id="c_password"
                                :type="showNewPassword ? 'text' : 'password'"
                                v-model="createForm.password"
                                placeholder="Mínimo 8 caracteres"
                                required
                                class="pr-10"
                            />
                            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                                @click="showNewPassword = !showNewPassword">
                                <Eye v-if="!showNewPassword" class="size-4" />
                                <EyeOff v-else class="size-4" />
                            </button>
                        </div>
                        <InputError :message="createForm.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="c_confirm">Confirmar senha</Label>
                        <div class="relative">
                            <Input
                                id="c_confirm"
                                :type="showNewPasswordConfirm ? 'text' : 'password'"
                                v-model="createForm.password_confirmation"
                                placeholder="Repita a senha"
                                required
                                class="pr-10"
                            />
                            <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground"
                                @click="showNewPasswordConfirm = !showNewPasswordConfirm">
                                <Eye v-if="!showNewPasswordConfirm" class="size-4" />
                                <EyeOff v-else class="size-4" />
                            </button>
                        </div>
                        <InputError :message="createForm.errors.password_confirmation" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showCreateDialog = false">Cancelar</Button>
                        <Button type="submit" :disabled="createForm.processing">Criar usuário</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ─── Edit User Dialog ─── -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar usuário</DialogTitle>
                    <DialogDescription>Altere nome, e-mail, papel ou status da conta.</DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="e_name">Nome</Label>
                        <Input id="e_name" v-model="editForm.name" required />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="e_email">E-mail</Label>
                        <Input id="e_email" type="email" v-model="editForm.email" required />
                        <InputError :message="editForm.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="e_role">Papel</Label>
                        <select id="e_role" v-model="editForm.role"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm">
                            <option v-for="r in props.roles" :key="r" :value="r">{{ roleLabel(r) }}</option>
                        </select>
                        <InputError :message="editForm.errors.role" />
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="e_active" type="checkbox" v-model="editForm.active" class="size-4 rounded border-input" />
                        <Label for="e_active">Conta ativa</Label>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showEditDialog = false">Cancelar</Button>
                        <Button type="submit" :disabled="editForm.processing">Salvar alterações</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ─── Reset Password Dialog ─── -->
        <Dialog v-model:open="showPasswordDialog">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Redefinir senha</DialogTitle>
                    <DialogDescription v-if="selectedUser">
                        Define uma nova senha para <strong>{{ selectedUser.name }}</strong>.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitResetPassword" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="rp_password">Nova senha</Label>
                        <Input id="rp_password" type="password" v-model="passwordForm.password" placeholder="Mínimo 8 caracteres" required />
                        <InputError :message="passwordForm.errors.password" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="rp_confirm">Confirmar nova senha</Label>
                        <Input id="rp_confirm" type="password" v-model="passwordForm.password_confirmation" placeholder="Repita a senha" required />
                        <InputError :message="passwordForm.errors.password_confirmation" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showPasswordDialog = false">Cancelar</Button>
                        <Button type="submit" :disabled="passwordForm.processing">Redefinir senha</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ─── Delete Confirm Dialog ─── -->
        <Dialog v-model:open="showDeleteConfirm">
            <DialogContent class="sm:max-w-sm">
                <DialogHeader>
                    <DialogTitle>Confirmar exclusão</DialogTitle>
                    <DialogDescription v-if="selectedUser">
                        Tem certeza que deseja excluir <strong>{{ selectedUser.name }}</strong>? Essa ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteConfirm = false">Cancelar</Button>
                    <Button variant="destructive" @click="confirmDelete">Excluir</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
