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
        $user = DB::table('tb_user as tu')
                      ->select('tu.user_seq','tu.id','tu.name','tu.nickname','tu.email','tu.tel','tu.comment','tu.add_date','tu.mod_date','tu.profile_file_seq')
                      ->where(['user_seq'=>$user_seq])
                      ->get();
        $result["data"] = $user;
        $result["result"] = true;

        return $result;
    }

}
