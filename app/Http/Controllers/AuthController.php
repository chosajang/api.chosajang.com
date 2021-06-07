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
        $loginResult = array();

        $id = $request->input('id');
        $password = $request->input('password');

        // 회원 정보 조회
        $user = tb_user::select('user_seq','id','name','nickname','email','profile_file_seq','password','remember_token')
                    ->where('id', $id)->first();
        
        if (!Hash::check($password, $user->password)) {
            $loginResult['result'] = false;
            $loginResult['message'] = '아이디 또는 비밀번호가 올바르지 않습니다';

            return $loginResult;
        }
        
        // 로그인 진행
        // Auth::login($user, true);
        $bearerToken = $user->createToken('token-name', ['server:update'])->plainTextToken;

        $loginResult['result'] = true;
        $loginResult['data'] = $user->toArray();
        $loginResult['data']['bearerToken'] = $bearerToken;

        return $loginResult;
    }


    public function logout(Request $request): array
    {
        $logoutResult = array();
        $request->user()->currentAccessToken()->delete();

        //Auth::logout();
        $logoutResult['result'] = true;
        $logoutResult['message'] = '로그아웃되었습니다';
        return $logoutResult;
    }


}
