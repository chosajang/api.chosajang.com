<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 */
class User extends CI_Controller {

    public $function_prefix = '';
    public $callback;
    public $result;

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_user_';
        
        $this->load->library('email');
        // Model Load
        $this->load->model('memberModel');
        $this->load->model('fileModel');

        $this->result['result'] = false;
        $this->result['message'] = 'API 미처리 상태';
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
            // API 인증 목록
            $auth_list = array('logout','login_check','info','modify','password_modify');
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
                // 크로스 도메인 사용관련
                header_cors();
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
        $birthday           = $this->input->post('birthday');
        $tel                = $this->input->post('tel');
        $comment            = $this->input->post('comment');

        $member_title_seq   = $this->input->post('title_seq');

        // 회원 객체 생성
        $member_info                      = array();
        $member_info['ID']                = $id;
        $member_info['PASSWORD']          = password_hash($password, PASSWORD_BCRYPT);
        $member_info['NAME']              = $name;
        $member_info['ENTRY_DATE']        = $entry_date;
        $member_info['BIRTHDAY']          = $birthday;
        $member_info['MEMBER_TITLE_SEQ']  = $member_title_seq;
        $member_info['TEL']               = $tel;
        $member_info['COMMENT']           = nvl($comment);

        // ID 중복 확인
        $temp_member_info['ID'] = $id;
        $password_info = $this->memberModel->selectMember_passwordInfo( $temp_member_info );

        if ( $password_info === null ) {
            // 회원가입 진행, 회원 정보 입력
            $member_seq = $this->memberModel->insertMember($member_info);
            // 프로필 이미지 등록
            if ( count($_FILES) > 0 ) {
                // 프로필 이미지 등록
                $file_seq_list = $this->my_common_library->file_upload( array(
                    'MEMBER_SEQ' => $member_seq,
                    'FILE' => 'profile_file'
                ));

                if( count($file_seq_list) > 0 ) {
                    // 등록된 이미지를 회원정보로 등록
                    $this->memberModel->updateMember( array(
                        "SEQ" => $member_seq,
                        "PROFILE_FILE_SEQ" => $file_seq_list[0]
                    ));
                }
            }

            if ( $member_seq ) {
                $result['result'] = true;
                $result['message'] = '회원 가입 완료';
                $result['member_seq'] = $member_seq;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
                $result['message'] .= ' - 회원 가입 실패';
            }
        } else {
            // 아이디 중복
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= ' - 이미 가입한 ID가 있습니다';
        }

