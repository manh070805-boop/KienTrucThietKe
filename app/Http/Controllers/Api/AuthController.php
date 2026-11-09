<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\IdentityService;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function login(LoginRequest $r, IdentityService $identity)
    {
        return response()->json(['data' => $this->userData($identity->login($r, $r->validated()))]);
    }

    public function logout(Request $r, IdentityService $identity)
    {
        $identity->logout($r);

        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    public function me(Request $r)
    {
        return response()->json(['data' => $this->userData($r->user())]);
    }

    private function userData(User $user): array
    {
        return ['id' => $user->id, 'full_name' => $user->displayName(), 'email' => $user->email, 'student_code' => $user->student_code, 'roles' => $user->roles->pluck('code')];
    }
}
