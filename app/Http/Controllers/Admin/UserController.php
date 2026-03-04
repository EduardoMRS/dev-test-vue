<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List all users with optional search/pagination.
     */
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"))
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id'                    => $u->id,
                'name'                  => $u->name,
                'email'                 => $u->email,
                'role'                  => $u->role,
                'active'                => $u->active,
                'two_factor_enabled'    => ! is_null($u->two_factor_secret),
                'email_verified_at'     => $u->email_verified_at,
                'created_at'            => $u->created_at,
            ]);

        return Inertia::render('admin/Users', [
            'users'   => $users,
            'filters' => $request->only(['search', 'role']),
            'roles'   => [User::ROLE_USER, User::ROLE_MODERATOR, User::ROLE_ADMIN],
        ]);
    }

    /**
     * Store a new user created by an admin.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role ?? User::ROLE_USER,
            'active'   => true,
        ]);

        return back()->with('success', 'Usuário criado com sucesso.');
    }

    /**
     * Update user role / active status / name / email.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return back()->with('success', 'Usuário atualizado.');
    }

    /**
     * Delete a user (admin cannot delete themselves).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Você não pode excluir sua própria conta.']);
        }

        $user->delete();

        return back()->with('success', 'Usuário removido.');
    }

    /**
     * Send a password reset link to the user's e-mail.
     */
    public function sendPasswordReset(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', "Link de redefinição enviado para {$user->email}.");
        }

        return back()->withErrors(['email' => __($status)]);
    }

    /**
     * Force-change (reset) the user's password directly without confirmation.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => $request->password]);

        return back()->with('success', 'Senha redefinida com sucesso.');
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disableTwoFactor(User $user): RedirectResponse
    {
        $user->forceFill([
            'two_factor_secret'           => null,
            'two_factor_recovery_codes'   => null,
            'two_factor_confirmed_at'     => null,
        ])->save();

        return back()->with('success', "Autenticação de dois fatores desabilitada para {$user->name}.");
    }

    /**
     * Toggle the active/inactive status of the user.
     */
    public function toggleActive(User $user, Request $request): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Você não pode desativar sua própria conta.']);
        }

        $user->update(['active' => ! $user->active]);

        $status = $user->active ? 'ativado' : 'desativado';

        return back()->with('success', "Usuário {$status} com sucesso.");
    }
}
