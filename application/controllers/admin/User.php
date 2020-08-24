<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 관리자용 : 회원 API
 */
class User extends CI_Controller {

    public $function_prefix = '';
    public $callback;

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_user_';
        
        $this->load->library('email');
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

        // 크로스 도메인 사용관련
        header_cors();

        /**
         * view로 전달할 공통 정보
         * - 요청 함수명
         * - 요청 URI에서 파라메터를 제거한 URI 추출
         */
        $this->view_data['PAGE_NAME'] = $function;
        $this->view_data['URI'] = $this->my_common_library->uri_noise_removal($function);

        // 요청 컨트롤러가 존재하는 확인
        if (method_exists($this, $method)) {
            // API 인증 목록
            $auth_list = array('create','update');
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
     * 회원 생성(_user_create)
     */
    private function _user_create() {
        $result = false;
        // 파라메터
        $id                 = $this->input->post('id');
        $password           = $this->input->post('password');
        $name               = $this->input->post('name');
        $entry_date         = $this->input->post('entry_date');
        $comment            = $this->input->post('comment');
        $member_title_seq   = $this->input->post('title_seq');

        // 회원 객체 생성
        $member_info                      = array();
        $member_info['ID']                = $id;
        $member_info['PASSWORD']          = password_hash($password, PASSWORD_BCRYPT);
        $member_info['NAME']              = $name;
        $member_info['ENTRY_DATE']        = $entry_date;
        $member_info['MEMBER_TITLE_SEQ']  = $member_title_seq;
        $member_info['COMMENT']           = nvl($comment);

        // ID 중복 확인
        $temp_member_info['ID'] = $id;
        $password_info = $this->memberModel->selectMember_passwordInfo( $temp_member_info );

        if ( $password_info === null ) {
            // 회원가입 진행, 회원 정보 입력
            $member_seq = $this->memberModel->insertMember($member_info);
            if ( $member_seq ) {
                // 프로필 이미지 등록
                if (count($_FILES) > 0) {
                    // 프로필 이미지 등록
                    $file_seq_list = $this->my_common_library->file_upload(array(
                        'MEMBER_SEQ' => $member_seq,
                        'FILE' => 'profile_file'
                    ));
                    // 등록된 이미지를 회원정보로 등록
                    $this->memberModel->updateMember([
                        "SEQ" => $member_seq,
                        "PROFILE_FILE_SEQ" => $file_seq_list[0]['FILE_SEQ'],
                    ]);
                }

                $result['result'] = true;
                $result['message'] = '회원 가입 완료';
                $result['member_seq'] = $member_seq;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            // 아이디 중복
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1] . ' - 중복된 아이디입니다';
        }

        echo json_encode($result);
    }//     EOF     private function _user_create()

    /** 
     * 회원 정보 조회(_user_info)
     */
    private function _user_info() {
        $member_seq = $this->input->get('req_member_seq');

        $member_info = $this->memberModel->selectMember($member_seq, FALSE);

        if ( !is_null($member_info) ) {
            // 회원 프로필 사진 정보
            $profile_file_info = $this->fileModel->selectFileForSeq($member_info['PROFILE_FILE_SEQ']);
            $member_info['PROFILE_FILE_INFO'] = $profile_file_info;

            $result['result'] = true;
            $result['member_info'] = $member_info;
        } else {
            // 회원정보 조회 실패
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
        }        

        echo json_encode( $result );
    }//     EOF     private function _user_info()
    
    /** 
     * 회원 정보 수정(_user_update)
     */
    private function _user_update() {
        $result = array();
        $req_member_seq     = $this->input->post('req_member_seq');
        $req_member_seq     = nvl($req_member_seq);

        $id                 = $this->input->post('id');
        $id                 = nvl($id);
        $password           = $this->input->post('new_password');
        $password           = nvl($password);
        $name               = $this->input->post('name');
        $name               = nvl($name);
        $title              = $this->input->post('title');
        $comment            = $this->input->post('comment');
        
        if( $req_member_seq != "" && $id != "" && $name != "" ) {
            // 회원 객체 생성
            $member_info                      = array();
            $member_info['SEQ']               = $req_member_seq;
            $member_info['ID']                = $id;
            $member_info['NAME']              = $name;
            $member_info['TITLE']             = $title;
            $member_info['COMMENT']           = $comment;
            if( $password != '' ){
                $member_info['PASSWORD'] = password_hash($password, PASSWORD_BCRYPT);
            }
            
            // 회원 정보 수정
            $updateMember_Result = $this->memberModel->updateMember($member_info);

            if( $updateMember_Result ) {
                $profile_file_info = null;
                // 프로필 이미지 등록
                if (count($_FILES) > 0) {
                    // 프로필 이미지 등록
                    $file_seq_list = $this->my_common_library->file_upload(array(
                        'MEMBER_SEQ' => $req_member_seq,
                        'FILE' => 'profile_file'
                    ));
                    $file_seq = $file_seq_list[0]['FILE_SEQ'];
                    // 등록된 이미지를 회원정보로 등록
                    $this->memberModel->updateMember([
                        "SEQ" => $req_member_seq,
                        "PROFILE_FILE_SEQ" => $file_seq,
                    ]);
                    $profile_file_info = $this->fileModel->selectFileForSeq($file_seq);
                }
                $member_info['PROFILE_FILE_INFO'] = $profile_file_info;

                $result['result'] = true;
                $result['message'] = '회원정보 업데이트 성공';
                $result['data'] = $member_info;
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

        echo json_encode( $result );
    }//     EOF     private function _user_update()

    /**
     * 회원 목록(_user_list)
     */
    private function _user_list() {
        $member_list = $this->memberModel->selectMember_list();

        if ( $member_list != null ) {
            $result['result'] = true;
            $result['data'] = $member_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
        }

        echo json_encode($result);
    }//     EOF     private function _user_list()

}//            EOC       class User extends CI_Controller
