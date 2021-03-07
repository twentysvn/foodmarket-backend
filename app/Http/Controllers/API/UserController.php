<?php

namespace App\Http\Controllers\API;

use App\Actions\Fortify\PasswordValidationRules;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use PasswordValidationRules;

    public function login(Request $request)
    {
        try {
            //validasi
            $request->validate([
                'email' => 'email|required',
                'password' => 'required'
            ]);



            //cek credensial
            $creds = request(['email', 'password']);
            if (!Auth::attempt($creds)) {
                return ResponseFormatter::error([
                    'msg' => 'Unauthorized'
                ], "Auth Failed", 500);
            }



            // jika hash tidak sesuai, kirim error
            $user = User::where('email', $request->email)->first();




            if (!Hash::check($request->password, $user->password, [])) {
                throw new Exception("Invalid creds");
            }



            // login-kan
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Login Sukses');
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'msg' => 'something went wrong',
                'error' => $e
            ], 'Auth failed', 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules()
            ]);

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'houseNumber' => $request->houseNumber,
                'phoneNumber' => $request->phoneNumber,
                'city' => $request->city,
                'password' => Hash::make($request->password)
            ]);

            // cek apa sudah ada
            $user = User::where('email', $request->email)->first();
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Register Sukses');
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'Error', 500);
        }
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token, 'Token Revoked');
    }


    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(), 'Data Profile User Berhasil Diambil');
    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Updated');
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:2048'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error([
                'error' => $validator->errors()
            ], 'Update photo failed', 401);
        }

        if ($request->file('file')) {
            $file = $request->file->store('assets/user/', 'public');

            // simpan foto ke db (url nya)
            $user = Auth::user();
            $user->profile_photo_url = $file;
            $user->update();

            return ResponseFormatter::success([$file], 'File successfully uploaded');
        }
    }


    public function getAll()
    {
        try {
            $users = User::all();
            return ResponseFormatter::success(
                $users,
                'Berhasil mengambil data'
            );
        } catch (Exception $e) {
            return ResponseFormatter::error([
                'error' => $e->getMessage()
            ], 'Gagal mengambil data', 500);
        }
    }
}
