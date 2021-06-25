<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class UsersController extends Controller
{

    /**
     * 회원 목록
     */
    public function userList() {
        $list = DB::table('tb_user')
            ->select('user_seq','id','name','nickname','email')
            ->orderBy('created_at')
            ->get();
        
        $result = array();
        $result['result'] = true;
        $result['data'] = $list;

        return response()->json($result, 201);
    }

    /**
     * 회원 정보 조회
     */
    public function userInfo($user_seq) {
        $user = DB::table('tb_user')
            ->select('user_seq','id','name','nickname','email','tel','comment')
            ->where('user_seq', $user_seq)
            ->first();

        $result = array();
        $result['result'] = true;
        $result['data'] = $user;

        return response()->json($result, 201);
    }

    /**
     * 회원 정보 수정
     * todo : 쿼리 빌더 추가
     */
    public function userUpdate() { 

    }

}
