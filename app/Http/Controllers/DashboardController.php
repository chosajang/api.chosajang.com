<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Article;

use App\Http\Controllers\UtilController;

class DashboardController extends Controller
{

    /**
     * 대시보드
     * - 회원 수
     * - 작성 글 개수
     * - 최근 작업일
     * - 발행한 게시글 최근 5개
     * - 미발행 게시글 최근 5개
     */
    public function dashboard() {

        $obj = new DashboardController;

        $result = array();
        $result['result'] = true;
        $result['data'] = [
            'userCount' => $obj->userCount(),
            'articleCount' => $obj->articleCount(),
            'latestWorkingDate' => $obj->latestWorkingDate(),
            'latestPostingArticleList' => $obj->latestArticleList('Y'),
            'latestWorkingArticleList' => $obj->latestArticleList('N')
        ];

        return response()->json($result, 200);
    }

    /**
     * 회원 수
     */
    private function userCount() {
        $userCount = DB::table('tb_user')->count();
        return $userCount;
    }

    /**
     * 게시물 수
     */
    private function articleCount() {
        $articleCount = DB::table('tb_article')->where('use_yn','Y')->count();
        return $articleCount;
    }

    /**
     * 최근 작업일
     */
    private function latestWorkingDate() {
        $latest_date = DB::table('tb_article')->selectRaw("DATE_FORMAT(updated_at,'%Y-%m-%d')as updated_at")->orderByDesc('updated_at')->first();
        return $latest_date->updated_at;
    }

    /**
     * 발행중인 최근 게시물
     */
    private function latestArticleList($post_yn) {
        $articleList = DB::table('tb_article as article')
            ->select(
                'article.article_seq',
                'article.title',
                'article.description',
                'article.use_yn',
                'article.post_yn',
                'user.id as user_id',
                'user.name as user_name',
                'user.nickname as user_nickname',
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", userFile.path, userFile.physical_name),"") as user_image_url'),
                DB::raw('IFNULL(CONCAT("' . env('IMAGE_URL') . '/", file.path, file.physical_name),"") as thumbnail_url') )
            ->selectRaw("DATE_FORMAT(article.created_at,'%Y-%m-%d')as created_at")
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
            ->where('article.post_yn', $post_yn)
            ->orderByDesc('article.created_at')
            ->limit(5)
            ->get();

        return $articleList;
    }

}


