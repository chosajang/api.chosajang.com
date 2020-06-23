<?php

class My_Common_Library {
    var $CI; // CodeIgniter 리소스 접근용 객체

    function __construct()
    {
        $this->CI =& get_instance(); // CodeIgniter 리소스 원본 할당
        $this->CI->load->model('memberModel');
        $this->CI->load->model('fileModel');
    }

    /**
     * get/post 파라메터 처리
     */
    function get_post( $param_key ) {
        $param = $this->CI->input->get( $param_key );
        $param = is_null($param) ? $this->CI->input->post( $param_key ) : $param;
        return $param;
    }//     EOF     function get_post( $param_value )

    /** 
     * 로그인 세션 체크
     */
    function session_check() {
        $result = false;
        $member_seq = $this->CI->input->get('member_seq');
        $session_id = $this->CI->input->get('session_id');
        
        $member_seq = nvl($member_seq) != '' ? $member_seq : $this->CI->input->post('member_seq');
        $session_id = nvl($session_id) != '' ? $session_id : $this->CI->input->post('session_id');

        if ( nvl($member_seq,'') != '' && nvl($session_id,'') != '' ) {
            $info = $this->CI->memberModel->selectMember( $member_seq, TRUE );
            $member_status_seq = (int)$info['MEMBER_STATUS_SEQ'];
            // 요청 세션과 회원 세션정보가 일치 한지 확인
            if ( !is_null($info) && @$info['SESSION_ID'] === $session_id && $member_status_seq === MEMBER_STATUS_ACCESS ) { 
                $member_grade_seq = (int)$info['MEMBER_GRADE_SEQ'];
                // 회원 등급으로 가능한 요청인지 확인
                if ( $member_grade_seq === SITE_MANAGER ) {
                    // 시스템 관리자
                    $result = true;
                }
            }
        }

        return $result;
    }//     EOF     function session_check()

    /**
     * 파일 업로드 경로 생성기
     */
    function upload_path_generator() {
        $upload_path = UPLOAD_DIR . date('Y') . DIRECTORY_SEPARATOR . date('md') . DIRECTORY_SEPARATOR;

        return $upload_path;
    }//       EOF       function upload_path_generator()

    /**
     * 파일 업로드
     * 
     * - 필수 파라메터 : 
     *  > MEMBER_SEQ - 회원 시퀀스 번호
     *  > FILE - 업로드 파라메터명($_FILES['{NAME}'])
     * - 옵션 파라메터 : 
     *  > PROJECT_SEQ - 프로젝트 시퀀스가 있을 경우, 프로젝트 폴더 밑으로 업로드 경로 변경
     */
    function file_upload( $upload_info ) {
        $file_info_list = array();

        if ( array_key_exists('MEMBER_SEQ',$upload_info) && array_key_exists('FILE',$upload_info) && count($_FILES) > 0 ) {
            $f = $upload_info['FILE'];
            
            $upload_path = $this->upload_path_generator();
            $file_full_path = DOCUMENT_ROOT . DATA_DIR . $upload_path;

            if (@mkdir($file_full_path, 0777, true)) {
                if (is_dir($file_full_path)) {
                    @chmod($file_full_path, 0777, true);
                }
            }
            
            // 다중 파일 확인
            if ( is_array($_FILES[$f]['name']) ) {
                foreach ( $_FILES[$f]['name'] as $idx=>$file_name ) {
                    if( nvl($file_name,'') != '' ) {
                        // 미디어 파일타입 확인
                        $file_info = array();
                        $physical_name = $this->file_name_generator();
                        $file_mimetype = $_FILES[$f]['type'][$idx];
                        $file_tmp_name = $_FILES[$f]['tmp_name'][$idx];
                        $file_size = $_FILES[$f]['size'][$idx];

                        // 파일 타입에 따라 업로드 진행
                        /**
                         * TODO : video, document 타입 처리 필요
                         */
                        $upload_status = true;
                        if( strpos($file_mimetype, "image") != FALSE ) {
                            $image_info = $this->image_process( $file_tmp_name, $upload_path, $physical_name );
                            if( $image_info != FALSE ) {
                                // 썸네일 정보 추가
                                $file_info = $image_info;
                                $physical_name = $image_info['PHYSICAL_NAME'];
                            } else {
                                // 올바른 이미지가 아닌 경우
                                $upload_status = FALSE;
                            }
                        }

                        if ( $upload_status === true && move_uploaded_file( $file_tmp_name, $file_full_path . $physical_name ) ) {
                            $file_info['MEMBER_SEQ'] = $upload_info['MEMBER_SEQ'];
                            $file_info['LOGICAL_NAME'] = $file_name;
                            $file_info['PHYSICAL_NAME'] = $physical_name;
                            $file_info['PATH'] = $upload_path;
                            $file_info['SIZE'] = $file_size;
                            $file_info['MIMETYPE'] = $file_mimetype;

                            $file_info["INFO"] = json_encode($file_info);
        
                            // 파일정보 입력
                            $file_seq = $this->CI->fileModel->insertFile($file_info);
                            $file_info['PATH'] = DATA_DIR . $file_info['PATH'];
                            $file_info['FILE_SEQ'] = $file_seq;
                            array_push($file_info_list, $file_info);
                        }
                    }
                }
            } else {
                $tmp_file_info = $_FILES[$f];
                if( nvl($tmp_file_info['name'],'') != '' ) {
                    // 미디어 파일타입 확인
                    $file_info = array();
                    $physical_name = $this->file_name_generator();
                    $file_type = $tmp_file_info['type'];

                    // 파일 타입에 따라 업로드 진행
                    /**
                     * TODO : video, document 타입 처리 필요
                     */
                    $upload_status = true;
                    if( strpos($file_type, "image") != FALSE ) {
                        $image_info = $this->image_process( $tmp_file_info["tmp_name"], $upload_path, $physical_name );
                        if( $image_info != false ) {
                            // 썸네일 정보 추가
                            $file_info = $image_info;
                            $physical_name = $image_info['PHYSICAL_NAME'];
                        } else {
                            // 올바른 이미지가 아닌 경우
                            $upload_status = false;
                        }
                    }

                    if ( $upload_status === true && move_uploaded_file( $tmp_file_info['tmp_name'], $file_full_path . $physical_name ) ) {
                        $file_info['MEMBER_SEQ'] = $upload_info['MEMBER_SEQ'];
                        $file_info['LOGICAL_NAME'] = $tmp_file_info['name'];
                        $file_info['PHYSICAL_NAME'] = $physical_name;
                        $file_info['PATH'] = $upload_path;
                        $file_info['SIZE'] = $tmp_file_info['size'];
                        $file_info['MIMETYPE'] = $tmp_file_info['type'];

                        $file_info["INFO"] = json_encode($file_info);
    
                        // 파일정보 입력
                        $file_seq = $this->CI->fileModel->insertFile($file_info);
                        $file_info['PATH'] = DATA_DIR . $file_info['PATH'];
                        $file_info['FILE_SEQ'] = $file_seq;
                        array_push($file_info_list, $file_info);
                    }
                }
            }//     EO      if ( is_array($_FILES[$f]['name']) )
        }//     EO      if ( array_key_exists('MEMBER_SEQ',$upload_info) && array_key_exists('FILE',$upload_info) && count($_FILES) > 0 )

        return $file_info_list;
    }//     EOF     function file_upload( $files )

