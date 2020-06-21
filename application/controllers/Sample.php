<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sample extends CI_Controller {
     
     public $method_prefix = '';
     
     public function __construct() {
          parent::__construct();
          $this->method_prefix = '_sample_';
          // Layout Setting & Load
          #$this->layout->setLayout('base_layout');
          // Model Load
          #$this->load->model('sample/SampleModel','SampleModel');
          #$this->load->model('memberModel');
          #$this->load->model('taskModel');
          #$this->load->model('cglistModel');
          $this->load->model('fileModel');
     }
    
     /*
      * 
      */
     public function _remap($function) {
          $method = $this->method_prefix . $function;
          
          $this->result_data['page_name'] = $function;
          
          // 요청 컨트롤러가 존재하는 확인
          if (method_exists($this, $method)) {
               /*
                * TODO : 로그인 인증 확인
                */
                header_cors();
               // 요청 컨트롤러 호출
               $this->{$this->method_prefix.$function}();
          } else {
               $message = "올바른 요청이 아닙니다";
               $sendData = array('message'=>$message);
               $this->layout->view('common/error', $sendData);
          }
     }//          EOF          function _remap()
     
     private function _sample_info() {
          echo 'sample/info';
     }
     
     private function _sample_login() {
          
          $cookie = array(
                    'name'   => 'TEST_NAME',
                    'value'  => 'The Value',
                    'expire' => '86500',
                    'domain' => 'erp.local.com',
                    'path'   => '/',
                    'prefix' => 'myprefix_'
          );
          
          set_cookie($cookie);
          
          $this->layout->setLayout('blank_layout');
          $this->layout->view('sample/login');
     }
      
     private function _sample_index() {
          
          $this->layout->setLayout('blank_layout');
          
          $getName = get_cookie('myprefix_TEST_NAME');
          
          printR($getName);
          
          // Paramter
          $member_seq= $this->input->post('test');
          $sendData = array('test'=>$member_seq);
          
          // 회원정보 호출
          // $member_seq = 4;
          $memberInfo = $this->SampleModel->selectMember($member_seq);
          // printR($memberInfo);
          
          // $this->layout->view('sample/index', $sendData);
          
          $memberList_Result = $this->SampleModel->selectMemberList();
          
          // printR($memberList_Result['COUNT']);
          
          // printR($memberList_Result['MEMBER_LIST']);
          $sendData = array('MEMBER_LIST'=>$memberList_Result['MEMBER_LIST']);
          
          $this->layout->view('sample/index',$sendData);
     }//       EOF       function index()
     
     function _sample_test() {
          $this->layout->setLayout('blank_layout');
          
          $this->layout->view('sample/test', $this->result_data);
     }
     
     function _sample_test2() {
          // $this->layout->setLayout('blank_layout');
     
          //$this->layout->view('sample/test', $this->result_data);
          $this->load->view('');
     }

     function _sample_session_id(){
         $result['session_id'] = session_id();
         // $result['random_string'] = random_string('sha1');
         echo json_encode($result);
     }
     
     function _sample_session(){
          @session_start();
          printR( session_save_path() );
          
          echo 'CodeIgniter Session';
          $this->session->set_userdata('TEST','TEST_MESSAGE');
          
          $session_data = array('MEMBER'=>
                                             array(  'MEMBER_SEQ'=>3,
                                                       'MEMBER_ID'=>'enjoysoft@cocoavision.co.kr' )
                                   );
          // $this->member_session_save
          $this->session->set_userdata($session_data);
          
          $session_data = $this->session->all_userdata();
          printR($session_data);
          
          echo 'PHP Session';
          $_SESSION['T_SESSION'] = 'TEST MESSAGE';
          printR($_SESSION['T_SESSION']);
          
          printR(session_id());
          
          echo $_SERVER["HTTP_HOST"];
          // echo '<script>location.href="/sample/session2";</script>';
     }
     
     function _sample_session2(){
          echo 'CodeIgniter Session - ' . session_cache_expire();
          $session_data = $this->session->all_userdata();
          printR($session_data);
          
          echo 'PHP Session';
          printR(@$_SESSION['T_SESSION']);
          
          printR(session_id());
          
          // echo '<br/>PHPSESSID - ' . $PHPSESSID;
          // printR(sys_get_temp_dir());
          // echo '<script>alert(document.cookie);</script>';
     }
     
     function _sample_fake_login(){
         printR('# fake_login');
         $id = "test@cocoavision.co.kr";
         $password = "test1234";
         
         // 유효성검사
         // ID, PW를 받아서 DB로 회원정보조회
         if( !is_null($id) && !is_null($password) ) {
             // 회원 비밀번호 비교
             $member_temp_info = $this->memberModel->selectMemberForID($id);
             printR( $member_temp_info );
             if( password_verify($password, $member_temp_info['PASSWORD']) ) {
                 // 회원정보 조회
                 $member_info = $this->memberModel->selectMemberForSeq($member_temp_info['SEQ']);
                 if( !is_null($member_info) ) {
                     // 회원로그인 정보 업데이트
                     $session_id = random_string('sha1');
                     $this->memberModel->updateMemberLogin($member_info['SEQ'], $session_id);
                     // 파일정보조회
                     $files_dao = $this->filesModel->selectFilesForSeq($member_info['PROFILE_FILES_SEQ']);
                     $profile_img = $files_dao['FILE_PATH'] . $files_dao['FILE_NAME'];
                     
                     // 회원정보 세션 입력
                     $member_session_data = array(
                                     'MEMBER_SEQ'=>$member_info['SEQ'],
                                     'MEMBER_ID'=> $member_info['ID'],
                                     'NAME'=>$member_info['NAME'],
                                     'MEMBER_TEAM_NAME'=>$member_info['MEMBER_TEAM_NAME'],
                                     'MEMBER_TITLE_NAME'=>$member_info['MEMBER_TITLE_NAME'],
                                     'PROFILE_IMG'=>$profile_img,
                                     'MEMBER_GRADE_SEQ'=>$member_info['MEMBER_GRADE_SEQ'],
                                     'MEMBER_GRADE_NAME'=>$member_info['MEMBER_GRADE_NAME'],
                                     'SESSION_ID'=>$session_id );
                     $this->my_common_library->member_session_save($member_session_data);
                     
                     // 메인/대쉬보드로 이동
                     // echo '<script>location.href="/main/dashboard";</script>';
                     printR('Login Success!');
                 } else {
                     // 회원정보 조회 실패
                     printR('alert:회원정보 조회 실패');
                 }
             } else {
                 // 비밀번호 틀림
                 printR('alert:비밀번호 틀림');
             }
         } else {
             // 로그인 페이지로 이동
             printR('move:로그인 페이지로 이동'); 
         }
     }//        EOF     function _sample_fake_login()
     
     function _sample_mkdir(){
         $dir = $this->input->get('dir');
         $dir = nvl($dir,"sample");
         
         $file_full_path = DOCUMENT_ROOT . DATA_DIR . '/test/' . $dir;
         printR($file_full_path);

         $dirs = explode(DIRECTORY_SEPARATOR, $file_full_path);
         $item = '';
         $created = false;
         printR($dirs);
         foreach ( $dirs as $part ) {
             $item .= $part . DIRECTORY_SEPARATOR;
             printR($item);
             if( !is_dir($item) && strlen($item) > 0 ) {
                 $created = mkdir($item, 0744, true);
             }
         }
         if( $created ){
             printR('success');
         }else{
             printR('failed');
         }
     }//        EOF     function _sample_mkdir()
     
     
     private function _sample_createForm_teamlist(){
         $team_seq_list = $this->input->post("team_seq_list") == null ? array() : $this->input->post("team_seq_list");
         
         $task_team_list = $this->taskModel->selectMemberTeamList( $team_seq_list );
         
         return exit(json_encode($task_team_list));
     }//        EOF     private function _task_createForm_teamlist()
     
     private function _sample_progressnote_write_process(){
         $task_seq = $this->input->post('task_seq');
         $contents = nvl($this->input->post('contents'),'');
         $progress = nvl($this->input->post('progress'),0);
         $working_hours = nvl($this->input->post('working_hours'),0);
         
         // $member_session = $this->view_data['MEMBER_SESSION'];
         $member_seq = null;
         
         $task_activity_info = array(
             'TASK_SEQ' => $task_seq,
             'CONTENTS' => $contents,
             'PROGRESS' => $progress,
             'WORKING_HOURS' => $working_hours,
             'MEMBER_SEQ' => $member_seq
         );
         
         // 프로그레스 노트 등록
         $task_activity_info['TASK_ACTIVITY_SEQ'] = $this->taskModel->insertTaskActivity($task_activity_info);
         
         return exit(json_encode($task_activity_info));
     }//        EOF     private function _task_progressnote_write_process()
     
     private function _sample_create_process(){
         $project_seq = 15;
         $cglist_seq = $this->input->post('cglist_seq');
         $cglist_column_seq = $this->input->post('cglist_column_seq');
         
         $task_no = $this->input->post('task_no');
         $member_seq = $this->input->post('member_seq');
         $task_type_seq = $this->input->post('task_type_seq');
         $start_date = $this->input->post('start_date');
         $end_date = $this->input->post('end_date');
         
         // 업무 등록
         $task_info['PROJECT_SEQ'] = $project_seq;
         $task_info['CGLIST_SEQ'] = $cglist_seq;
         
         $task_info['TASK_NO'] = $task_no;
         $task_info['MEMBER_SEQ'] = $member_seq;
         $task_info['TASK_TYPE_SEQ'] = $task_type_seq;
         $task_info['START_DATE'] = $start_date;
         $task_info['END_DATE'] = $end_date;
         
         print_r($task_info);
         // $task_seq = $this->taskModel->insertTask($task_info);
         
         /*
          * CG List Cell 정보 등록
          * - 등록할 회원 정보 조회
          * - 기존 Cell 정보 조회
          * - Cell 정보 입력
          *  > CG List Updater 정보 생성
          *  > Cell 등록 정보 생성
          */
         // 등록할 회원 정보 조회
         $member_dao = $this->memberModel->selectMemberForSeq($member_seq);
         $member_task_info = array(
             'TASK_SEQ'=>166,
             'MEMBER_SEQ'=>$member_seq,
             'MEMBER_NAME'=>$member_dao['NAME']
         );
         
         // 기존 Cell 정보 조회
         $cglist_cell_info = $this->cglistModel->selectCGListInfoByColumnSeq($cglist_column_seq,$cglist_seq);
         $cglist_cell_info_list = json_decode($cglist_cell_info);
         array_push($cglist_cell_info_list,$member_task_info);
         
         $cell_data = json_encode($cglist_cell_info_list);
         
         // Cell 정보 입력 : CG List Updater 정보 생성
         $member_seq = 1;
         $member_name = '조현희 팀장';
         $cglist_info_updater = array( 
             'MEMBER_SEQ'=>$member_seq,
             'MEMBER_NAME'=>$member_name,
             'UPDATE_DATE'=>date("Y-m-d H:i:s") 
         );
         
         // Cell 정보 입력 : Cell 등록 정보 생성
         $cell_info = array(
             'PROJECT_SEQ'=>$project_seq,
             'CGLIST_SEQ'=>$cglist_seq,
             'COLUMN_SEQ'=>$cglist_column_seq,
             'CELL_DATA'=>$cell_data,
             'MEMBER_SEQ'=>1,
             'CGLIST_INFO'=>json_encode($cglist_info_updater),
             'HISTORY_YN'=>'Y'
         );
         
         $result = $this->cglistModel->updateCell($cell_info);
         
         return exit(json_encode(array('RESULT'=>$result)));
     }//        EOF     private function _task_create_process()
     
     private function _sample_excel_down(){
         $this->load->library("PHPExcel");
         
         $objPHPExcel = new PHPExcel();
         $objPHPExcel->getProperties()->setCreator('COCOAVISION')
                                      ->setLastModifiedBy('')// 배포자
                                      ->setTitle('CG List')// 프로젝트명_CGList_날짜
                                      ->setSubject('CG List subject')//
                                      ->setDescription('TEST');
         
         $objPHPExcel->setActiveSheetIndex(0);// 시트 설정
         
         // 시트 정보 할당
         $objActiveSheet = $objPHPExcel->getActiveSheet();
         
         
         $objActiveSheet->getRowDimension(2)->setRowHeight(50);
         $objActiveSheet->setCellValue('B2','MESSAGE');
         
         // 문서 헤더 설정
         $file_name = 'Sample_'.date('Ymd').'.xlsx';
         
         header('Content-Type: application/vnd.ms-excel;charset=utf-8');
         // header('Content-Type: application/x-msexcel-excel;charset=utf-8');
         header('Content-Disposition: attachment; filename="'.$file_name.'"');
         header('Cash-Control: max-age=0');
         
         // $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
         $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
         $objWriter->save('php://output');
     }//        EOF     private function _sample_excel_down()
     
     private function _sample_libcheck(){
         echo "GD: ", extension_loaded('gd') ? 'OK' : 'MISSING', '<br>';
         echo "XML: ", extension_loaded('xml') ? 'OK' : 'MISSING', '<br>';
         echo "zip: ", extension_loaded('zip') ? 'OK' : 'MISSING', '<br>';
         
         printR(get_loaded_extensions());
     }//        EOF     private function _sample_lib_check()
     
    private function _sample_modifyForm_taskinfo(){
        $task_seq = $this->input->post('task_seq');
        $task_seq = 185;
        
        // 업무 정보
        $task_dao = $this->taskModel->selectTaskInfo($task_seq);
        printR($task_dao);
        // CG List 정보
        $modifyFrom_info['SELECTED_CGLIST_INFO'] = array( $this->cglistModel->selectCGListInfo($task_dao['CGLIST_SEQ']) );
        // 프로젝트에 등록된 업무 목록
        $modifyFrom_info['TASK_COLUMN_LIST'] = $this->taskModel->selectTaskColumnList($task_dao['PROJECT_SEQ']);
        // 최상위 팀 목록
        $modifyFrom_info['TASK_HIGHEST_TEAM_LIST'] = $this->taskModel->selectMemberTeamList(null);
        
        // CG List 컬럼 정보 조회
        $member_info = $this->memberModel->selectMemberForSeq($task_dao['MEMBER_SEQ']);
        
        // 회원이 속한 팀 목록 조회
        $member_team_list = $this->memberModel->selectMember_TeamList($task_dao['MEMBER_SEQ']);
        // 회원이 속한 최상위 팀 정보 조회(LEVEL:0)
        $highest_team_info = $member_team_list[ array_search('0',array_column($member_team_list,'LEVEL')) ];
        // 회원이 속한 팀 정보 조회(LEVEL:1)
        $team_info = $member_team_list[ array_search('1',array_column($member_team_list,'LEVEL')) ];
        // CG List 컬럼 정보 추출 
        $json_column_info = $modifyFrom_info['TASK_COLUMN_LIST'][ array_search($task_dao['CGLIST_COLUMN_SEQ'],array_column($modifyFrom_info['TASK_COLUMN_LIST'],'SEQ')) ]['COLUMN_INFO'];
        $selected_column_info = json_decode($json_column_info,TRUE);
        
        $info = array(
            'TEAM_SEQ_LIST'=>$selected_column_info['TEAM_SEQ_LIST'],
            'PARENT_TEAM_SEQ'=>$highest_team_info['MEMBER_TEAM_SEQ'],
            'EXTERNAL_YN'=>$highest_team_info['EXTERNAL_YN']
        );
        $modifyFrom_info['SELECTED_TASK_TEAM_LIST'] = $this->taskModel->selectMemberTeamList( $info );
        $modifyFrom_info["SELECTED_TEAM_TASKTYPE_LIST"] = $this->taskModel->selectTeamTaskTypeList($team_info['MEMBER_TEAM_SEQ']);
        $member_info = array(
            'PROJECT_SEQ'=>$task_dao['PROJECT_SEQ'],
            'MEMBER_TEAM_SEQ'=>$team_info['MEMBER_TEAM_SEQ']
        );
        $modifyFrom_info['SELECTED_MEMBER_LIST'] = $this->taskModel->selectMemberList($member_info);
        $modifyFrom_info['SELECTED_TEAM_INFO'] = $team_info;
        
        printR($modifyFrom_info);
         
//         return exit(json_encode($createFrom_info));
    }//        EOF     private function _task_modifyForm_taskinfo()
    
    private function _sample_excel(){
        $this->load->library("PHPExcel");
        
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator('COCOAVISION')
                                     ->setLastModifiedBy('')// 배포자
                                     ->setTitle('CG List')// 프로젝트명_CGList_날짜
                                     ->setSubject('CG List subject')//
                                     ->setDescription('TEST');
                                    
        $objPHPExcel->setActiveSheetIndex(0);// 시트 설정
        
        // 시트 정보 할당
        $objActiveSheet = $objPHPExcel->getActiveSheet();
        
        // 전체시트 스타일 설정
        $objActiveSheet->freezePaneByColumnAndRow(3,3);// 열 고정
        $objActiveSheet->getDefaultStyle()->getFont()->setName('맑은 고딕');
        $objActiveSheet->getDefaultStyle()->getFont()->setSize(8);
        $objActiveSheet->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        
        // ---------------- 제목 출력
        $objActiveSheet->mergeCells('A1:A2');// 셀 병합
        $objActiveSheet->setCellValue('A1','SEQ');
        $objActiveSheet->getColumnDimension('A')->setWidth(6); // 셀 넓이
        
        $objActiveSheet->setCellValue('A3','테스트 메세지1');
        $objActiveSheet->setCellValue('A4','테스트 메세지2');
        $objActiveSheet->setCellValue('B3','테스트');
        $objActiveSheet->setCellValue('B4','테스트');
        
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        // header('Content-Type: application/x-msexcel-excel;charset=utf-8');
        header('Content-Disposition: attachment; filename="TEXT.xlsx"');
        header('Cash-Control: max-age=0');
        
        // $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
        // $objWriter->save('php://output');
        $objWriter->save(__DIR__ . '/text.xlsx');
        
    }//     EOF     private function _sample_excel()

    private function _sample_fileupload() {
        $file_seq_list = $this->my_common_library->file_upload(array(
            'MEMBER_SEQ' => 47,
            'FILE' => 'profile_file'
        ));
        print_r($file_seq_list);
    }//     EOF     private function _sample_fileupload()

    /**
     * 파일 객체 샘플
     */
    private function _sample_fileread() {
        $file_seq = $this->input->get('file_seq');

        $fileInfo = $this->fileModel->selectFileForSeq($file_seq);

        // print_r($fileInfo);
        
        $file_pull_path = DOCUMENT_ROOT . $fileInfo['PATH'] . $fileInfo['PHYSICAL_NAME'];
        
        $fileObject = file($file_pull_path);
        
        // $result['FILE'] = $fileObject;

        // echo json_encode( $result );
    }//     EOF     private function _sample_fileread()

    /**
     * 업로드 경로 확인
     */
    private function _sample_fileUploadPath() {
        $upload_path = $this->my_common_library->upload_path_generator();
        echo DOCUMENT_ROOT . DATA_DIR . $upload_path;
    }
     
      
}//            EOC       class Sample extends CI_Controller {
