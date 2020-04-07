<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 관리자용 : 회원 API
 */
class Group extends CI_Controller {

    public $function_prefix = '';
    public $callback;

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_group_';
        
        // Model Load
        $this->load->model('memberModel');
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
                $session_check_result = $this->my_common_library->session_check(SITE_MANAGER);
                
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
     * 그룹정보 입력
     */
    private function _group_write() {
        $name = $this->input->post('name');
        $depth_no = $this->input->post('depth_no');
        $sort_no = $this->input->post('sort_no');
        $p_seq = $this->input->post('p_seq');

        $group_info = array();
        $group_info['NAME'] = $name;
        $group_info['DEPTH_NO'] = $depth_no;
        $group_info['SORT_NO'] = $sort_no;
        $group_info['P_SEQ'] = $p_seq;

        $group_seq = $this->memberModel->insertGroup( $group_info );

        if( $group_seq ) {
            $result['result'] = true;
            $result['message'] = '그룹정보 생성 완료';
            $result['group_seq'] = $group_seq;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
        }
        echo json_encode($result);
    }//     EOF     private function _group_write()

    /**
     * 그룹정보 수정
     */
    private function _group_modify() {
        $seq = $this->input->post('seq');
        $name = $this->input->post('name');
        $sort_no = $this->input->post('sort_no');

        if ( nvl($name) != "" && nvl($sort_no) != "" ) {
            $group_info = array();
            $group_info['SEQ'] = $seq;
            $group_info['NAME'] = $name;
            $group_info['SORT_NO'] = $sort_no;

            $updateGroup_result = $this->memberModel->updateGroup( $group_info );

            if ( $updateGroup_result ) {
                $result['result'] = true;
                $result['message'] = '그룹정보 수정 완료';
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
    }//     EOF     private function _group_modify()

    /**
     * 그룹정보 삭제
     */
    private function _group_delete() {
        $group_seq = $this->input->post('seq');

        $deleteGroup_result = $this->memberModel->deleteGroup($group_seq);

        if ( $deleteGroup_result ) {
            $result['result'] = true;
            $result['message'] = '그룹정보 삭제 완료';
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
        }
        
        echo json_encode($result);
    }//     EOF     private function _group_delete()

}//            EOC       class Group extends CI_Controller
