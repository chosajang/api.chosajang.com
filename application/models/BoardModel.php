<?php
class BoardModel extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database(DB_CONFIG_NAME, TRUE);
    }
    
    /**
     * 게시판 목록
     */
    function selectBoard_list() {
        $sql = "
SELECT
    SEQ,
    NAME,
    USE_YN,
    COMMENT_YN,
    ATTACHED_FILE_YN,
    ATTACHED_DOCUMENT_YN,
    ADD_DATE,
    MOD_DATE
FROM TB_BOARD
WHERE USE_YN = 'Y'
ORDER BY ADD_DATE DESC ";

        $result = $this->db->query( $sql );
        
        return $result->result_array();
    }//     EOF     function selectBoard_list()

    /**
     * 게시판 읽기
     */
    function selectBoard($board_seq) {
        $sql = "
SELECT
    SEQ,
    NAME,
    USE_YN,
    COMMENT_YN,
    ATTACHED_FILE_YN,
    ATTACHED_DOCUMENT_YN,
    ADD_DATE,
    MOD_DATE
FROM TB_BOARD
WHERE SEQ = ? ";

        $result = $this->db->query($sql, array($board_seq) );

        return $result->row_array();
    }//     EOF     function selectBoard($board_seq)

    /**
     * 게시판 정보
     */
    function selectBoardForMember( $board_seq ) {
        // 게시판 정보
        $sql = "
SELECT
    A.SEQ,
    A.NAME,
    A.USE_YN,
    A.COMMENT_YN,
    A.ATTACHED_FILE_YN,
    A.ATTACHED_DOCUMENT_YN
FROM TB_BOARD A
WHERE A.SEQ = ? ";

        $result = $this->db->query( $sql, array( $board_seq ) );
        
        return $result->row_array();
    }//     EOF     function selectBoardForMember( $board_seq )

    /**
     * 게시판 생성
     * - 일반 게시판용 생성 함수
     */
    function insertBoard($name, $comment_yn, $attached_file_yn, $attached_document_yn ) {
        $sql = "
INSERT INTO TB_BOARD( NAME, COMMENT_YN, ATTACHED_FILE_YN, ATTACHED_DOCUMENT_YN )
VALUES( ?, ?, ?, ? ) ";

        $this->db->query( $sql, array($name, $comment_yn, $attached_file_yn, $attached_document_yn ) );

        return $this->db->insert_id();
    }//     EOF     function insertBoard($name, $comment_yn, $attached_file_yn, $attached_document_yn )

    /**
     * 게시판 수정
     */
    function updateBoard($board_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn) {
        $sql = "
UPDATE TB_BOARD
    SET
        NAME = ?,
        COMMENT_YN = ?,
        ATTACHED_FILE_YN = ?,
        ATTACHED_DOCUMENT_YN = ?
WHERE SEQ = ? ";

        $query_result = $this->db->query( $sql, array($name, $comment_yn, $attached_file_yn, $attached_document_yn, $board_seq) );

        return $query_result;
    }//     EOF     function updateBoard($board_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn)

    /**
     * 게시판 삭제
     */
    function deleteBoard($board_seq) {
        $sql = "
UPDATE TB_BOARD
    SET 
        USE_YN = 'N'
WHERE SEQ = ? ";

        $query_result = $this->db->query( $sql, array($board_seq) );

        return $query_result;
    }//     EOF     function deleteBoard($board_seq)

}//     EOC     class BoardModel extends CI_Model