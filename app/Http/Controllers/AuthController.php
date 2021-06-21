<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request as Request;
use Illuminate\Http\Response;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Arr;

use Exception;
use App\Http\CommonLib;
use App\Models\tb_user;

class AuthController extends BaseController
{

    public function login(Request $request): array
    {
        $result = array();

        $id = $request->input('id');
        $password = $request->input('password');

        // 회원 정보 조회
        $user = tb_user::select('user_seq','id','name','nickname','email','profile_file_seq','password','remember_token')
                    ->where('id', $id)->first();
        
        if (!Hash::check($password, $user->password)) {
            $result['result'] = false;
            $result['message'] = '아이디 또는 비밀번호가 올바르지 않습니다';

            return $result;
        }
        
        // 로그인 진행
        // Auth::login($user, true);
        $bearerToken = $user->createToken('token-name', ['server:update'])->plainTextToken;

        $result['result'] = true;
        $result['data'] = $user->toArray();
        $result['data']['bearerToken'] = $bearerToken;

        return $result;
    }

    public function loginCheck(Request $request): array
    {
        $result = array();
        $result["result"] = true;
        
        if(Auth::check()){
            $result["status"] = "로그인 중";
        }else{
            $result["status"] = "로그아웃 중";
        }
        return $result;
    }


    public function logout(Request $request): array
    {
        $result = array();
        // $request->user()->currentAccessToken()->delete();
        Auth::user()->tokens->each(function($token, $key) {
            $token->delete();
        });

        $result['result'] = true;
        $result['message'] = '로그아웃되었습니다';
        return $result;
    }


}
