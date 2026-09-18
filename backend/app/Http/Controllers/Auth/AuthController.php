<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response as OpenApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

#[Group('Autenticação', weight: 1)]
class AuthController extends Controller
{
    /**
     * Cadastra um usuário e devolve um token Sanctum.
     */
    #[OpenApiResponse(201, type: 'array{user: UserResource, token: string}')]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only(['name', 'email', 'password']));

        return response()->json([
            'user' => UserResource::make($user)->resolve(),
            'token' => $user->createToken('auth')->plainTextToken,
        ], Response::HTTP_CREATED);
    }

    /**
     * Autentica o usuário e devolve um token Sanctum.
     */
    #[OpenApiResponse(200, type: 'array{user: UserResource, token: string}')]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'user' => UserResource::make($user)->resolve(),
            'token' => $user->createToken('auth')->plainTextToken,
        ]);
    }

    /**
     * Revoga o token atual.
     */
    public function logout(Request $request): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }

    /**
     * Devolve o usuário autenticado.
     */
    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
