<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Http\Controllers\UsersController;

class JWTAuthController extends Controller
{
    /**
     * 회원 가입
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:100|unique:tb_user',
            'name' => 'required|string|max:100',
            'email' => 'required|email:rfc,dns|max:255|unique:tb_user',
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
        ], 201);
    }

    /**
     * 로그인
     */
    public function login(Request $request) {
        /**
         * 비밀번호 규칙
         * - 영문 대문자 포함
         * - 영문 소문자 포함
         * - 숫자 포함
         * - 특수문자 포함
         * - 8자 이상
         * - 255자 이하
         */
        $validator = Validator::make($request->all(), [
            'id' => 'required|string|max:100',
            'password' => 'required|string|min:8|max:255|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%@]).*$/',
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
                'error' => '아이디 또는 비밀번호가 올바르지 않습니다']
            , 401);
        }
    
        return $this->respondWithToken($token);
    }
    
    /**
     * 토큰 갱신
     */
    protected function respondWithToken($token) {
        $usersController = new UsersController;
        $userReadResult = $usersController->userRead( Auth::guard('api')->user()->user_seq );
        return response()->json([
            'result' => true,
            'access_token' => $token,
            'userInfo' => $userReadResult->original['data'],
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60000 /** 토큰 유지시간 */
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
