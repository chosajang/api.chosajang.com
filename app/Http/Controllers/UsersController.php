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
     */
    public function userUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_seq' => 'required|numeric|max:100',
            'password' => 'required|string|min:8|max:255',
            'name' => 'required|string|max:100',
            'nickname' => 'required|string|max:100',
            'tel' => 'required|string|max:14',
            'email' => 'required|email|max:255|unique:users',
        ]);

        $user_seq = $request->input('user_seq');
        /**
         * 유효성검사 실패 시, 
         */
        if($validator->fails()) {
            return response()->json([
                'result' => false,
                'messages' => $validator->messages()
            ], 401);
        }
        /**
         * 비밀번호 확인
         */
        if (! $token = Auth::guard('api')->attempt(['user_seq' => $request->user_seq, 'password' => $request->password])) {
            return response()->json([
                'result' => false,
                'error' => 'Unauthorized']
            , 401);
        }

        $userData = array();
        $userData['name'] = $request->input('name');
        $userData['nickname'] = $request->input('nickname');
        $userData['tel'] = $request->input('tel');
        $userData['email'] = $request->input('email');


        DB::table('tb_user')
        ->where('user_seq', $user_seq)
        ->update($userData);

        $result = array();
        $result['result'] = true;
        $result['data'] = $userData;

        return response()->json($result, 201);
    }

}
