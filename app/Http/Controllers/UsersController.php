<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use App\Models\User;

use App\Http\Controllers\UtilController;

class UsersController extends Controller
{

    /**
     * 회원 목록
     */
    public function userList() {
        $list = DB::table('tb_user as user')
            ->select(
                'user.user_seq',
                'user.id',
                'user.name',
                'user.nickname',
                'user.email',
                'user.profile_file_seq',
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", file.path, file.physical_name),"") as profile_image_url'),
                'user.created_at',
                'user.updated_at' )
            ->leftjoin('tb_file as file', function($join) {
                $join->on('user.profile_file_seq', '=', 'file.file_seq')
                    ->where('file.use_yn','Y');
            })
            ->orderByDesc('user.created_at')
            ->get();
        
        $result = array();
        $result['result'] = true;
        $result['data'] = $list;

        return response()->json($result, 201);
    }

    /**
     * 회원 정보 조회
     */
    public function userRead($user_seq) {
        $user = DB::table('tb_user as user')
            ->select(
                'user.user_seq',
                'user.id',
                'user.name',
                'user.nickname',
                'user.email',
                'user.tel',
                'user.comment',
                'user.profile_file_seq',
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/",file.path, file.physical_name),"") as profile_image_url'),
                'user.created_at',
                'user.updated_at' )
            ->leftjoin('tb_file as file', function($join) {
                $join->on('user.profile_file_seq', '=', 'file.file_seq')
                    ->where('file.use_yn','Y');
            })
            ->where('user.user_seq', $user_seq)
            ->first();

        $result = array();
        $result['result'] = true;
        $result['data'] = $user;

        return response()->json($result, $user != null ? 200 : 204);
    }

    /**
     * 회원 정보 수정
     */
    public function userUpdate(Request $request) {
        $validator = Validator::make($request->all(), [
            'user_seq' => 'required|numeric',
            'password' => 'required|string|min:8|max:255|confirmed',
            'password_confirmation' => 'required|string|min:8|max:255',
            'name' => 'required|string|max:100',
            'nickname' => 'required|string|max:100',
            'tel' => 'required|string|max:14',
            'email' => 'required|email|max:255|unique:tb_user',
        ]);

        /**
         * 유효성검사 실패 시, 
         */
        if($validator->fails()) {
            return response()->json([
                'result' => false,
                'messages' => $validator->messages()
            ], 400);
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
            ->where('user_seq', $request->user_seq)
            ->update($userData);

        $result = array();
        $result['result'] = true;
        $result['data'] = $userData;

        return response()->json($result, 200);
    }

    /**
     * 프로필 이미지 업로드
     */
    public function profileImageUpload(Request $request) {
        $utilController = new UtilController;
        $filePath = 'user/' . $request->user_seq . '/';
        $request->merge( array( 'upload_user_seq' => $request->user_seq ) );
        $fileUploadResult = $utilController->fileUpload($request, 'file', 'image', $filePath, '');
        
        if( $fileUploadResult['result'] ) {
            /**
             * 회원 테이블에 업데이트
             */
            DB::table('tb_user')
                ->where('user_seq', $request->user_seq )
                ->update([
                    'profile_file_seq' => $fileUploadResult['data']['file_seq']
                ]);
            
            return response()->json([
                    $fileUploadResult
                ], 201);
        } else {
            return response()->json([
                    'result' => false,
                    'messages' => $fileUploadResult['messages']
                ], $fileUploadResult['status_code']);
        }
    }

}


