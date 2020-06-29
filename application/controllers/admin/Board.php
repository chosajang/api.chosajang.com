<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 */
class Board extends CI_Controller {

    public $function_prefix = '';
    public $result = array();
    public $callback;

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_board_';
        
        // Model Load
        $this->load->model('boardModel');
        $this->load->model('fileModel');
    }

    /** remap
     *
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
            $auth_list = array('write','modify','delete');
            $api_auth = in_array( $function, $auth_list );

            if ( $api_auth === true ) {
                // API 사용 인증 : 세션ID 확인
                $session_check_result = $this->my_common_library->session_check();

                if( $session_check_result ) {
                    // 요청 컨트롤러 호출
                    $this->{$this->method_prefix.$function}();
                    exit;
                } else {
                    $result['result'] = false;
                    $result['error_code'] = AR_FAILURE[0];
                    $result['message'] = AR_FAILURE[1];
                    $result['message'] .= " - 요청권한이 없습니다";
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
     * 게시판 목록
     */
    private function _board_list() {
        $board_list = $this->boardModel->selectBoard_list();

        $result['result'] = true;
        $result['board_list'] = $board_list;

        echo json_encode($result);
    }//     EOF     private function _board_list()

    /**
     * 게시판 읽기
     */
    private function _board_read() {
        $board_seq = $this->input->get('board_seq');

        $result = array();

        if ( is_numeric($board_seq) ) {
            $board_info = $this->boardModel->selectBoard( $board_seq );
            if ( !is_null($board_info) ) {
                $result['result'] = true;
                $result['board'] = $board_info;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_EMPTY_REQUEST[0];
                $result['message'] = AR_EMPTY_REQUEST[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode( $result );
    }//     EOF     private _board_read()

    /**
     * 게시판 생성
     */
    private function _board_create() {
        $name = $this->input->post('name');
        $comment_yn = $this->input->post('comment_yn');
        $comment_yn = nvl($comment_yn,'N') == 'N' ? 'N' : 'Y';
        $attached_file_yn = $this->input->post('attached_file_yn');
        $attached_file_yn = nvl($attached_file_yn,'N') == 'N' ? 'N' : 'Y';
        $attached_document_yn = $this->input->post('attached_document_yn');
        $attached_document_yn = nvl($attached_document_yn,'N') == 'N' ? 'N' : 'Y';

        $result = array();

        if ( nvl($name) != '' ) {
            $board_seq = $this->boardModel->insertBoard( $name, $comment_yn, $attached_file_yn, $attached_document_yn );
            if ( $board_seq ) {
                $boardInfo = array();
                $boardInfo['SEQ'] = $board_seq;
                $boardInfo['NAME'] = $name;
                $boardInfo['COMMENT_YN'] = $comment_yn;
                $boardInfo['ATTACHED_FILE_YN'] = $attached_file_yn;
                $boardInfo['ATTACHED_DOCUMENT_YN'] = $attached_document_yn;
                $boardInfo['USE_YN'] = 'Y';

                $result['result'] = true;
                $result['message'] = '게시판 생성 완료';
                $result['data'] = $boardInfo;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode($result);
    }//     EOF     private function _board_create()

    /**
     * 게시판 수정
     */
    private function _board_update() {
        $board_seq = $this->input->post('board_seq');
        $name = $this->input->post('name');
        $comment_yn = $this->input->post('comment_yn');
        $comment_yn = nvl($comment_yn,'N') === 'N' ? 'N' : 'Y';
        $attached_file_yn = $this->input->post('attached_file_yn');
        $attached_file_yn = nvl($attached_file_yn,'N') === 'N' ? 'N' : 'Y';
        $attached_document_yn = $this->input->post('attached_document_yn');
        $attached_document_yn = nvl($attached_document_yn,'N') === 'N' ? 'N' : 'Y';
        $auth_yn = $this->input->post('auth_yn');
        $auth_yn = nvl($auth_yn,'N') == 'N' ? 'N' : 'Y';
        $notice_count = $this->input->post('notice_count');
        $notice_count = !is_numeric(nvl($notice_count,BOARD_NOTICE_COUNT)) ? BOARD_NOTICE_COUNT : ($notice_count > BOARD_NOTICE_COUNT ? BOARD_NOTICE_COUNT : $notice_count );

        $result = array();

        if ( is_numeric($board_seq) && nvl($name) != '' ) {
            $updateBoard_result = $this->boardModel->updateBoard( $board_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn, $notice_count );

            if ( $updateBoard_result ) {
                $result['result'] = true;
                $result['message'] = '게시판 수정 완료';
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode($result);
    }//     EOF     private function _board_update()

    /**
     * 게시판 삭제
     */
    private function _board_delete() {
        $board_seq = $this->input->post('board_seq');

        $result = array();

        if ( is_numeric($board_seq) ) {
            $deleteBoard_result = $this->boardModel->deleteBoard($board_seq);
            if( $deleteBoard_result ) {
                $result['result'] = true;
                $result['message'] = '게시판 삭제 완료';
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode($result);
    }//     EOF     private function _board_delete()

}//            EOC       class Login extends CI_Controller
