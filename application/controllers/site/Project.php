<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * CMS 프로젝트 컨트롤러
 * site/Project.php
 */
class Project extends CI_Controller {

    public $function_prefix = '';
    public $callback;

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_project_';
        
        // $this->load->library('email');
        // Model Load
        $this->load->model('projectModel');
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

            /**
             * API 인증 목록 : 회원 등급에 따른 API 승인요청
             * - 프로젝트 매니저
             *  > create
             * - 일반회원
             *  > list
             */
            $manager_auth_list = array('create');
            $common_auth_list = array('list');

            $manager_api_check = in_array( $function, $manager_auth_list );
            $common_api_check = in_array( $function, $common_auth_list );

            if ( $manager_api_check === true || $common_api_check === true ) {
                $check_member_grade = $manager_api_check === true ? PROJECT_MANAGER : USER;
                // API 사용 인증 : 세션ID 확인
                $session_check_result = $this->my_common_library->session_check($check_member_grade);
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
                $result['result'] = false;
                $result['error_code'] = AR_BAD_REQUEST[0];
                $result['message'] = AR_BAD_REQUEST[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        echo json_encode($result);
    }//       EOF          public function _remap($function)

    /**
     * 프로젝트 목록
     */
    private function _project_list(){
        $result = array();

        $page = $this->input->get('page');
        $limit = $this->input->get('limit');
        $search = $this->input->get('search');
        $member_seq = $this->input->get('member_seq');
        $member_seq = nvl($member_seq);

        if( $member_seq != "" ) {
            // page가 숫자가 아니거나 값이 없는경우, 게시물 전체 검색으로 간주
            if( !is_numeric($page) && nvl($limit) === '' ){
                $limit = 0;
                $limit_start = 0;
            } else {
                $page = !is_numeric($page) ? 1 : $page;
                $limit = (int)nvl($limit, 10);
                $limit_start = ($page - 1) * $limit;
            }
            
            $project_list = $this->projectModel->selectProject_list($member_seq, $limit_start, $limit, $search);

            if( !is_null($project_list) ) { 
                $result['result'] = true;
                $result['project_list'] = $project_list;
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
    }//     EOF     private function _project_list()

    /**
     * 프로젝트 생성
     */
    private function _project_create() {
        $member_seq = $this->input->post('member_seq');
        $title = $this->input->post('title');
        $title = nvl($title,'');

        if( $title !== '' ) {
            $this->db->trans_begin();

            /**
             * - 프로젝트 생성
             * - 프로젝트 상태 이력
             * - 프로젝트 게시판 생성 및 연결
             * 
             * - 시트 생성
             * - 프로젝트 참여 회원 연결
             */
            // - 프로젝트 생성
            $project_seq = $this->projectModel->insertProject($member_seq, $title);
            // - 프로젝트 상태 이력
            $content = "프로젝트가 생성되었습니다";
            $insertProjectStatusHistory = $this->projectModel->insertProjectStatusHistory( $member_seq, $project_seq, PS_WAIT, $content );
            // - 프로젝트 게시판 생성 및 연결
            $insertProjectBoard = $this->boardModel->insertProjectBoard( $project_seq, $title, 'Y', 'Y', 'Y' );
            // - 시트 생성
            $sheet_name = "CG LIST";
            $insertSheet_result = $this->projectModel->insertSheet($sheet_name, $project_seq, DEFAULT_COLUMN_INFO );
            // - 프로젝트 참여 회원 연결
            $member_info = array();
            $member_info['member_seq'] = $member_seq;
            $member_info['grade_seq'] = PMG_MANAGER;
            $member_info['join_yn'] = 'Y';
            $member_list[] = $member_info;
            $updateProjectMember_result = $this->projectModel->updateProjectMember( $project_seq, $member_list );

            if( $this->db->trans_status() === TRUE ) {
                $result['result'] = true;
                $result['message'] = '프로젝트 등록 완료';
                $result['project_seq'] = $project_seq;

                $this->db->trans_commit();
            }else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];

                $this->db->trans_rollback();
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_OMISSION[0];
            $result['message'] = AR_OMISSION[1];
            $result['message'] .= ' - 프로젝트명은 빈 값으로 사용할 수 없습니다';
        }

        echo json_encode($result);
    }//     EOF     private function _project_create()

}//     EOC     class Project extends CI_Controller