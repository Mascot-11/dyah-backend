<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use App\Notifications\CustomResetPasswordNotification;

class UserAuthController extends Controller
{
    // Login method
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials, Please Try Again',
            ], 401);
        }
        $user->tokens->each(function ($token) {
            $token->delete();
         });


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'role_message' => $this->getRoleMessage($user->role),
        ]);
    }


    private function getRoleMessage($role)
    {
        return match ($role) {
            'admin' => 'Admin login successful',
            'tattoo_artist' => 'Tattoo artist login successful',
            default => 'User login successful',
        };
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255,unique:users',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:tattoo_artist,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Email Already Exists',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);


        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Signup successful',
        ]);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Password::createToken($user);
            $user->notify(new CustomResetPasswordNotification($user, $token));

            return response()->json([
                'message' => 'Password reset link sent to your email address.',
            ], 200);
        }

        return response()->json([
            'message' => 'Unable to send reset link.',
        ], 500);
    }


    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->password = Hash::make($request->password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password successfully reset.'], 200)
            : response()->json(['message' => 'Failed to reset password.'], 500);
    }


    public function users()
    {
        if (!in_array(auth()->user()->role, ['admin'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(User::all());
    }


    public function createUser(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,tattoo_artist,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json($user, 201);
    }

   public function updateUser(Request $request, $id)
{

    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }


    $user = User::find($id);


    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }


    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:users,email,' . $id,
        'password' => 'nullable|string|min:8',
        'role' => 'nullable|string|in:user,admin,tattoo_artist',
    ]);


    $user->update([
        'name' => $validated['name'] ?? $user->name,
        'email' => $validated['email'] ?? $user->email,
        'password' => isset($validated['password']) ? bcrypt($validated['password']) : $user->password,
        'role' => $validated['role'] ?? $user->role,
    ]);


    return response()->json($user);
}



    public function deleteUser($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }
    public function changePassword(Request $request)
{
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Check if current password matches
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'message' => 'Current password is incorrect.',
        ], 401);
    }

    // Update password
    $user->password = Hash::make($request->new_password);
    $user->save();

    return response()->json([
        'message' => 'Password changed successfully.',
    ], 200);
}
// Get authenticated user's profile
public function profile()
{
    $user = auth()->user();

    return response()->json($user);
}

// Update authenticated user's profile
public function updateProfile(Request $request)
{
    $user = auth()->user();

    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:users,email,' . $user->id,
        // You can add other fields here if you want
    ]);

    $user->update($validated);

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => $user,
    ]);
}

}


