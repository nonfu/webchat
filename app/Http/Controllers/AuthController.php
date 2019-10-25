<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->only('logout');
    }

    public function register(Request $request) 
    {
        // 验证注册字段
        Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6']
        ])->validate();

        // 在数据库中创建用户并返回
        return User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'api_token' => Str::random(60)
        ]);
    }

    public function login(Request $request) 
    {
        // 验证登录字段
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');
        $user = User::where('email', $email)->first();
        // 用户校验成功则返回 Token 信息
        if ($user && Hash::check($password, $user->password)) {
            $user->api_token = Str::random(60);
            $user->save();
            return response()->json(['user' => $user, 'success' => true]);
        }

        return  response()->json(['success' => false]);
    }

    public function logout(Request $request) 
    {
        $user = Auth::guard('auth:api')->user();
        $userModel = User::find($user->id);
        $userModel->api_token = null;
        $userModel->save();
        return response()->json(['success' => true]);
    }
}