    /**
     * file_download
     */
    function file_download( $file_info ) {
        $file_path = DOCUMENT_ROOT . $file_info['PATH'];
        $file_name = $file_info['PHYSICAL_NAME'];
        $target_file = $file_path.$file_name;

        if( file_exists($target_file) ) {
            $filesize = filesize( $target_file );
            header("Content-Type:application/octet-stream");
            header("Content-Disposition:attachment;filename=".$file_info['LOGICAL_NAME']);
            header("Content-Transfer-Encoding:binary");
            header("Content-Length:".$filesize);
            header("Cache-Control:cache,must-revalidate");
            header("Pragma:no-cache");
            header("Expires:0");
            if( is_file( $target_file ) ){
                $fp = fopen( $target_file, "r" );
                while( !feof($fp) ) {
                    $buf = fread($fp,8096);
                    $read = strlen($buf);
                    print($buf);
                    flush();
                }
                fclose( $fp );
                exit();
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_EMPTY_REQUEST[0];
            $result['message'] = AR_EMPTY_REQUEST[1];
            $result['message'] .= " - 경로상 파일이 존재하지 않습니다";

            return $result;
        }
    }//     EOF     function file_download( $file_info )

    /**
     * image_thumb_process
     */
    function image_process( $org_tmp_file, $upload_path, $save_name ) {
        $image_info = @getimagesize( $org_tmp_file );
        $image_type = $image_info['mime'];

        if( is_array( $image_info ) ) {
            if( $image_type === 'image/gif' ) {
                $save_name .= '.gif';
            } else if( $image_type === 'image/png' ) {
                $save_name .= '.png';
            } else if( $image_type === 'image/jpeg' ) {
                $save_name .= '.jpg';
            } else if( $image_type === 'image/x-icon' ) {
                $save_name .= '.ico';
            } else { 
                // 현재 지원하지 않는 이미지 타입
                return false;
            }
            
            // 썸네일 경로 및 파일명 생성
            $pathInfo = pathinfo($save_name);
            $thumb_file_name = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            $thumb_save_path = $upload_path . $thumb_file_name;

            // 원본 파일 업로드 전이기에 임시 파일로 썸네일 생성
            $result = array();
            $result['PHYSICAL_NAME'] = $save_name;
            $result['FILE_WIDTH'] = $image_info[0];
            $result['FILE_HEIGHT'] = $image_info[1];
            $result['THUMB_FILE_NAME'] = $thumb_file_name;
            $result['THUMB_INFO'] = $this->thumbnail( $org_tmp_file, $image_info, $thumb_save_path, 200, 200 );

            return $result;
        } else {
            // 올바른 이미지가 아님
            return false;
        }
    }//     EOF     function image_process( $tmp_file_info, $upload_path, $save_name )

    /**
     * 파일 이름 자동생성 함수
     */
    function file_name_generator() {
        $file_name = date('His') . '_' . substr((string)microtime(), 2, 8) . mt_rand(1, 10000);
        return $file_name;
    }//       EOF       function file_name_generator()

    /**
     *
     */
    function uri_noise_removal($function) {
        $segment_array = $this->CI->uri->segment_array();
        $uri = '';
        $break_check = false;
        foreach( $segment_array as $segment ) {
            $uri .= $segment;
            if($segment == $function) { $break_check = true; break; }
            if($segment != $function) { $uri .= '/'; }
        }
        if( !$break_check ) { $uri .= $function; }
        return $uri;
    }//       EOF       function uri_noise_removal($function)

    
    /**
     * 썸네일 이미지 생성
     */
    function thumbnail($org_file_path,$org_img_info,$save_file_path,$max_width,$max_height) {
        // $org_file_pullpath = DOCUMENT_ROOT . DATA_DIR . $org_file_path;
        $org_file_pullpath = $org_file_path;
        $org_file_mimetype = $org_img_info['mime'];

        $process_status = true;
        if( $org_file_mimetype == 'image/jpeg' ) {
            $tmp_img = imagecreatefromjpeg($org_file_pullpath);
        }else if( $org_file_mimetype == 'image/png' ) {
            $tmp_img = imagecreatefrompng($org_file_pullpath);
        }else if( $org_file_mimetype == 'image/gif' ) {
            $tmp_img = imagecreatefromgif($org_file_pullpath);
        }

        $org_img_width = $org_img_info[0];
        $org_img_height = $org_img_info[1];

        $target_img_width = 200;
        $target_img_height = 200;

        if( ($org_img_width/$max_width) == ($org_img_height/$max_height) ) {// 넓이/높이가 같은 경우,
            $target_img_width = $max_width;
            $target_img_height = $max_height;
        }else if( ($org_img_width/$max_width) < ($org_img_height/$max_height) ) {// 세로가 긴 경우,
            $target_img_width = $max_height * ($org_img_width/$org_img_height);
            $target_img_height = $max_height;
        }else{// 그 외(가로가 긴 경우)
            $target_img_width = $max_width;
            $target_img_height = $max_width * ($org_img_height/$org_img_width);
        }

        $thumb_img = imagecreatetruecolor($target_img_width,$target_img_height);

        imagecopyresized($thumb_img,$tmp_img,0,0,0,0,$target_img_width,$target_img_height,$org_img_width,$org_img_height);
        imageinterlace($thumb_img);

        $save_file_pullpath = DOCUMENT_ROOT . DATA_DIR . $save_file_path;
        imagejpeg($thumb_img,$save_file_pullpath);
        imagedestroy($thumb_img);
        imagedestroy($tmp_img);

        $thumbnail_info = array(
                        'FILE_PATH'=>$save_file_path,
                        'WIDTH'=>$target_img_width,
                        'HEIGHT'=>$target_img_height
        );

        return $thumbnail_info;
    }//     EOF     function thumbnail($file,$image_info,$save_filename,$max_width,$max_height)

    public function postCURL($_url, $_param) {

        $postData = '';
        //create name value pairs seperated by &
        foreach($_param as $k => $v)
        {
            $postData .= $k . '='.$v.'&';
        }
        rtrim($postData, '&');


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, count($postData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $output=curl_exec($ch);

        curl_close($ch);

        return $output;
    }//     EOF     public function postCURL($_url, $_param)

    /**
     * 컬럼 키 생성
     * COLUMN_KEY + NUMBER
     * EX) KEY0001
     * 
     * 20.02.14
     * - COLUMN_KEY 변경 되었을 경우, 새로운 키를 발급하기 위한 프로세스 수정
     */
    public function column_key_generator( $last_key ) {
        $last_key = is_null(strpos($last_key, COLUMN_KEY)) ? COLUMN_KEY . "0000" : $last_key;
        $key_number = (int)str_replace(COLUMN_KEY, "",$last_key);
        $key_number = str_pad( ++$key_number, 4, "0", STR_PAD_LEFT );

        return COLUMN_KEY . $key_number;
    }//     EOF     public function column_key_generator( $last_key )

}//         EOC     class My_Common_Library

