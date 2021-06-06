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
class AuthController extends BaseController
{

    public function login(Request $request): array
    {
        // 로그인 진행
        Auth::login($user, true);

        $loginResult['result'] = true;
        $userInfo = $user->toArray();
        $userInfo['auth'] = true;
        $loginResult['data'] = $userInfo;

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
