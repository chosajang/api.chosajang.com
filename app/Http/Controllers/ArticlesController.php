<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Article;

use App\Http\Controllers\UtilController;

class ArticlesController extends Controller
{
    /**
     * 게시물 목록
     */
    public function articleList() {
        $articleList = DB::table('tb_article as article')
            ->select(
                'article.article_seq',
                'article.title',
                'article.thumbnail_contents',
                'article.use_yn',
                'article.post_yn',
                'article.created_at',
                'article.updated_at',
                'user.id',
                'user.name',
                'user.nickname',
                DB::raw('IFNULL(CONCAT(userFile.path, userFile.physical_name),"") as profile_file_path'),
                DB::raw('IFNULL(CONCAT(file.path, file.physical_name),"") as thumbnail_file_path') )
            ->join('tb_user as user', function($join){
                $join->on('article.user_seq', '=', 'user.user_seq')
                    ->leftjoin('tb_file as userFile', function($userJoin){
                        $userJoin->on('user.profile_file_seq','=','userFile.file_seq')
                            ->where('userFile.use_yn','Y');
                    });
            })
            ->leftjoin('tb_file as file', function($join) {
                $join->on('article.thumbnail_file_seq', '=', 'file.file_seq')
                    ->where('file.use_yn','Y');
            })
            ->where('article.use_yn', 'Y')
            ->orderBy('article.created_at')
            ->get();

        return response()->json([
            'result' => true,
            'data' => $articleList
        ], 200);
    }

    /**
     * 게시물 등록
     */
    public function articleCreate(Request $request){
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:100',
            'contents' => 'required|string',
            'thumbnail_contents' => 'string|max:255',
            'use_yn' => 'required|string|max:1',
            'post_yn' => 'required|string|max:1',
            'thumbnail_image' => 'image|max:1024'
        ]);

        /**
         * 유효성검사 실패 시, 
         */
        if($validator->fails()) {
            return response()->json([
                'result' => false,
                'messages' => $validator->messages()
            ], 401);
        }

        $user = Auth::guard('api')->user();

        $article = new Article;
        $article->fill($request->all());
        $article->user_seq = $user['user_seq'];
        $article->save();

        $utilController = new UtilController;
        $filePath = 'article/' . $article->article_seq . '/';
        $fileUploadResult = $utilController->fileUpload($request, 'thumbnail_image', 'image', $filePath);

        if( $fileUploadResult['result'] ) {
            DB::table('tb_article')
                ->where('article_seq', $article->article_seq)
                ->update([
                    'thumbnail_file_seq' => $fileUploadResult['data']['file_seq']
                ]);
        }

        return response()->json([
            'result' => true,
            'data' => $article
        ], 201);
    }

    /**
     * 게시물 에디터 이미지 업로드
     * todo : 
     * - 에디터에서 받는 양식에 맞게 변경
     * - 게시물이 등록될 때, temp폴더에서 게시물 폴더로 이동 관련 
     */
    public function editorImageUpload(Request $request) {
        $utilController = new UtilController;
        $filePath = 'article/temp/';
        $fileUploadResult = $utilController->fileUpload($request, 'file', 'image', $filePath);
            
        return response()->json([
                $fileUploadResult
            ], 201);
    }

}