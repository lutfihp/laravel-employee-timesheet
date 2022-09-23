<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        $employee_role = Role::where('name', 'employee')->first();
        $employee_role->user()->save($user);
        $token = $user->createToken('api_token')->plainTextToken;

        $response = [
            'user' => $user,
            'access_token' => $token
        ];
        return response()->json($response, 201);
    }

    public function login (Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(Auth::attempt($validated))
        {
            $user = User::where('email', $validated['email'])->first();
            $token = $user->createToken('api_token')->plainTextToken;

            $response = [
                'data' => [
                    'access_token' => $token
                ]
            ];
            return response()->json($response);
        }
        else{
            return response()->json(['message' => 'Unauthenticated'], 401);
        }        
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(null, 204);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|min:8'
        ]);

        $user = $request->user();
        $user->password = Hash::make($validated['password']);
        $user->save();
        $user->tokens()->delete();

        $response = [
            'data' => [
                'message' => 'Password change successfully'
            ]
        ];
        return response()->json($response, 200);
    }
}
