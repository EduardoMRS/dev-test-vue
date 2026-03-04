<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TokenController extends Controller
{
    /**
     * Issue a Sanctum personal access token via email + password.
     *
     * Utilizado por SPAs externas ou apps nativos para obter um Bearer token
     * sem passar pelo fluxo de sessão Fortify.
     *
     * POST /api/auth/token
     * Body: { email, password, name? (token label), abilities? (array) }
     */
    public function issue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'     => ['required', 'email'],
            'password'  => ['required', 'string'],
            'name'      => ['sometimes', 'string', 'max:255'],
            'abilities' => ['sometimes', 'array'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        // Revogar tokens anteriores com o mesmo nome para evitar acúmulo
        $tokenName = $data['name'] ?? 'api-token';
        $user->tokens()->where('name', $tokenName)->delete();

        $abilities = $data['abilities'] ?? ['*'];
        $token = $user->createToken($tokenName, $abilities);

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities'  => $abilities,
        ], 201);
    }

    /**
     * Issue a Sanctum personal access token for the currently authenticated
     * session user (Fortify web session).
     *
     * Utilizado pelo front-end SPA logo após o login Fortify para obter um
     * Bearer token que as chamadas axios usarão nas rotas da API.
     *
     * POST /auth/token/session  (rota web — autenticada via sessão)
     */
    public function issueForSession(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Revogar token de sessão anterior para evitar acúmulo
        $user->tokens()->where('name', 'session-token')->delete();

        $token = $user->createToken('session-token', ['*']);

        return response()->json([
            'token'      => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Revoke the current Bearer token.
     *
     * DELETE /api/auth/token  (requer auth:sanctum)
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revogado com sucesso.']);
    }
}
