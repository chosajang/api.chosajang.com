<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 */
class Posts extends CI_Controller {

    public $data = array();

    public function __construct() {
        parent::__construct();
        $this->method_prefix = '_posts_';
        
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
        if ( $function == 'index' || $function == '' ) { $function = 'list'; }
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
            // 요청 컨트롤러 호출
            $this->{$this->method_prefix.$function}();
            exit;
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
    private function _posts_list() {
        // 게시물 목록 조회
        $post_list = $this->articleModel->selectArticle_list();

        $result['result'] = true;
        $result['data'] = $post_list;

        echo json_encode($result);
    }//     EOF     private function _posts_list()
    
    /**
     * 게시물 조회
     */
    private function _posts_read() {
        $article_seq = $this->input->get('article_seq');
        $member_seq = $this->input->get('member_seq');

        $result = array();

        if ( is_numeric( $article_seq ) ) {    
            // 게시물 조회
            $article_info = $this->articleModel->selectArticle($article_seq);
            
            if ( !is_null($article_info) ) {
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
    }//     EOF     private function _posts_read()
    
    /**
     * 댓글 작성
     */
    private function _posts_comment_write() {
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
    }//     EOF     private function _posts_comment_write()

    /**
     * 댓글 수정
     */
    private function _posts_comment_modify() {
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
    }//     EOF     private function _posts_comment_modify()

    /**
     * 댓글 삭제
     */
    private function _posts_comment_delete() {
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
    }//     EOF     private function _posts_comment_delete()

    /**
     * 댓글 목록
     */
    private function _posts_comment_list() {
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
    }//     EOF     private function _posts_comment_list()

    /**
     * 게시물 첨부파일 다운로드
     */
    private function _posts_file_download() {
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
    }//     EOF     private function _posts_file_download()
}//     EOC     class Article extends CI_Controller