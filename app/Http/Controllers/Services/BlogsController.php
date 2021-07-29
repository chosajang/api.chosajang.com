<?php

namespace App\Http\Controllers\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Article;

use App\Http\Controllers\UtilController;
use App\Http\Controllers\Controller;

class BlogsController extends Controller
{
    /**
     * 게시물 목록
     */
    public function articleList() {
        $articleList = DB::table('tb_article as article')
            ->select(
                'article.article_seq',
                'article.title',
                'article.description',
                'article.use_yn',
                'article.post_yn',
                'article.created_at',
                'article.updated_at',
                'user.id as user_id',
                'user.name as user_name',
                'user.nickname as user_nickname',
                'user.comment as user_comment',
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", userFile.path, userFile.physical_name),"") as user_image_url'),
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", file.path, file.physical_name),"") as thumbnail_url') )
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
            ->where('article.post_yn', 'Y')
            ->orderByDesc('article.created_at')
            ->get();

        return response()->json([
            'articleList' => $articleList
        ], count($articleList) > 0 ? 200 : 204);
    }

    /**
     * 게시물 조회
     */
    public function articleRead($article_seq) {
        $article = DB::table('tb_article as article')
            ->select(
                'article.article_seq',
                'article.title',
                'article.description',
                'article.contents',
                'article.use_yn',
                'article.post_yn',
                'article.created_at',
                'article.updated_at',
                'user.id as user_id',
                'user.name as user_name',
                'user.nickname as user_nickname',
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", userFile.path, userFile.physical_name),"") as user_image_url'),
                'article.thumbnail_file_seq',
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", file.path, file.physical_name),"") as thumbnail_url') )
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
            ->where('article.post_yn', 'Y')
            ->where('article.article_seq', $article_seq)
            ->first();
        
        return response()->json([
            'article' => $article
        ], $article != null ? 200 : 204);
    }

}