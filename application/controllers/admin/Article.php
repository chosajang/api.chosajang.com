<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 */
class Article extends CI_Controller {

    public $data = array();

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_article_';
        
        // Model Load
        $this->load->model('boardModel');
        $this->load->model('articleModel');
        $this->load->model('fileModel');

        // helper
        $this->load->helper('download');
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
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        echo json_encode($result);
    }//       EOF          public function _remap($function)

    /**
     * 게시물 목록
     */
    private function _article_list() {
        // 게시물 목록 조회
        $result['result'] = true;
        $result['data'] = $this->articleModel->selectArticle_list();

        echo json_encode($result);
    }//     EOF     private function _article_list()

    /**
     * 게시물 작성
     */
    private function _article_write() {
        $board_seq = $this->input->post('board_seq');
        $member_seq = $this->input->post('member_seq');
        $title = $this->input->post('title');
        $content = $this->input->post('content');
        
        $result = array();

        // 게시판 컨텐츠 입력
        $article_seq = $this->articleModel->insertArticle($board_seq, $member_seq, $title, $content, $notice_yn);

        if ( $article_seq ) {
            $upload_info = array(
                'MEMBER_SEQ'=>$member_seq,
                'FILE' => 'file'
            );
            $file_info_list = $this->my_common_library->file_upload( $upload_info );
            $upload_file_count = 0;
            foreach ( $file_info_list as $file_info ){
                // 게시판 내용 파일 입력
                $insertArticleFile_result = $this->articleModel->insertArticleFile($article_seq, $file_info['FILE_SEQ']);
                if ( $insertArticleFile_result ) { 
                    $upload_file_count++;
                }
            }
            
            $result['result'] = true;
            $result['message'] = '게시판 컨텐츠 입력 완료';
            $result['article_seq'] = $article_seq;
            $result['upload_file_count'] = $upload_file_count;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_PROCESS_ERROR[0];
            $result['message'] = AR_PROCESS_ERROR[1];
        }

        echo json_encode($result);
    }//     EOF     private function _article_write()

    /**
     * 게시물 수정
     */
    private function _article_modify() {
        $board_seq = $this->input->post('board_seq');
        $article_seq = $this->input->post('article_seq');
        $member_seq = $this->input->post('member_seq');
        $title = $this->input->post('title');
        $content = $this->input->post('content');
        $delete_file_seq_list = $this->input->post('delete_file_seq');
        $notice_yn = $this->input->post('notice_yn');

        // 관리자 권한없이 NOTICE_YN 값을 보낸 경우, 무조건 N으로 처리한다
        $board_info = $this->data['BOARD_INFO'];
        $notice_yn = $board_info['ADMIN_YN'] == 'Y' ? nvl($notice_yn,'N') : 'N';

        $result = array();
        
        if ( is_numeric($article_seq) && nvl($title,'') != '' ) {
            $article_info = $this->articleModel->selectArticle( $article_seq );    
            if( !is_null($article_info) ) {
                if( $article_info['NOTICE_YN'] == 'Y' 
                    || ( $notice_yn == 'Y' && $board_info['NOTICE_SET_YN'] == 'Y' ) 
                    || $notice_yn == 'N' ) {
                    if( $article_info['MEMBER_SEQ'] == $member_seq || $this->data['BOARD_INFO']['ADMIN_YN'] == 'Y' ) {
                        // 게시물 수정
                        $updateArticle_result = $this->articleModel->updateArticle($article_seq, $board_seq, $title, $content, $notice_yn);
                        if ( $updateArticle_result ){
                            // 요청 첨부파일 삭제
                            if ( is_array($delete_file_seq_list) ) {
                                $this->articleModel->deleteArticleFile($delete_file_seq_list);
                            }
                            // 신규 첨부파일 업로드
                            $upload_info = array(
                                'MEMBER_SEQ'=>$member_seq,
                                'FILE' => 'file'
                            );
                            $file_info_list = $this->my_common_library->file_upload( $upload_info );

                            $upload_file_count = 0;
                            foreach ( $file_info_list as $file_info ){
                                // 게시판 내용 파일 입력
                                $insertArticleFile_result = $this->articleModel->insertArticleFile($article_seq, $file_info['FILE_SEQ']);
                                if ( $insertArticleFile_result ) { 
                                    $upload_file_count++; 
                                }
                            }

                            $result['result'] = true;
                            $result['message'] = '게시물이 수정되었습니다';
                            $result['upload_file_count'] = $upload_file_count;
                        } else {
                            $result['result'] = false;
                            $result['error_code'] = AR_PROCESS_ERROR[0];
                            $result['message'] = AR_PROCESS_ERROR[1];
                        }
                    } else {
                        $result['result'] = false;
                        $result['error_code'] = AR_PROCESS_ERROR[0];
                        $result['message'] = AR_PROCESS_ERROR[1];
                        $result['message'] .= ' - 게시물 작성자가 아니거나 관리자 권한 없습니다';
                    }
                } else {
                    $result['result'] = false;
                    $result['error_code'] = AR_PROCESS_ERROR[0];
                    $result['message'] = AR_PROCESS_ERROR[1];
                    $result['message'] .= ' - 공지사항 설정 최대개수를 넘었습니다';
                }
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_EMPTY_REQUEST[0];
                $result['message'] = AR_EMPTY_REQUEST[1];
                $result['message'] .= ' - 삭제되었거나 없는 게시물입니다';
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }

        echo json_encode($result);
    }//     EOF     private function _article_modify()

    /**
     * 게시물 조회
     */
    private function _article_read() {
        $article_seq = $this->input->get('article_seq');
        $member_seq = $this->input->get('member_seq');

        $result = array();

        if ( is_numeric( $article_seq ) ) {    
            // 게시물 조회
            $article_info = $this->articleModel->selectArticle($article_seq);
            
            if ( !is_null($article_info) ) {
                // 게시물 편집권한 체크
                $board_info = $this->data['BOARD_INFO'];
                $edit_yn = "N";
                if( $board_info['ADMIN_YN'] == "Y" ) {
                    $edit_yn = "Y";
                } else if( $board_info['EDIT_YN'] == "Y" && $member_seq == $article_info['MEMBER_SEQ'] ) {
                    $edit_yn = "Y";
                } else {
                    $edit_yn = "N";
                }
                $article_info['EDIT_YN'] = $edit_yn;
                $article_info['ADMIN_YN'] = $board_info['ADMIN_YN'];
                // 게시물 첨부파일 조회
                $attachedFile_list = $this->articleModel->selectAttachedFile_list($article_seq);
                // 게시물 첨부문서 조회
                $attachedDocument_list = $this->articleModel->selectAttachedDocument_list($article_seq);
                // 댓글 목록
                $comment_list = $this->articleModel->selectArticleComment_list($article_seq);
                
                $result['result'] = true;
                $result['article'] = $article_info;
                $result['attachedFile_list'] = $attachedFile_list;
                $result['attachedDocument_list'] = $attachedDocument_list;
                $result['comment_list'] = $comment_list;
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
    }//     EOF     private function _article_read()

    /**
     * 게시물 삭제
     */
    private function _article_delete() {
        $article_seq = $this->input->post('article_seq');
        $member_seq = $this->input->post('member_seq');

        $result = array();

        if ( is_numeric($article_seq) ) {
            // 게시물 작성자 or 관리자 인증
            $article_info = $this->articleModel->selectArticle( $article_seq );
            
            if( !is_null($article_info) ) {
                if( $article_info['MEMBER_SEQ'] == $member_seq || $this->data['BOARD_INFO']['ADMIN_YN'] == 'Y' ) {
                    // 게시물 삭제
                    $deleteArticle_result = $this->articleModel->deleteArticle( $article_seq );
    
                    if ( $deleteArticle_result ) {
                        $result['result'] = true;
                        $result['message'] = '게시물 삭제 완료';
                    } else {
                        $result['result'] = false;
                        $result['error_code'] = AR_PROCESS_ERROR[0];
                        $result['message'] = AR_PROCESS_ERROR[1];
                    }
                } else {
                    $result['result'] = false;
                    $result['error_code'] = AR_PROCESS_ERROR[0];
                    $result['message'] = AR_PROCESS_ERROR[1];
                    $result['message'] .= ' - 게시물 작성자가 아니거나 관리자 권한 없습니다';
                }
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
    }//     EOF     private function _article_delete()

    /**
     * 댓글 작성
     */
    private function _article_comment_write() {
        $article_seq = $this->input->post('article_seq');
        $member_seq = $this->input->post('member_seq');
        $content = $this->input->post('content');

        $result = array();

        if ( is_numeric($article_seq) && is_numeric($member_seq) && nvl($content,'') !== '' ) {
            // 댓글 등록
            $insertArticleComment_result = $this->articleModel->insertArticleComment($article_seq, $member_seq, $content);
            if ( $insertArticleComment_result !== false )  {
                $commentInfo = $this->articleModel->selectComment( $insertArticleComment_result );

                $result['result'] = true;
                $result['message'] = '댓글 등록 완료';
                $result['data'] = $commentInfo;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }//     EO      if ( is_numeric($article_seq) && is_numeric($member_seq) && nvl($content,'') !== '' )

        echo json_encode($result);
    }//     EOF     private function _article_comment_write()

    /**
     * 댓글 수정
     */
    private function _article_comment_modify() {
        $comment_seq = $this->input->post('comment_seq');
        $member_seq = $this->input->post('member_seq');
        $content = $this->input->post('content');

        $result = array();

        if ( is_numeric($comment_seq) && is_numeric($member_seq) && nvl($content,'') !== '' ) {
            $updateArticleComment_result = $this->articleModel->updateArticleComment($comment_seq, $member_seq, $content);
            if ( $updateArticleComment_result ) {
                $result['result'] = true;
                $result['message'] = '댓글 수정 완료';
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }//     EO      if ( is_numeric($article_seq) && is_numeric($member_seq) && nvl($content,'') !== '' )

        echo json_encode($result);
    }//     EOF     private function _article_comment_modify()

    /**
     * 댓글 삭제
     */
    private function _article_comment_delete() {
        $comment_seq = $this->input->post('comment_seq');
        $member_seq = $this->input->post('member_seq');

        $result = array();

        if ( is_numeric($comment_seq) && is_numeric($member_seq) ) {
            $deleteArticleComment_result = $this->articleModel->deleteArticleComment($comment_seq, $member_seq);
            if ( $deleteArticleComment_result ) {
                $result['result'] = true;
                $result['message'] = '댓글 삭제 완료';
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_PROCESS_ERROR[0];
                $result['message'] = AR_PROCESS_ERROR[1];
            }
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }//     EOF     if ( is_numeric($comment_seq) && is_numeric($member_seq) )

        echo json_encode($result);
    }//     EOF     private function _article_comment_delete()

    /**
     * 댓글 목록
     */
    private function _article_comment_list() {
        $article_seq = $this->input->get('article_seq');

        $result = array();

        if ( is_numeric($article_seq) ) {
            // 댓글 목록
            $comment_list = $this->articleModel->selectArticleComment_list($article_seq);

            if( !is_null( $comment_list['COMMENT_LIST'] ) ) {
                $result['result'] = true;
                $result['comment_list'] = $comment_list;
            } else {
                $result['result'] = false;
                $result['error_code'] = AR_EMPTY_REQUEST[0];
                $result['message'] = AR_EMPTY_REQUEST[1];
            }            
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }//     EO      if ( is_numeric($article_seq) )

        echo json_encode($result);
    }//     EOF     private function _article_comment_list()

    /**
     * 게시물 첨부파일 다운로드
     */
    private function _article_file_download() {
        $article_seq = $this->input->get('article_seq');
        $article_seq = nvl($article_seq);
        $file_seq = $this->input->get('file_seq');
        $file_seq = nvl($file_seq);

        if( $article_seq != "" && $file_seq != "" ) {
            $file_info = $this->articleModel->selectAttachedFile( $article_seq, $file_seq );
            if( !is_null( $file_info ) ) {
                // 파일 다운로드 요청
                // $this->my_common_library->file_download($file_info);
                $target_file = DOCUMENT_ROOT . $file_info['PATH'] . $file_info['PHYSICAL_NAME'];
                if( file_exists( $target_file ) ){
                    force_download( $file_info['LOGICAL_NAME'], file_get_contents( $target_file ) );
                    exit();
                } else {
                    echo '파일이 존재하지 않습니다';
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
    }//     EOF     private function _article_file_download()

    /**
     * 게시물 컨텐츠 삽입용 파일업로드(이미지전용)
     */
    private function _article_contents_file_upload() {
        $result = array();
        $member_seq = $this->input->post('member_seq');

        // 신규 첨부파일 업로드
        $upload_info = array(
            'MEMBER_SEQ'=>$member_seq,
            'FILE' => 'file'
        );
        $file_info_list = $this->my_common_library->file_upload( $upload_info );

        if( count($file_info_list)) {
            $result['result'] = true;
            $result['data'] = $file_info_list;
        } else {
            $result['result'] = false;
            $result['error_code'] = AR_BAD_REQUEST[0];
            $result['message'] = AR_BAD_REQUEST[1];
        }
        echo json_encode( $result );
    }//     EOF     private function _article_contents_file_upload()

}//     EOC     class Article extends CI_Controller