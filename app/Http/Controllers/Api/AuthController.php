<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Traits\ResponseTrait;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->userRepository->createUser($request->validated());

        $token = JWTAuth::fromUser($user);

        return $this->sendResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 'User registered successfully.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return $this->sendError('Invalid email or password.', [], 401);
        }

        $user = Auth::guard('api')->user();

        return $this->sendResponse([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 'Login successful.');
    }

    public function logout(): JsonResponse
    {
        try {
            Auth::guard('api')->logout();
        } catch (JWTException $e) {
            return $this->sendError('Failed to logout, please try again.', [], 500);
        }

        return $this->sendResponse(null, 'Successfully logged out.');
    }

    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (! $user) {
            return $this->sendError('Unauthenticated.', [], 401);
        }

        return $this->sendResponse($user, 'User profile retrieved successfully.');
    }
}
