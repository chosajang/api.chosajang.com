<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 프로젝트 내부 컨트롤러
 * work/Project.php
 */
class Project extends CI_Controller {

    public $function_prefix = '';
    public $callback;
    public $auth_info;

    public function __construct() {
        parent::__construct();
        $this->function_prefix = '_project_';
        
        // Model Load
        $this->load->model('projectModel');
        $this->load->model('articleModel');
    }

    /** 
     * remap
     */
    public function _remap($function) {
        $this->output->enable_profiler(TRUE);
        if ( $function == 'index' || $function == '' ) { $function = ''; }
        $method = $this->function_prefix . $function;

        /**
         * view로 전달할 공통 정보
         * - 요청 함수명
         * - 요청 URI에서 파라메터를 제거한 URI 추출
         */
        $this->view_data['PAGE_NAME'] = $function;
        $this->view_data['URI'] = $this->my_common_library->uri_noise_removal($function);

        // 요청 컨트롤러가 존재하는 확인
        $result = array();
        if (method_exists($this, $method)) {
            // 크로스 도메인 사용관련
            header_cors();

            /**
             * API 인증 목록 : 회원 등급에 따른 API 승인요청
             * - 프로젝트 매니저
             *  > info_modify
             * - 일반회원
             *  > auth, info
             */
            $manager_func_list = array('member_modify','column_modify','column_delete');
            $orderer_func_list = array('modify', 'status_modify', 'status_history', 'status_list' );
            $common_func_list = array('auth', 'info', 'member_list','column_type_list','sheet_list','participated_list','integration_info');

            $manager_func_check = in_array( $function, $manager_func_list );
            $orderer_func_check = in_array( $function, $orderer_func_list );
            $common_func_check = in_array( $function, $common_func_list );

            if ( $manager_func_check === true || $orderer_func_check === true || $common_func_check === true ) {
                $func_grade = $manager_func_check === true ? PMG_MANAGER : ($orderer_func_check === true ? PMG_ORDERER : PMG_WORKER);

                // 프로젝트 권한 체크 
                $project_auth_result = $this->my_common_library->project_auth_check( $func_grade );
                if ( $project_auth_result['result'] === true ) {
                    $this->auth_info = $project_auth_result['project_authority_info'];
                    // 요청 컨트롤러 호출
                    $this->{$this->function_prefix.$function}();
                    exit;
                } else {
                    // 권한 체크 false인 경우 project_auth_check 결과값을 그대로 반환한다
                    $result = $project_auth_result;
                }
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_UNSUPPORTED[0];
                $result['message'] = AR_UNSUPPORTED[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        echo json_encode($result);
    }//       EOF          public function _remap($function)

    /**
     * 프로젝트 회원인증
     * 
     * 요청 프로젝트 회원 권한정보를 반환한다
     */
    private function _project_auth() {
        $result['result'] = true;
        $result['project_auth_info'] = $this->auth_info;
        echo json_encode( $result );
    }//     EOF     private function _project_auth()

    /**
     * 프로젝트 정보 조회
     */
    private function _project_info() {
        $member_seq = $this->input->post('member_seq');
        $project_seq = $this->input->post('project_seq');
        $member_seq = nvl($member_seq) != '' ? $member_seq : $this->input->get('member_seq');
        $project_seq = nvl($project_seq) != '' ? $project_seq : $this->input->get('project_seq');

        $result;
        if( !is_null($member_seq) && !is_null($project_seq) ) {
            // 프로젝트 정보조회
            $project_info = $this->projectModel->selectProject( $member_seq, $project_seq );
            if ( !is_null($project_info) ) {
                $project_info['OTHER_INFO'] = json_decode($project_info['OTHER_INFO']);
                $project_info['COLUMN_INFO'] = json_decode($project_info['COLUMN_INFO']);

                $result['result'] = true;
                $result['project_info'] = $project_info;
            } else {
                // 프로젝트 회원인증 실패
                $result['result'] = false;
                $result['error_code'] = AR_EMPTY_REQUEST[0];
                $result['message'] = AR_EMPTY_REQUEST[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_OMISSION[0];
            $result['message'] = AR_OMISSION[1];
        }//     EO      if( !is_null($member_seq) && !is_null($project_seq) )

        echo json_encode( $result );
    }//     EOF     private function _project_info()

    /**
     * 프로젝트 정보 수정
     */
    private function _project_modify() {
        $member_seq = $this->input->post('member_seq');
        $project_seq = $this->input->post('project_seq');

        $name = nvl( $this->input->post('name') );
        $initial = nvl( $this->input->post('initial') );
        $start_date = nvl( $this->input->post('start_date') );
        $completion_date = nvl( $this->input->post('completion_date') );
        $deliberation_date = nvl( $this->input->post('deliberation_date') );
        $release_date = nvl( $this->input->post('release_date') );

        $mov_format = nvl( $this->input->post('mov_format') );
        $mov_name = nvl( $this->input->post('mov_name') );
        $sq_output_name = nvl( $this->input->post('sq_output_name') );
        $sq_output_size = nvl( $this->input->post('sq_output_size') );
        $colorspace = nvl( $this->input->post('colorspace') );

        // 프로젝트 객체 생성
        $project_info = array();
        $project_info['PROJECT_SEQ'] = $project_seq;
        $project_info['NAME'] = $name;
        $project_info['INITIAL'] = $initial;
        $project_info['START_DATE'] = $start_date;
        $project_info['COMPLETION_DATE'] = $completion_date;
        $project_info['DELIBERATION_DATE'] = $deliberation_date;
        $project_info['RELEASE_DATE'] = $release_date;

        /**
         * OTHER INFO 항목(JSON 형태로 저장)
         * - mov_format, mov_name, sq_output_name, sq_output_size, colorspace
         */
        $other_info = array();
        $other_info['MOV_FORMAT'] = $mov_format;
        $other_info['MOV_NAME'] = $mov_name;
        $other_info['SQ_OUTPUT_NAME'] = $sq_output_name;
        $other_info['SQ_OUTPUT_SIZE'] = $sq_output_size;
        $other_info['COLORSPACE'] = $colorspace;

        $project_info['OTHER_INFO'] = json_encode( $other_info );

        /**
         * 프로젝트 업데이트
         */ 
        $this->db->trans_begin();

        $updateProject_result = $this->projectModel->updateProject( $project_info );

        // 포스터 이미지 등록
        if ( count($_FILES) > 0 ) {
            if( $_FILES['poster_file']['error'] === 0 ) {
                // 파일 업로드 후 파일 시퀀스 부여
                $file_seq_list = $this->my_common_library->file_upload(array(
                    'MEMBER_SEQ' => $member_seq,
                    'FILE' => 'poster_file'
                ));
                if( count($file_seq_list) > 0 ) {
                    // 프로젝트 포스터 파일 등록
                    $this->projectModel->updateProject_key([
                        "PROJECT_SEQ" => $project_seq,
                        "POSTER_FILE_SEQ" => $file_seq_list[0],
                    ]);
                }
            } else {
                $result['message'] .= '* 파일 업로드시 장애 발생';
            }
        }

        if( $this->db->trans_status() === TRUE ) {
            $result['result'] = true;
            $result['message'] = '프로젝트 생성 완료';
            $result['project_seq'] = $project_seq;

            $this->db->trans_commit();
        }else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] .= AR_PROCESS_ERROR[1];

            $this->db->trans_rollback();
        }

        echo json_encode( $result );
    }//     EOF     private function _project_modify()

    /**
     * 프로젝트 상태 수정
     */
    private function _project_status_modify() {
        $result = array();

        $member_seq = $this->input->post('member_seq');
        $project_seq = $this->input->post('project_seq');
        $project_status_seq = $this->input->post('project_status_seq');
        $content = nvl( $this->input->post('content') );

        // 필수 파라메터 확인
        if( !is_null($member_seq) && !is_null($project_seq) && !is_null($project_status_seq) ) {
            $project_info = array();
            $project_info['PROJECT_SEQ'] = $project_seq;
            $project_info['STATUS_SEQ'] = $project_status_seq;

            /**
             * 프로젝트 상태 수정
             * - 프로젝트 상태 수정 
             * - 프로젝트 상태 이력 입력
             */ 
            $this->db->trans_begin();

            // - 프로젝트 상태 수정 
            $this->projectModel->updateProject_key( $project_info );
            // - 프로젝트 상태 이력 입력
            $project_status_history_seq = $this->projectModel->insertProjectStatusHistory( $member_seq, $project_seq, $project_status_seq, $content );

            // - 프로젝트 상태 이력 조회
            $projectStatusHistoryInfo = $this->projectModel->selectProjectStatusHistory( $project_seq, $project_status_history_seq );

            if( $this->db->trans_status() === TRUE ) {
                $result['result'] = true;
                $result['message'] = '프로젝트 수정 완료';
                $result['project_seq'] = $project_seq;
                $result['data'] = $projectStatusHistoryInfo;
    
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
        }

        echo json_encode( $result ); 
    }//     EOF     private function _project_status_modify()

    /**
     * 프로젝트 상태 이력 목록
     */
    private function _project_status_history() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        if( !is_null( $project_seq ) ) {
            $projectStatusHistory_list = $this->projectModel->selectProjectStatusHistory_list( $project_seq );

            $result['result'] = true;
            $result['statusHistory'] = $projectStatusHistory_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_OMISSION[0];
            $result['message'] = AR_OMISSION[1];
        }

        echo json_encode( $result );
    }//     EOF     private function _project_status_history()

    /**
     * 프로젝트 상태 목록
     */
    private function _project_status_list() {
        $selectProjectStatusList = $this->projectModel->selectProjectStatusList();

        if( !is_null( $selectProjectStatusList ) ) {
            $result['result'] = true;
            $result['status_list'] = $selectProjectStatusList;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_EMPTY_REQUEST[0];
            $result['message'] = AR_EMPTY_REQUEST[1];
        }

        echo json_encode( $result );
    }//     EOF     private function _project_status_list()

    /**
     * 회원목록(프로젝트 참여인원 정보용)
     */
    private function _project_member_list() {
        $result = array();

        $project_seq = $this->input->get('project_seq');
        $page = $this->input->get('page');
        $page = nvl( $page, 1 );
        $limit = $this->input->get('limit');
        $limit = nvl( $limit, 10 );
        $search = nvl( $this->input->get('search') );
        $join_yn = nvl( $this->input->get('join_yn') );

        /**
         * page, limit가 숫자가 아닌 경우
         */
        if ( is_numeric( $page ) && is_numeric( $limit ) ) {
            $page = (int)$page;
            $limit = (int)$limit;
            $limit_start = ($page - 1) * $limit;

            $member_list = $this->projectModel->selectProjectMemberList( $project_seq, $limit_start, $limit, $search, $join_yn );

            $result['result'] = true;
            $result['member_list'] = $member_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode( $result );
    }//     EOF     private function _project_member_list()

    /**
     * 프로젝트 회원 수정(참여여부)
     */
    private function _project_member_modify() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        $modify_info = $this->input->post('modify_info');
        
        if( is_json( $modify_info ) ) {
            $modify_info_list = json_decode( $modify_info, true );
            $modify_info_list = is_object( $modify_info_list ) ? array( $modify_info_list ) : $modify_info_list;
            
            $updateProjectMember = $this->projectModel->updateProjectMember( $project_seq, $modify_info_list );

            if( $updateProjectMember ) {
                $result['result'] = true;
                $result['message'] = '회원 참여 및 권한 정보가 수정되었습니다';
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
    }//     EOF     private function _project_member_modify()



    /**
     * CG List > Column Type 목록
     */
    private function _project_column_type_list() {
        $result = array();
        $columnTypeList = $this->projectModel->selectColumnTypeList();

        if( !is_null($columnTypeList) ) { 
            $result['result'] = true;
            $result['data'] = $columnTypeList;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_UNSUPPORTED[0];
            $result['message'] = AR_UNSUPPORTED[1];
        }
        
        echo json_encode( $result );
    }//     EOF     private function _project_column_type_list()
    
    /**
     * CG List > Column 정보 저장
     * [Parameters]
     * - info
     *  > key : 자료식별 고유키, 수정시 필수값
     *  > (필수)name : 컬럼 이름
     *  > (필수)sort_no : 정렬 번호
     * 
     * [Context]
     * 1. 파라메터 유효성 검사
     * 2. 입력 컬럼 정보(입력/수정)가 있는 경우
     *  2.1 기존 컬럼 정보가 없는 경우
     *   - 입력 컬럼 정보의 전체 키 신규 발급
     *  2.2 기존 컬럼 정보가 있는 경우
     * 3. 입력 컬럼 정보가 없는 경우
     * 
     */
    private function _project_column_modify() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        $sheet_seq = $this->input->post('sheet_seq');
        $info = $this->input->post('info');

        // 1. 파라메터 유효성 검사
        if( is_json( $info ) && !is_null($project_seq) && !is_null($sheet_seq) ) {
            // - 시트 정보 조회
            $sheetInfo = $this->projectModel->selectSheet( $sheet_seq, $project_seq );
            $column_info = $sheetInfo['COLUMN_INFO'];
            $column_info = json_decode($column_info, true);
            
            $info = json_decode( $info, true );

            // 2. 입력 컬럼 정보값(입력/수정)가 있는 경우
            if( count($info) > 0 ) {
                // - 배열안 key를 소문자로 변경
                $info = ci_array_change_key_case( $info, CASE_LOWER );
                // - 입력 컬럼 정보의 key값 소문자변환 및 list array가 아닌 경우 list로 묶음
                $info_list = array_key_exists(0,$info) ? $info : array( $info );
                // - 입력 컬럼 정보의 필수 key 검사
                $info_key_check = true;
                foreach( $info_list as $tmp_info ) {
                    if( !array_key_exists("name", $tmp_info) || !array_key_exists("sort_no", $tmp_info) ) {
                        $info_key_check = false;
                        break;
                    }
                }
                
                if( $info_key_check ){
                    $last_key = is_null($column_info) ? "" : $column_info['last_key'];
                    $org_info_list = is_null($column_info) ? [] : $column_info['data_list'];
                        
                    foreach( $info_list as &$tmp_info ) {
                        // - 입력 컬럼 정보에 key값이 없으면 신규발급
                        if( !array_key_exists("key", $tmp_info) ) {
                            $last_key = $this->my_common_library->column_key_generator($last_key);
                            $tmp_info['key'] = $last_key;
                            $tmp_info['use_yn'] = true;
                        } else {
                            $current_key = $tmp_info['key'];
                            // 입력 컬럼 정보와 key가 겹치는 기존 컬럼 정보는 삭제, 겹치지 않는 정보는 사용안함 처리
                            foreach( $org_info_list as $idx=>&$org_info ) {
                                if( $org_info['key'] == $current_key ) {
                                    unset( $org_info_list[$idx] );
                                } else {
                                    $org_info['use_yn'] = false;
                                }
                            }
                            $org_info_list = array_values($org_info_list);
                        }
                    }//     EO      foreach( $info_list as &$tmp_info )
                        
                    // 사용안함 처리된 기존 컬럼 정보와 입력 컬럼 정보를 취합
                    $info_list = array_merge( $org_info_list, $info_list );
    
                    // sort_no 기준으로 정렬
                    $info_list = arr_sort($info_list,"sort_no");
                    
                    // 시트의 컬럼 정보 업데이트
                    $update_info = array();
                    $update_info['last_key'] = $last_key;
                    $update_info['data_list'] = $info_list;
                    $updateSheet_ColumnInfo = $this->projectModel->updateSheet_ColumnInfo( json_encode($update_info), $sheet_seq, $project_seq );
    
                    if( $updateSheet_ColumnInfo ) {
                        $result['result'] = true;
                        $result['message'] = '컬럼 정보가 변경되었습니다';
                        /**
                         * 삭제된 항목에 대해서는 노출시키지 않는다
                         * use_yn : false
                         */
                        foreach( $info_list as $idx=>$col ){
                            if( array_key_exists("use_yn", $col) ){
                                if( $col['use_yn'] == false ) {
                                    unset($info_list[$idx]);
                                }
                            }
                        }
                        $info_list = array_values($info_list);
    
                        $result['data'] = $info_list;
                    } else {
                        $result['result'] = false;
                        $result['error_code'] = AR_PROCESS_ERROR[0];
                        $result['message'] = AR_PROCESS_ERROR[1];
                    }
                } else {
                    $result['result'] = false;
                    $result['error_code'] = AR_BAD_REQUEST[0];
                    $result['message'] = AR_BAD_REQUEST[1];
                    $result['message'] .= " - info에 필수 항목이 누락되었습니다";
                }
            } else {
                // $info 값이 비어있는 경우, 전체삭제 진행
                if( !is_null($column_info) ) {
                    $org_info_list = $column_info['data_list'];
                    foreach( $org_info_list as &$org_info ) {
                        $org_info['use_yn'] = false;
                    }
                    $update_info = array();
                    $update_info['last_key'] = $column_info['last_key'];
                    $update_info['data_list'] = $org_info_list;
                    
                    $updateSheet_ColumnInfo = $this->projectModel->updateSheet_ColumnInfo( json_encode($update_info), $sheet_seq, $project_seq );

                    if( $updateSheet_ColumnInfo ) {
                        $result['result'] = true;
                        $result['message'] = '컬럼 정보가 변경되었습니다';
                        /**
                         * 삭제된 항목에 대해서는 노출시키지 않는다
                         * use_yn : false
                         */
                        foreach( $org_info_list as $idx=>$col ){
                            if( array_key_exists("use_yn", $col) ){
                                if( $col['use_yn'] == false ) {
                                    unset($org_info_list[$idx]);
                                }
                            }
                        }
                        $org_info_list = array_values($org_info_list);

                        $result['data'] = $org_info_list;
                    } else {
                        $result['result'] = false;
                        $result['error_code'] = AR_PROCESS_ERROR[0];
                        $result['message'] = AR_PROCESS_ERROR[1];
                    }
                } else {
                    $result['result'] = true;
                    $result['message'] = '컬럼 정보가 변경되었습니다';
                }
            }//     EO      if( count($info) > 0 )
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        
        echo json_encode( $result );
    }//     EOF     private function _project_column_modify()

    /**
     * 컬럼 정보 삭제
     */
    private function _project_column_delete() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        $sheet_seq = $this->input->post('sheet_seq');
        $key = $this->input->post('key');
        
        if( !is_null($project_seq) && !is_null($sheet_seq) && !is_null($key) ) {
            // 시트 정보 조회
            $sheetInfo = $this->projectModel->selectSheet( $sheet_seq, $project_seq );
            $column_info = $sheetInfo['COLUMN_INFO'];
            if( is_json($column_info) ) {
                $column_info = json_decode($column_info, true);
                $data_list = $column_info['data_list'];
                foreach( $data_list as &$data ) {
                    if( array_key_exists("key", $data) ) {
                        if( $data["key"] == $key ) {
                            $data['use_yn'] = FALSE;
                        }
                    }
                }
                $column_info['data_list'] = $data_list;
                
                $updateSheet_ColumnInfo = $this->projectModel->updateSheet_ColumnInfo( json_encode($column_info), $sheet_seq, $project_seq );

                if( $updateSheet_ColumnInfo ) {
                    $result['result'] = true;
                    $result['message'] = '컬럼 정보가 삭제되었습니다';
                    $result['data'] = $data_list;
                } else {
                    $result['result'] = false;
                    $result['error_code'] = AR_PROCESS_ERROR[0];
                    $result['message'] = AR_PROCESS_ERROR[1];
                }
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
                $result['message'] .= ' - 올바르지 자료가 아닙니다';
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
    
        echo json_encode( $result );
    }//     EOF     private function _project_column_delete()

    /**
     * 시트 목록
     */
    private function _project_sheet_list() {
        $result = array();
        $project_seq = $this->input->post('project_seq');

        $sheet_list = $this->projectModel->selectSheetList( $project_seq );

        if( !is_null($sheet_list) && count($sheet_list) > 0 ) {
            $result['result'] = true;
            if( !is_null($sheet_list) ){
                foreach( $sheet_list as &$sheetInfo ) {
                    if( array_key_exists('COLUMN_INFO', $sheetInfo) ) {
                        $col_list = !is_null($sheetInfo['COLUMN_INFO']) ? json_decode($sheetInfo['COLUMN_INFO'], true) : [];
                        /**
                         * 삭제된 항목에 대해서는 노출시키지 않는다
                         * use_yn : false
                         */
                        foreach( $col_list as $idx=>$col ){
                            if( array_key_exists("use_yn", $col) ){
                                if( $col['use_yn'] == false ) {
                                    unset($col_list[$idx]);
                                }
                            }
                        }
                        $col_list = array_values($col_list);
                        
                        $sheetInfo['COLUMN_INFO'] = $col_list;
                    }
                    if( array_key_exists('VSHEET_INFO', $sheetInfo) ) {
                        $sheetInfo['VSHEET_INFO'] = json_decode($sheetInfo['VSHEET_INFO'], true);
                    }
                }
            }
            $result['data'] = $sheet_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= " - 기본 시트정보가 없습니다";
        }

        echo json_encode( $result );
    }//     EOF     private function _project_sheet_list()

    /**
     * 프로젝트 참여 목록
     * - 요청한 회원이 참여한 프로젝트 목록
     * - 회원 등급에 따라 요청하는 프로젝트 상태(대기, 진행, 종료)가 달라짐
     *  > 일반회원, 사이트매니저 : 진행, 종료
     *  > 프로젝트 매니저, 시스템 매니저 : 모든 상태
     */
    private function _project_participated_list() {
        $result = array();
        $member_seq = $this->input->post('member_seq');

        $memberInfo = $this->memberModel->selectMember( $member_seq );
        $project_status_seq_list = array();
        if( $memberInfo['MEMBER_GRADE_SEQ'] == 1 || $memberInfo['MEMBER_GRADE_SEQ'] == 2) {
            $project_status_seq_list = [2,3];
        } else { 
            $project_status_seq_list = [1,2,3];
        }
        
        $participated_list = $this->projectModel->selectProject_participatedList( $member_seq, $project_status_seq_list );
        
        $result['result'] = true;
        $result['data'] = $participated_list;

        echo json_encode( $result );
    }//     EOF     private function _project_participated_list()

    /**
     * 프로젝트 통합
     * - 참여한 프로젝트 목록
     * - 프로젝트 정보
     * - 시트 목록 + 컷 통계 자료 + 컬럼 정보 + 컷 목록
     * - 팀별 업무 진행율
     * - 게시판 목록
     * 
     * * 회원 등급에 따라 관리자 페이지 정보 추가 예정
     */
    private function _project_integration_info() {
        $result = array();

        $member_seq = $this->input->get('member_seq');
        $project_seq = $this->input->get('project_seq');

        // 프로젝트 정보
        $project_info = $this->projectModel->selectProject( $member_seq, $project_seq );
        if( !is_null($project_info) ) {
            // 프로젝트 정보의 json 자료 변환
            $project_info['OTHER_INFO'] = is_null($project_info['OTHER_INFO']) ? array() : json_decode($project_info['OTHER_INFO'], true);
            // 참여한 프로젝트 목록
            $project_member_grade_seq = $this->auth_info['MEMBER_GRADE_SEQ'];
            $project_status_seq_list = $project_member_grade_seq == 3 ? [1,2,3] : [2,3];
            $project_join_list = $this->projectModel->selectProject_participatedList( $member_seq, $project_status_seq_list );
            // 프로젝트 시트 목록
            $sheet_list = $this->projectModel->selectSheetList($project_seq);
            // 시트별 하위 정보 조회
            foreach( $sheet_list as &$sheet_info ) {
                $sheet_seq = $sheet_info['SEQ'];
                $sheet_info['COLUMN_INFO'] = json_decode($sheet_info['COLUMN_INFO'], true);
                // 시트 컬럼 통계 자료
                $statistics_cutStatusList = $this->projectModel->selectStatistics_cutStatusList($sheet_seq);
                $sheet_info['CUT_STATISTICS'] = $statistics_cutStatusList;
                // 컷 목록
                $cut_list = $this->projectModel->selectCutList( $sheet_seq );
                foreach( $cut_list as &$cut_info ) {
                    $cut_info['DATA'] = json_decode($cut_info['DATA'], true);
                }
                $sheet_info['CUT_LIST'] = $cut_list;
            }//     EO      foreach( $sheet_list as $sheet_info )
            // 프로젝트 게시물 목록
            $article_list = $this->articleModel->selectArticle_list($project_info['BOARD_SEQ'], 0, 0, "");

            $result['result'] = true;
            $result['message'] = "통합정보 조회 완료";
            $result['data']['PROJECT_INFO'] = $project_info;
            $result['data']['PROJECT_JOIN_LIST'] = $project_join_list;
            $result['data']['SHEET_LIST'] = $sheet_list;
            $result['data']['TASK_LIST'] = array();
            $result['data']['ARTICLE_LIST'] = $article_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
            $result['message'] .= " - 프로젝트 정보가 없습니다";
        }//     EO      if( !is_null($project_info) )
        
        echo json_encode( $result );
    }//     EOF     private function _project_integration_info()

}//     EOC     class Project extends CI_Controller