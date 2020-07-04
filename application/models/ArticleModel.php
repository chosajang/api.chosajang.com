<?php
class ArticleModel extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database(DB_CONFIG_NAME, TRUE);
    }

    /**
     * 게시판 정보
     */
    function selectBoard( $board_seq ) {
        $sql = "
SELECT
    A.SEQ,
    A.NAME,
    A.USE_YN,
    A.COMMENT_YN,
    A.ATTACHED_FILE_YN,
    A.ATTACHED_DOCUMENT_YN,
    A.PROJECT_YN
FROM TB_BOARD A
WHERE A.SEQ = ? ";

        $result = $this->db->query( $sql, array( $board_seq ) );

        return $result->row_array();
    }//     EOF     function selectBoard( $board_seq )

    /**
     * 게시물 읽기
     */
    function selectArticle($article_seq) {

        $sql = "
SELECT
    A.SEQ,
    A.BOARD_SEQ,
    A.TITLE,
    A.CONTENT,
    A.ADD_DATE,
    A.MOD_DATE,
    B.SEQ AS MEMBER_SEQ,
    B.NAME AS MEMBER_NAME
FROM TB_ARTICLE A
    INNER JOIN TB_MEMBER B ON B.SEQ = A.MEMBER_SEQ
WHERE A.USE_YN = 'Y'
AND A.SEQ = ? ";

        $result = $this->db->query( $sql, array($article_seq) );

        return $result->row_array();
    }//     EOF     function selectArticle($article_seq)

    /**
     * 게시물 목록(BOARD LIST)
     */
    function selectArticle_list() {
        $result_info = array();

        $sql = "
SELECT
    A.SEQ AS ARTICLE_SEQ,
    B.SEQ AS BOARD_SEQ,
    A.TITLE,
    C.NAME,
    A.ADD_DATE,
    A.MOD_DATE
FROM TB_ARTICLE A
INNER JOIN TB_BOARD B ON B.SEQ = A.BOARD_SEQ
INNER JOIN TB_MEMBER C ON C.SEQ = A.MEMBER_SEQ
AND A.USE_YN = 'Y' ";

        $result = $this->db->query( $sql );
        $result_info['ARTICLE_LIST'] = $result->result_array();
        
        return $result_info;
    }//     EOF     function selectArticle_list ()

    /**
     * 게시물 첨부파일 조회
     */
    function selectAttachedFile_list($article_seq) {
        $sql = "
SELECT
    A.ARTICLE_SEQ,
    B.SEQ AS FILE_SEQ,
    B.LOGICAL_NAME,
    B.PHYSICAL_NAME,
    IFNULL( JSON_UNQUOTE( JSON_EXTRACT( B.INFO, '$.THUMB_FILE_NAME' ) ), '') AS THUMB_FILE_NAME,
    CONCAT('" . urlencode(DATA_DIR) . "',B.PATH)AS PATH,
    B.SIZE,
    B.MIMETYPE,
    B.INFO,
    B.ADD_DATE
FROM TB_ARTICLE_FILE_LINK A
INNER JOIN TB_FILE B ON B.SEQ = A.FILE_SEQ
WHERE B.USE_YN = 'Y'
AND A.ARTICLE_SEQ = ? ";

        $result = $this->db->query( $sql, array($article_seq) );
                
        return $result->result_array();
    }//     EOF     function selectAttachedFile_list()

    /**
     * 게시물 쓰기
     */
    function insertArticle($board_seq, $member_seq, $title, $content) {
        $param = array();
        array_push($param,$board_seq);
        array_push($param,$member_seq);
        array_push($param,$title);
        array_push($param,$content);

        $sql = "
INSERT INTO TB_ARTICLE( BOARD_SEQ, MEMBER_SEQ, TITLE, CONTENT )
VALUES( ?, ?, ?, ? ) ";

        $this->db->query( $sql, $param );

        return $this->db->insert_id();
    }//     EOF     function insertArticle($board_seq, $member_seq, $title, $content)

    /**
     * 게시물 첨부파일 입력
     */
    function insertArticleFile($board_content_seq, $file_seq) {
        $sql = "
INSERT INTO TB_ARTICLE_FILE_LINK(ARTICLE_SEQ, FILE_SEQ)
VALUES( ?, ? ) ";

        $query_result = $this->db->query( $sql, array($board_content_seq, $file_seq) );

        return $query_result;
    }//     EOF     function insertArticleFile($board_content_seq, $file_seq)

    /**
     * 게시물 수정
     */
    function updateArticle($article_seq, $board_seq, $title, $content) {
        $sql = "
UPDATE TB_ARTICLE 
    SET TITLE=?, CONTENT=?
WHERE SEQ = ?
AND BOARD_SEQ = ? ";

        $query_result = $this->db->query( $sql, array($title, $content, $article_seq, $board_seq) );
        
        return $query_result;
    }//     EOF     function updateArticle($article_seq, $board_seq, $member_seq, $title, $content)

    /**
     * 게시물 첨부파일 삭제
     */
    function deleteArticleFile($file_seq_list) {
        $this->db->trans_start();

        $sql = "
UPDATE TB_ARTICLE_FILE_LINK A 
INNER JOIN TB_FILE B ON B.SEQ = A.FILE_SEQ
    SET A.USE_YN='N', B.USE_YN='N'
WHERE A.FILE_SEQ = ? ";

        foreach( $file_seq_list as $file_seq ) {
            $this->db->query( $sql, $file_seq );
        }
        
        return $this->db->trans_complete();
    }//     EOF     function deleteArticleFile($file_seq_list)

    /**
     * 게시물 삭제
     */
    function deleteArticle($article_seq) {
        $this->db->trans_start();
        // 게시물 삭제
        $sql = "
UPDATE TB_ARTICLE 
    SET USE_YN = 'N'
WHERE SEQ = ? ";

        $this->db->query( $sql, array($article_seq) );

        // 게시물 첨부파일 삭제
        $sql = "
UPDATE TB_ARTICLE_FILE_LINK
    SET USE_YN = 'N'
WHERE ARTICLE_SEQ = ? ";
        
        $this->db->query( $sql, array($article_seq) );

        // 게시물 첨부문서 삭제
        $sql = "
UPDATE TB_ARTICLE_DOCUMENT_LINK
    SET USE_YN = 'N'
WHERE ARTICLE_SEQ = ? ";
        
        $this->db->query( $sql, array($article_seq) );
        
        return $this->db->trans_complete();
    }//     EOF     function deleteArticle($article_seq, $member_seq)

    /**
     * 게시물 첨부문서 목록
     */
    function selectAttachedDocument_list($article_seq) {
        $sql = "
SELECT
    A.ARTICLE_SEQ,
    A.DOCUMENT_SEQ,
    B.TITLE,
    B.DATA,
    B.ADD_DATE,
    B.MOD_DATE,
    C.NAME,
    C.PATH
FROM TB_ARTICLE_DOCUMENT_LINK A
INNER JOIN TB_DOCUMENT B ON B.SEQ = A.DOCUMENT_SEQ
INNER JOIN TB_DOCUMENT_TEMPLATE C ON C.SEQ = B.DOCUMENT_TEMPLATE_SEQ
WHERE A.ARTICLE_SEQ = ?
AND A.USE_YN = 'Y'
AND B.USE_YN = 'Y' ";

        $result = $this->db->query( $sql, array($article_seq) );
                
        return $result->result_array();
    }//     EOF     function selectAttachedDocument_list($article_seq)

    /**
     * 댓글 목록
     */
    function selectArticleComment_list($article_seq) {
        $result_info = array();
        // 댓글 전체 개수
        $sql = "
SELECT
    COUNT(A.SEQ) AS COUNT
FROM TB_COMMENT A
INNER JOIN TB_ARTICLE_COMMENT_LINK B ON B.COMMENT_SEQ = A.SEQ AND B.USE_YN = 'Y'
WHERE A.USE_YN = 'Y'
AND B.ARTICLE_SEQ = ? ";

        $result = $this->db->query( $sql, array($article_seq) );
        $count_result = $result->row_array();
        $result_info['COMMENT_COUNT'] = $count_result['COUNT'];

        // 댓글 목록
        $sql = "
SELECT
    A.SEQ AS COMMENT_SEQ,
    A.CONTENT,
    A.ADD_DATE,
    C.SEQ AS MEMBER_SEQ,
    C.NAME AS MEMBER_NAME,
    IFNULL(CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "',CB.PATH,CB.PHYSICAL_NAME),'') AS PROFILE_IMG
FROM TB_COMMENT A
    INNER JOIN TB_ARTICLE_COMMENT_LINK B ON B.COMMENT_SEQ = A.SEQ AND B.ARTICLE_SEQ = ? AND B.USE_YN = 'Y'
    INNER JOIN TB_MEMBER C ON C.SEQ = A.MEMBER_SEQ 
    LEFT JOIN TB_FILE CB ON CB.SEQ = C.PROFILE_FILE_SEQ AND CB.USE_YN = 'Y'
WHERE A.USE_YN = 'Y'
ORDER BY B.SORT_NO ASC ";

        $result = $this->db->query( $sql, array($article_seq) );
        $result_info['COMMENT_LIST'] = $result->result_array();

        return $result_info;
    }//     EOF     function selectArticleComment_list($article_seq)

    /**
     * 댓글 조회
     */
    function selectComment( $comment_seq ) {
        $sql = "
SELECT
    A.SEQ AS COMMENT_SEQ,
    A.CONTENT,
    C.SEQ AS MEMBER_SEQ,
    C.NAME AS MEMBER_NAME,
    A.ADD_DATE,
    IFNULL(CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "',CB.PATH,CB.PHYSICAL_NAME),'') AS PROFILE_IMG
FROM TB_COMMENT A
    INNER JOIN TB_MEMBER C ON C.SEQ = A.MEMBER_SEQ 
    LEFT JOIN TB_FILE CB ON CB.SEQ = C.PROFILE_FILE_SEQ AND CB.USE_YN = 'Y'
WHERE A.USE_YN = 'Y'
AND A.SEQ = ? ";

        $result = $this->db->query( $sql, array($comment_seq) );

        return $result->row_array();
    }//     EOF     function selectComment( $comment_seq )

    /**
     * 댓글 등록
     * - 게시물 여부 확인
     * - 댓글 입력
     * - 게시물 댓글 연결 입력
     */
    function insertArticleComment($article_seq, $member_seq, $content) {

        // 게시물 여부 확인
        $sql = "
SELECT
    SEQ
FROM TB_ARTICLE
WHERE SEQ = ? 
AND USE_YN = 'Y' ";

        $result = $this->db->query( $sql, array($article_seq) );
        $article_info = $result->row_array();
        
        if( !is_null($article_info) ) {
            $this->db->trans_start();

            // 댓글 입력
            $sql = "
INSERT INTO TB_COMMENT(MEMBER_SEQ, CONTENT)
VALUES( ?, ? ) ";
        
            $this->db->query( $sql, array($member_seq, $content) );
    
            $comment_seq = $this->db->insert_id();
    
            // 게시물 댓글 마지막 정렬번호 조회
            $sql = "
SELECT
    IFNULL(MAX(SORT_NO) + 1,1) AS NEXT_SORT_NO
FROM TB_ARTICLE_COMMENT_LINK
WHERE ARTICLE_SEQ = ? ";
        
            $result = $this->db->query( $sql, array($article_seq) );
            $temp = $result->row_array();
    
            // 게시물 댓글 연결 입력
            $sql = "
INSERT INTO TB_ARTICLE_COMMENT_LINK(ARTICLE_SEQ, COMMENT_SEQ, SORT_NO)
VALUES( ?, ?, ?) ";
        
            $this->db->query( $sql, array($article_seq, $comment_seq, $temp['NEXT_SORT_NO']) );
    
            if( $this->db->trans_complete() ) {
                $result = $comment_seq;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }//     EOF     function insertArticleComment($article_seq, $member_seq, $content)

    /**
     * 댓글 수정
     */
    function updateArticleComment($comment_seq, $member_seq, $content) {
        $sql = "
UPDATE TB_COMMENT 
    SET CONTENT = ?
WHERE SEQ = ?
AND MEMBER_SEQ = ? ";

        $query_result = $this->db->query( $sql, array($content, $comment_seq, $member_seq) );

        return $query_result;
    }//     EOF     function updateArticleComment($comment_seq, $member_seq, $content)

    /**
     * 댓글 삭제
     */
    function deleteArticleComment($comment_seq, $member_seq) {
        $sql = "
UPDATE TB_ARTICLE_COMMENT_LINK A 
INNER JOIN TB_COMMENT B ON B.SEQ = A.COMMENT_SEQ
    SET A.USE_YN='N', B.USE_YN='N'
WHERE A.COMMENT_SEQ = ?
AND B.MEMBER_SEQ = ? ";

        $query_result = $this->db->query( $sql, array($comment_seq, $member_seq) );

        return $query_result;
    }//     EOF     function deleteArticleComment($comment_seq, $member_seq)

    /**
     * 게시물 첨부파일 조회
     */
    function selectAttachedFile( $article_seq, $file_seq ) {
        $sql = "
SELECT
    A.SEQ,
    A.LOGICAL_NAME,
    CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', PATH)AS PATH,
    A.PHYSICAL_NAME,
    A.SIZE,
    A.MIMETYPE,
    A.INFO,
    A.ADD_DATE,
    A.MEMBER_SEQ
FROM TB_FILE A
    INNER JOIN TB_ARTICLE_FILE_LINK B 
        ON B.FILE_SEQ = A.SEQ 
        AND B.USE_YN = 'Y' 
        AND B.ARTICLE_SEQ = ?
AND A.SEQ = ? ";

        $result = $this->db->query( $sql, array( $article_seq, $file_seq ) );
        $file_info = $result->row_array();

        return $file_info;
    }//     EOF     function selectAttachedFile( $article_seq, $file_seq )

}//     EOC     class ArticleModel extends CI_Model