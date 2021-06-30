<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class JWTAuthController extends Controller
{
    /**
     * 회원 가입
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:100',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string|min:8|max:255',
        ]);

        if($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'messages' => $validator->messages()
            ], 200);
        }

        $user = new User;
        $user->fill($request->all());
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json([
            'result' => true,
            'data' => $user
        ], 200);
    }

    /**
     * 로그인
     */
    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:100',
            'password' => 'required|string|min:8|max:255',
        ]);
    
        if($validator->fails()) {
            return response()->json([
                'result' => false,
                'messages' => $validator->messages()
            ], 401);
        }
    
        if (! $token = Auth::guard('api')->attempt(['id' => $request->id, 'password' => $request->password])) {
            return response()->json([
                'result' => false,
                'error' => 'Unauthorized']
            , 401);
        }
    
        return $this->respondWithToken($token);
    }
    
    /**
     * 토큰 갱신
     */
    protected function respondWithToken($token) {
        return response()->json([
            'result' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 360 /** 토큰 유지시간 */
        ]);
    }

    /**
     * 토큰 갱신
     */
    public function refresh() {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    /**
     * 현재 로그인한 회원 정보
     */
    public function user() {
        return response()->json([
            'result' => true,
            'data' => Auth::guard('api')->user()
        ], 201);
    }

    /**
     * 로그아웃
     */
    public function logout() {
        Auth::guard('api')->logout();
    
        return response()->json([
            'result' => true,
            'message' => 'logout'
        ], 200);
    }
}
