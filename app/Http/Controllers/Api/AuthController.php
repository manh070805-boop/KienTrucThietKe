<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::where('email', $data['email'])->first();
        if (!$user || !$user->is_active || !Auth::attempt(['email' => $data['email'], 'password' => $data['password'], 'is_active' => true])) {
            return response()->json(['message' => 'Email hoặc mật khẩu không đúng.'], 401);
        }
        $request->session()->regenerate();
        return response()->json(['data' => $this->userData($request->user())]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $this->userData($request->user())]);
    }

    private function userData(User $user): array
    {
        return ['id' => $user->id, 'full_name' => $user->displayName(), 'email' => $user->email, 'student_code' => $user->student_code, 'roles' => $user->roles()->pluck('code')];
    }
}
