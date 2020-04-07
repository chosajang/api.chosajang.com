<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 프로젝트 내부 컨트롤러
 * work/Cglist.php
 */
class Cglist extends CI_Controller {

    public $function_prefix = '';
    public $callback;
    public $auth_info;

    public function __construct() {
        parent::__construct();
        $this->function_prefix = '_cglist_';
        
        // Model Load
        $this->load->model('projectModel');
    }

    /** 
     * remap
     */
    public function _remap($function) {
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
            $manager_func_list = array();
            $orderer_func_list = array('cut_create','cut_update','cut_delete');
            $common_func_list = array('cut_list');

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

    private function _cglist_cut_list() {
        $result = array();
        
        $sheet_seq = $this->input->get('sheet_seq');
        $sheet_seq = nvl($sheet_seq);

        if( $sheet_seq != "" ){
            $cut_list = $this->projectModel->selectCutList( $sheet_seq );
        
            $result['result'] = true;
            $result['data'] = $cut_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        
        echo json_encode($result);
    }

    /**
     * 컷 생성
     * - 파라메터 정보 정리
     */
    private function _cglist_cut_create() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        $project_seq = nvl($project_seq);
        $sheet_seq = $this->input->post('sheet_seq');
        $sheet_seq = nvl($sheet_seq);
        $count = $this->input->post('count');
        $count = nvl_no($count);
        $position = $this->input->post('position');
        $position = nvl($position,"d");
        $sort_no = $this->input->post('sort_no');
        $sort_no = nvl_no($sort_no,0);
        $sub_sort_no = $this->input->post('sub_sort_no');
        $sub_sort_no = nvl_no($sub_sort_no,1);

        // 필수 파라메터 처리
        if( $project_seq != "" && $sheet_seq != "" && $count !== false ) {
            // 시트의 컬럼 정보를 가져온다
            $sheetInfo = $this->projectModel->selectSheet( $sheet_seq, $project_seq );
            $columnList = json_decode( $sheetInfo['COLUMN_INFO'], true );
            if( !is_null($sheetInfo) && !is_null($columnList) ){
                // 시트 컬럼 정보를 기반으로 빈 cut 정보 생성
                $cut = array();
                foreach( $columnList['data_list'] as $columnInfo ) {
                    $cut[$columnInfo["key"]] = "";
                }
                $cut = json_encode($cut);

                /**
                 * sort_no가 빈값인 경우 0으로 처리하며, 신규 sort_no를 발행하여 컷 정보 생성
                 * sort_no가 있는 경우, sub_sort_no를 발행하여 컷 정보 생성 후, 같은 sort_no그룹의 sub_sort_no를 업데이트
                 */
                if( $sort_no == 0 ) {    
                    // sort_no 없는 경우, 마지막 sort_no 값을 가져온다
                    $tempInfo = $this->projectModel->selectCut_lastSortNo( $sheet_seq );
                    $last_sort_no = (int)$tempInfo['SORT_NO'];
                    $sub_sort_no = 1;
                    
                    $create_cut_list = array();
                    for( $i=0; $i<$count; $i++ ){
                        $cut_seq = $this->projectModel->insertCut($sheet_seq, $cut, $last_sort_no, $sub_sort_no);
                        $temp = array();
                        $temp['SEQ'] = $cut_seq;
                        $temp['SORT_NO'] = $last_sort_no;
                        $temp['SUB_SORT_NO'] = $sub_sort_no;
                        $temp['DATA'] = $cut;
                        $temp['SHEET_SEQ'] = $sheet_seq;
                        $temp['ADD_DATE'] = date("Y-m-d H:i:s");
                        $temp['MOD_DATE'] = $temp['ADD_DATE'];
                        array_push($create_cut_list, $temp);
                        $last_sort_no++;
                    }
                    $result['result'] = true;
                    $result['data'] = $create_cut_list;
                } else {
                    // 트랜잭션 시작
                    $this->db->trans_begin();

                    // 기존 자료 정렬번호 일괄 수정
                    $this->projectModel->updateCutSortInfo( $sheet_seq, $sort_no, $sub_sort_no, $count, $position );
                    $updated_cut_list = $this->projectModel->selectCutSortGroupList( $sheet_seq, $sort_no, $sub_sort_no, $position );

                    // 신규 컷 생성
                    $sub_sort_no = $position == "d" ? ($sub_sort_no+1) : $sub_sort_no;
                    $create_cut_list = array();
                    $sort_no = (int)$sort_no;
                    for( $i=0; $i<$count; $i++ ){
                        $cut_seq = $this->projectModel->insertCut($sheet_seq, $cut, $sort_no, $sub_sort_no);
                        $temp = array();
                        $temp['SEQ'] = $cut_seq;
                        $temp['SORT_NO'] = $sort_no;
                        $temp['SUB_SORT_NO'] = $sub_sort_no;
                        $temp['DATA'] = $cut;
                        $temp['SHEET_SEQ'] = $sheet_seq;
                        $temp['ADD_DATE'] = date("Y-m-d H:i:s");
                        $temp['MOD_DATE'] = $temp['ADD_DATE'];
                        array_push($create_cut_list, $temp);
                        $sub_sort_no++;
                    }
    
                    if( $this->db->trans_status() === TRUE ) {
                        $result['result'] = true;
                        $result['data'] = array_merge($create_cut_list, $updated_cut_list);
                        $this->db->trans_commit();
                    }else {
                        $result['result'] = false;
                        $result['error_code'] = AR_PROCESS_ERROR[0];
                        $result['message'] .= AR_PROCESS_ERROR[1];
            
                        $this->db->trans_rollback();
                    }
                }
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
                $result['message'] .= " - 프로젝트 정보가 없습니다";
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }//     EO      if( nvl_no($count) )

        echo json_encode( $result );
    }//     EOF     private function _cglist_cut_create()

    /**
     * 컷 수정
     */
    private function _cglist_cut_update() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        $project_seq = nvl($project_seq);
        $sheet_seq = $this->input->post('sheet_seq');
        $sheet_seq = nvl($sheet_seq);
        $data = $this->input->post('data');
        
        if ( $project_seq != "" && $sheet_seq != "" && is_json($data) ) {
            $this->db->trans_begin();

            $data = json_decode( $data, true );
            $this->projectModel->updateCut($project_seq, $sheet_seq, $data );

            if( $this->db->trans_status() === TRUE ) {
                $result['result'] = true;
                // $result['data'] = $data;
                $this->db->trans_commit();
            }else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
    
                $this->db->trans_rollback();
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode( $result );
    }//     EOF     private function _cglist_cut_update()

    /**
     * 컷 삭제
     */
    private function _cglist_cut_delete() {
        $result = array();

        $project_seq = $this->input->post('project_seq');
        $project_seq = nvl($project_seq);
        $sheet_seq = $this->input->post('sheet_seq');
        $sheet_seq = nvl($sheet_seq);
        $cut_seq_list = $this->input->post('cut_seq_list');
        $cut_seq_list = explode(",", $cut_seq_list);
        
        $cut_seq_validate = true;
        foreach( $cut_seq_list as $idx=>$cut_seq ) {
            if( !is_numeric($cut_seq) ) {
                $cut_seq_validate = false;
                break;
            }
        }
        
        if ( $project_seq != "" && $sheet_seq != "" && $cut_seq_validate ) {
            // 삭제 쿼리 실행
            $deleteCut_affected_rows = $this->projectModel->deleteCut($project_seq, $sheet_seq, $cut_seq_list);

            if( $deleteCut_affected_rows > 0 ) {
                $result['result'] = true;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
                $result['message'] .= " - 실행 결과가 0건입니다";
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode( $result );
    }//     EOF     private function _cglist_cut_delete()

}//     EOC     class Cglist extends CI_Controller