        echo json_encode($result);
    }//     EOF     private function _user_create()

    /** 
     * 회원 정보 조회(_user_info)
     */
    private function _user_info() {
        $req_member_seq = $this->input->get('req_member_seq');

        $member_info = $this->memberModel->selectMember( $req_member_seq, FALSE );

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
            $result['message'] .= ' - 회원정보 조회 실패';
        }        

        echo json_encode( $result );
    }//     EOF     private function _user_info()


    /** 
     * 로그인(_user_login)
     */
    private function _user_login() {
        $id = $this->input->post('id');
        $password = $this->input->post('password');

        // 결과값 기본설정
        $result = $this->result;

        // 유효성검사
        // ID, PW를 받아서 DB로 회원정보조회
        if( !is_null($id) && !is_null($password) ) {
            // 회원 비밀번호 비교
            $temp_member_info['ID'] = $id;
            $password_info = $this->memberModel->selectMember_passwordInfo( $temp_member_info );
            if( password_verify($password, $password_info['PASSWORD']) ) {
                // 회원정보 조회
                $member_info = $this->memberModel->selectMember( $password_info['SEQ'], TRUE );
                if( !is_null($member_info) ) {
                    // 회원로그인 정보 업데이트
                    $session_id = random_string('sha1');
                    $this->memberModel->updateMemberLogin($member_info['SEQ'], $session_id);

                    // 파일정보조회
                    $profile_file_info = $this->fileModel->selectFileForSeq($member_info['PROFILE_FILE_SEQ']);
                    $member_info['PROFILE_FILE_INFO'] = $profile_file_info;

                    $member_info['SESSION_ID'] = $session_id;

                    // 결과값 생성
                    $result['result'] = true;
                    $result['message'] = '로그인 정보가 확인되었습니다';
                    $result['member_info'] = $member_info;
                } else {
                    // 회원정보 조회 실패
                    $result['result'] = false;
                    $result['error_code'] = AR_PROCESS_ERROR[0];
                    $result['message'] = AR_PROCESS_ERROR[1];
                    $result['message'] .= ' - 회원정보 조회 실패';
                }
            } else {
                // 비밀번호 틀림
                $result['result'] = false;
                $result['error_code'] = AR_FAILURE[0];
                $result['message'] = AR_FAILURE[1];
                $result['message'] .= ' - 아이디 또는 비밀번호가 올바르지 않습니다';
            }
        } else {
            // 로그인 페이지로 이동
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        echo json_encode($result);
    }//     EOF     private function _user_login()


    /** 
     * 로그인 체크(_user_login_check)
     */
    private function _user_login_check() {
        $member_seq = $this->input->get('member_seq');
        $result = array();

        if( !is_null($member_seq) ){
            $member_info = $this->memberModel->selectMember( $member_seq, TRUE );
        
            // 회원 프로필 사진 정보
            $profile_file_info = $this->fileModel->selectFileForSeq($member_info['PROFILE_FILE_SEQ']);
            $member_info['PROFILE_FILE_INFO'] = $profile_file_info;

            $result['result'] = true;
            $result['member_info'] = $member_info;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode( $result );
    }//     EOF     private function _user_login_check()


    /** 
     * 로그아웃(_user_logout)
     */
    private function _user_logout() {
        $member_seq = $this->input->get('member_seq');
        $session_id = $this->input->get('session_id');
        
        $memberInfo = $this->memberModel->selectMember( $member_seq, TRUE );

        if ( $session_id === $memberInfo['SESSION_ID'] ) {
            // 회원 테이블 session_id 항목 빈값으로 업데이트
            if( $this->memberModel->updateMember_sessionClear($member_seq) ){
                $result['result'] = true;
                $result['message'] = '세션ID가 초기화 되었습니다';
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            // 세션 삭제 실패
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode($result);
    }//     EOF     private function _user_logout()


    /** 
     * 회원 정보 수정(_user_modify)
     */
    private function _user_modify() {
        $result = array();
        $member_seq         = $this->input->post('member_seq');

        $id                 = $this->input->post('id');
        $password           = $this->input->post('password');
        $name               = $this->input->post('name');
        $entry_date         = $this->input->post('entry_date');
        $birthday           = $this->input->post('birthday');
        $tel                = $this->input->post('tel');
        $comment            = $this->input->post('comment');
        
        // 회원 객체 생성
        $member_info                        = array();
        $member_info['SEQ']                 = $member_seq;
        $member_info['ID']                  = $id;
        $member_info['NAME']                = $name;
        $member_info['ENTRY_DATE']          = $entry_date;
        $member_info['BIRTHDAY']            = $birthday;
        $member_info['TEL']                 = $tel;
        $member_info['COMMENT']             = nvl($comment);

        // 비밀번호 확인
        $temp_member_info['ID'] = $id;
        $password_info = $this->memberModel->selectMember_passwordInfo( $temp_member_info );
        
        if ( password_verify($password, $password_info['PASSWORD']) ) {
            // 회원 정보 수정
            $updateMember_Result = $this->memberModel->updateMember($member_info);
            $result['result'] = $updateMember_Result;

            if( $updateMember_Result ) {
                $result['message'] = '회원정보 업데이트 성공';
                // 프로필 이미지 등록
                if (count($_FILES) > 0) {
                    if( $_FILES['profile_file']['error'] === 0 ) {
                        // 프로필 이미지 등록
                        $file_seq_list = $this->my_common_library->file_upload(array(
                            'MEMBER_SEQ' => $member_seq,
                            'FILE' => 'profile_file'
                        ));
                        if( count($file_seq_list) > 0 ) {
                            // 등록된 이미지를 회원정보로 등록
                            $this->memberModel->updateMember([
                                "SEQ" => $member_seq,
                                "PROFILE_FILE_SEQ" => $file_seq_list[0],
                            ]);
                        }
                    } else {
                        $result['result'] = false;
                        $result['error_code'] = AR_PROCESS_ERROR[0];
                        $result['message'] = AR_PROCESS_ERROR[1];
                        $result['message'] .= ' - 파일 업로드시 장애 발생';
                    }
                }
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
                $result['message'] .= ' - 회원정보 업데이트 실패';
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_FAILURE[0];
            $result['message'] = AR_FAILURE[1];
            $result['message'] .= ' - 비밀번호가 틀립니다';
        }

        // 결과값 처리        
        echo json_encode( $result );
    }//     EOF     private function _user_modify()

    /** 
     * 회원 비밀번호 변경(_user_password_modify)
     */
    private function _user_password_modify() {
        $result = array();

        $member_seq = $this->input->post('member_seq');
        $password = $this->input->post('password');
        $new_password = $this->input->post('new_password');

        // 비밀번호 확인
        $temp_member_info['SEQ'] = $member_seq;
        $password_info = $this->memberModel->selectMember_passwordInfo( $temp_member_info );
        
        if ( password_verify($password, $password_info['PASSWORD']) ) {
            // 비밀번호 변경
            $updateMember_result = $this->memberModel->updateMember([
                "SEQ" => $member_seq,
                "PASSWORD" => password_hash($new_password, PASSWORD_BCRYPT),
            ]);
            if ( $updateMember_result ) {
                $result['result'] = true;
                $result['message'] = '비밀번호 변경 성공';
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
                $result['message'] .= ' - 비밀번호 변경 실패';
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_FAILURE[0];
            $result['message'] = AR_FAILURE[1];
            $result['message'] .= ' - 비밀번호가 틀립니다';
        }

        echo json_encode($result);
    }//     EOF     private function _user_password_modify()


    /** 
     * 패스워드 재발행(_user_password_reissue)
     */
    private function _user_password_reissue() {
        $id = $this->input->post('id');
        $name = $this->input->post('name');
        /**
         * TODO : 이름과 ID로 회원정보가 있는지 조회
         */

        if ( !is_null($id) ) {
            // 회원 정보 조회 후, 메일발송
            // $temp_member_info['ID'] = $id;
            // $passwordInfo = $this->memberModel->selectMember_passwordInfo( $temp_member_info );

            // 회원정보 조회
            $member_temp_info = $this->memberModel->selectMemberForID($id);
            $member_info = $this->memberModel->selectMember( $member_temp_info['SEQ'], FALSE );

            $from_mail = 'cgwork2019@gmail.com';
            $from_name = 'Pipeline';

            $to_mail = $member_info['ID'];
            $to_name = $member_info['NAME'];

            $this->email->from( $from_mail, $from_name );
            $this->email->to( $to_mail, $to_name );
            $this->email->subject('COCOA PIPELINE : PASSWORD REISSUE');
            $this->email->message('This is my message');

            if ( $this->email->send(FALSE) ) {
                $result['result'] = true;
                $result['message'] = '메일이 발송되었습니다. 메일 내용을 확인해 주세요.';
            } else {
                $result['result'] = false;
                $result['message'] = '메일 발송 실패';
            }
        } else {
            $result['result'] = false;
            $result['message'] = '올바른 요청이 아닙니다';
        }

        echo json_encode($result);
    }//     EOF     private function _user_password_reissue() {


    /** 
     * 회원 직함 목록(_user_title_list)
     */
    private function _user_title_list() {
        $title_list = $this->memberModel->selectMemberTitle_list();
        if ( $title_list !== null ) {
            $result['result'] = true;
            $result['data'] = $title_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= ' - 조회 실패';
        }
        
        echo json_encode($result);
    }//     EOF     private function _user_title_list()

    /**
     * 회원 등급 목록
     */
    private function _user_grade_list() {
        $grade_list = $this->memberModel->selectMemberGrade_list();
        if ( $grade_list !== null ) {
            $result['result'] = true;
            $result['data'] = $grade_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= ' - 조회 실패';
        }
        
        echo json_encode($result);
    }//     EOF     private function _user_grade_list()

    /**
     * 회원 상태 목록
     */
    private function _user_status_list() {
        $result = array();

        $status_list = $this->memberModel->selectMemberStatusList(); 
        if( $status_list != null ) {
            $result['result'] = true;
            $result['data'] = $status_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= ' - 조회 실패';
        }

        echo json_encode($result);
    }//     EOF     private function _user_status_list()

    /**
     * 회원 목록(_user_list)
     */
    private function _user_list() {
        // 승인 상태(3) 회원의 목록만 조회
        $member_list = $this->memberModel->selectMember_list();

        if ( $member_list !== null ) {
            $result['result'] = true;
            $result['data'] = $member_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= ' - 조회 실패';
        }

        echo json_encode($result);
    }//     EOF     private function _user_list()


}//            EOC       class Login extends CI_Controller
 