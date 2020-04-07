<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 */
class Page extends CI_Controller {

    public $function_prefix = '';
    public $callback;

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_page_';
        
        $this->load->library('email');
        // Model Load
        $this->load->model('site/projectModel','projectModel');
        $this->load->model('articleModel');
        $this->load->model('memberModel');
        $this->load->model('boardModel');
    }

    /** 
     * remap
     */
    public function _remap($function) {
        if ( $function == 'index' || $function == '' ) { $function = ''; }
        $method = $this->method_prefix . $function;

        /**
         * view로 전달할 공통 정보
         * - 요청 함수명
         * - 요청 URI에서 파라메터를 제거한 URI 추출
         */
        $this->view_data['PAGE_NAME'] = $function;
        $this->view_data['URI'] = $this->my_common_library->uri_noise_removal($function);

        // 요청 컨트롤러가 존재하는 확인
        if (method_exists($this, $method)) {
            // 크로스 도메인 사용관련
            header_cors();

            // API 인증 목록
            $auth_list = array('main');
            $api_auth = in_array( $function, $auth_list );

            if ( $api_auth === true ) {
                // API 사용 인증 : 세션ID 확인
                $session_check_result = $this->my_common_library->session_check();

                if ( $session_check_result ) {
                    // 요청 컨트롤러 호출
                    $this->{$this->method_prefix.$function}();
                    exit;
                } else {
                    $result['result'] = false;
                    $result['error_code'] = AR_FAILURE[0];
                    $result['message'] = AR_FAILURE[1];
                }
            } else {
                // 요청 컨트롤러 호출
                $this->{$this->method_prefix.$function}();
                exit;
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        echo json_encode($result);
    }//       EOF          public function _remap($function)

    /**
     * 메인페이지용
     */
    private function _page_main() {
        // 공지사항
        $notice_list = $this->articleModel->selectArticle_list(1, 1, 5, '');
        // 자유게시판
        $board_list = $this->articleModel->selectArticle_list(2, 1, 5, '');
        // 프로젝트 목록
        $project_list = $this->projectModel->selectProject_list(1, 10, '');
        // 회원 목록
        $member_list = $this->memberModel->selectMember_list();

        $result['result'] = true;
        $result['notice_list'] = $notice_list;
        $result['board_list'] = $board_list;
        $result['project_list'] = $project_list;
        $result['member_list'] = $member_list;

        echo json_encode($result);
    }//     EOF     private function _project_list()

}//     EOC     class Page extends CI_Controller