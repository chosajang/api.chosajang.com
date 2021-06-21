<?php
namespace App\Http;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use App\Models\tb_user;
use Illuminate\Http\Request;

class CommonLib
{
    /**
     * todo : 
     * - 회원 정보 중 user_seq로 조회하여 사용중인(use_yn) 회원인지 확인
     * - 회원 정보 중 tokenLifeTime을 확인하여 토큰 유지기간 확인
     */
    static function auth_check(){
        return request()->user() ? true : false;
    }
    
    static function passwordCheck($_str)
    {
        $pw     =   $_str;
        $num    =   preg_match('/[0-9]/u', $pw);
        $eng    =   preg_match('/[a-z]/u', $pw);
        $spe    =   preg_match("/[\!\@\#\$\%\^\&\*]/u", $pw);
 
        if (strlen($pw) < 9 || strlen($pw) > 20) {
            return array("result"=>false,"message"=>"비밀번호는 영문, 숫자, 특수문자를 혼합하여 최소 10자리 ~ 최대 20자리 이내로 입력해주세요.");
            exit;
        }
 
        if (preg_match("/\s/u", $pw) == true) {
            return array("result"=>false, "message"=>"비밀번호는 공백없이 입력해주세요.");
            exit;
        }
 
        if ($num == 0 || $eng == 0 || $spe == 0) {
            return array("result"=>false, "message"=>"영문자, 숫자, 특수문자를 혼합하여 입력해주세요.");
            exit;
        }
 
        return array("result"=>true, "message"=>"");
    }

    /** 
    *   오류참고                 
    *   1.trycatch 오류         -   0.공통  -   0.공통  -   100	
    *   2.json 키값 누락        -   0.공통  -   0.공통  -   200	
    *   3.json 데이터값 누락    -   0.공통  -   0.공통  -   300	                
    *   4.중복오류              
    *           -   1.아이디    -   0.공통  -   410	        
    *           -   1.아이디    -   1.기업  -   411	
    *           -   1.아이디    -   2.기업 관리자   -   412	
    *           -   2.전화번호  -   0.공통  -   420	
    *           -   3.이메일    -   0.공통  -   430	
    *   5.수정/상세정보시 해당 데이터 존재안함   -   0.공통  -   0.공통  -   500	
    *   6.로그인계정company_seq존재안할시   -   0.공통  -   0.공통  -   600	
    */
    static function errorCode($code){
        $result             =   array();
        $result['result']   =   false;

        switch($code){
            case "100" : $result['message'] = '요청값이 정확하지 않습니다. 개발팀에 문의해주세요.'; break;

            case "200" : $result['message'] = '요청이 정확하지 않습니다. 정보를 정확히 입력하였는지 확인해주세요.'; break;
            case "300" : $result['message'] = '요청이 정확하지 않습니다. 정보를 정확히 입력하였는지 확인해주세요.'; break;

            case "410" : $result['message'] = '해당 아이디가 이미 존재합니다.'; break;
            case "411" : $result['message'] = '해당 기업 아이디가 이미 존재합니다.'; break;
            case "412" : $result['message'] = '해당 기업 관리자 아이디가 이미 존재합니다.'; break;
            case "413" : $result['message'] = '해당 기업 관리자 이메일이 이미 존재합니다.'; break;

            case "420" : $result['message'] = '해당 전화번호가 이미 존재합니다.'; break;
            case "430" : $result['message'] = '해당 이메일이 이미 존재합니다.'; break;

            case "500" : $result['message'] = '정보가 존재하지 않습니다. 개발팀에 문의해주세요.'; break;

            case "600" : $result['message'] = '해당 기업에 접근권한이 없습니다. 확인 후 다시 로그인해주세요.'; break;
            case "700" : $result['message'] = '탈퇴한 회원입니다. 로그인 할 수 없습니다.'; break;
            case "701" : $result['message'] = '인증된 회원이 아닙니다 본인인증 후, 다시 시도해주세요.'; break;
            case "702" : $result['message'] = '요청 기업의 관리자 권한이 없습니다.'; break;
            default : $result['message']    = '요청값이 정확하지 않습니다. 개발팀에 문의해주세요.'; break;
        };
        return $result;
    }

    static function filePath(){
        return 'https://static.chosajang.com/blog/';
    }
}