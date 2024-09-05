<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Interfaces\AuthInterface;
use App\Models\User;
use App\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private AuthInterface $authInterface;

    public function __construct(AuthInterface $authInterface)
    {
        $this->authInterface = $authInterface;
    }

    public function register(RegisterRequest $registerRequest)
    {
        $data = [
            'name' => $registerRequest->name,
            'email' => $registerRequest->email,
            'password' => $registerRequest->password
        ];

        DB::beginTransaction();
        try {
            $user = $this->authInterface->register($data);

            DB::commit();

            return ApiResponse::sendResponse(
                true, 
                [new UserResource($user)], 
                'Opération effectuée.', 
                201
            );

        } catch (\Throwable $th) {
            // return $th;
            return ApiResponse::rollback($th);
        }
    }

    public function login(LoginRequest $loginRequest)
    {
        $data = [
            'email' => $loginRequest->email,
            'password' => $loginRequest->password
        ];

        DB::beginTransaction();
        try {
            $user = $this->authInterface->login($data);

            DB::commit();

            return ApiResponse::sendResponse(
                // true, 
                // [new UserResource($user)], 
                // 'Connexion réussie.', 
                // 201
                $user, 
                [], 
                'Connexion réussie.', 
                $user ? 200 : 401
            );

        } catch (\Throwable $th) {
            return $th;
            return ApiResponse::rollback($th);
        }
    }

    public function logout()
    {
        $user = User::find(auth()->user()->getAuthIdentifier());

        $user->tokens()->delete();

        return ApiResponse::sendResponse(
            true, 
            [], 
            'Utilisateur connecter.', 
            $user ? 200 : 401
        );

    }

    public function checkOtpCode(Request $request)
    {
        $data = [
            'email' => $request->email,
            'code' => $request->code,
        ];

        DB::beginTransaction();
        try {
            $user = $this->authInterface->checkOtpCode($data);

            if (!$user) {
                return ApiResponse::sendResponse(
                    // true, 
                    // [new UserResource($user)], 
                    // 'Connexion réussie.', 
                    // 201
                    false, 
                    [], 
                    'Code confirmation invalide.', 
                    $user ? 200 : 401
                );
            }

            return ApiResponse::sendResponse(
                true, 
                [new UserResource($user)], 
                'Opération effettuee.', 
                $user ? 200 : 401
            );

            DB::commit();

        } catch (\Throwable $th) {
            return $th;
            return ApiResponse::rollback($th);
        }
    }
}
