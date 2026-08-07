<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/login',
        tags: ['Auth'],
        summary: 'Autentica un utente e restituisce un token Bearer',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token e profilo utente', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 422, description: 'Credenziali non valide'),
        ],
    )]
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
            'password.required' => 'La password è obbligatoria.',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Le credenziali inserite non sono valide.',
                'error' => 'invalid_credentials',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'token' => $user->createToken('api', [$user->role->value])->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    #[OA\Post(
        path: '/auth/register',
        tags: ['Auth'],
        summary: 'Registra un nuovo utente (ruolo user)',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Token e profilo utente', content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')),
            new OA\Response(response: 422, description: 'Validazione fallita'),
        ],
    )]
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Il nome è obbligatorio.',
            'name.max' => 'Il nome non può superare :max caratteri.',
            'email.required' => "L'email è obbligatoria.",
            'email.email' => "L'email non è valida.",
            'email.max' => "L'email non può superare :max caratteri.",
            'password.required' => 'La password è obbligatoria.',
            'password.min' => 'La password deve avere almeno :min caratteri.',
            'password.confirmed' => 'Le password non coincidono.',
        ]);

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => Role::User->value,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'Questa email è già registrata.',
                'error' => 'email_taken',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'token' => $user->createToken('api', [$user->role->value])->plainTextToken,
            'user' => new UserResource($user),
        ], JsonResponse::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/auth/me',
        tags: ['Auth'],
        summary: "Profilo dell'utente autenticato",
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Profilo utente', content: new OA\JsonContent(ref: '#/components/schemas/User')),
        ],
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    #[OA\Post(
        path: '/auth/logout',
        tags: ['Auth'],
        summary: 'Revoca il token corrente',
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout effettuato'),
        ],
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout effettuato.',
        ]);
    }
}
