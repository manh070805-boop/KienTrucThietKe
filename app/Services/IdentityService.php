<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class IdentityService
{
    public function login(Request $request, array $data): User
    {
        abort_unless(Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => true]), 401, 'Email hoặc mật khẩu không đúng.');
        $request->session()->regenerate();

        return $request->user();
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
