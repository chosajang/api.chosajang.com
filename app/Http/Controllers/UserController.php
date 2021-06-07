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
class UserController extends BaseController
{

    public function info($user_seq): array
    {
        $result = array();
        $userInfo = DB::table('tb_user')
                      ->where(['user_seq'=>$user_seq])
                      ->first();
        $result["data"] = $userInfo;
        $result["result"] = true;

        return $result;
    }

}
