<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Rules\Password;
use PhpParser\Node\Stmt\TryCatch;

class UserController extends Controller
{
    //login
    public function login(Request $request)
    {
        try {
            // Validate Request
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            //Find user by email
            $credentials = request(['email', 'password']);
            if (!Auth::attempt($credentials)) {
                return ResponseFormatter::error('Unauthorized', 401);
            }

            $user = User::where('email', $request->email)->first(); //intinya nampilin data user yang emailnya sudah kita validasi

            //ngecek password udah sesuai apa belom
            if (!Hash::check($request->password, $user->password)) {
                throw new Exception('Invalid Password');
            }

            //Generate Token
            //kita pakai dari sanctum create token
            $toketResult = $user->createToken('authToken')->plainTextToken;

            //Return Response
            //ngembaliiin data response token
            return ResponseFormatter::success([
                'access_token' => $toketResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Login Success');
        } catch (Exception $error) {
            return ResponseFormatter::error('Authentication Failed');
        }
    }

    //register
    public function register(Request $request)
    {
        try {
            //Validate Request
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'nim' => ['string', 'max:255'],
                'roles' => ['required', 'string', 'max:255'],
                'program_name' => ['string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', new Password]
            ]);

            //Create User
            $user = User::create([
                'name' => $request->name,
                'nim' => $request->nim,
                'email' => $request->email,
                'program_name' => $request->program_name,
                'roles' => $request->roles,
                'password' => Hash::make($request->password),
            ]);

            //Generate Token
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            //Return Response
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'Register Success');
        } catch (Exception $error) {
            //Retrun error Response
            return ResponseFormatter::error($error->getMessage());
        }
    }

    public function logout(Request $request)
    {
        // Revoke Token
        $token = $request->user()->currentAccessToken()->delete();

        // Return Response
        return ResponseFormatter::success($token, 'Logout success');
    }


    // Ngambilin data user
    //cuman ngambil data user
    // harus login dulu
    public function fetch(Request $request)
    {
        // Get user
        $user = $request->user();

        // Return Response
        return ResponseFormatter::success($user, 'Fetch Success');
    }

    public function getUser(Request $request)
    {

        $id = $request->input('id');
        $name = $request->input('name');

        $userQuery = User::query();

        //Get single data by id
        if ($id) {
            $user = $userQuery->find($id);

            if ($user) {
                return ResponseFormatter::success($user, 'user found');
            }

            return ResponseFormatter::error('student not found', 404);
        }

        //get all user
        $user = User::all();

        //return Response
        return ResponseFormatter::success($user, 'Fetch Success');
    }
}
