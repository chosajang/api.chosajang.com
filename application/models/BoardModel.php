<?php
class BoardModel extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database(DB_CONFIG_NAME, TRUE);
    }
    
    /**
     * 게시판 목록
     */
    function selectBoard_list($limit_start, $limit_end, $search) {
        $limit_sql = "";
        $where_sql = "";
        $param = array();

        if ( $search !== "" ) {
            $where_sql = " AND NAME LIKE '%{$search}%'";
        }
        if( $limit_end !== 0 ) {
            $limit_sql = " LIMIT ?, ? ";
            array_push($param,$limit_start);
            array_push($param,$limit_end);
        }

        $sql = "
SELECT
    SEQ,
    NAME,
    USE_YN,
    COMMENT_YN,
    ATTACHED_FILE_YN,
    ATTACHED_DOCUMENT_YN,
    PROJECT_YN,
    AUTH_YN,
    NOTICE_COUNT,
    ADD_DATE,
    MOD_DATE
FROM TB_BOARD
WHERE USE_YN = 'Y'
AND PROJECT_YN = 'N' " . $where_sql . $limit_sql;

        $result = $this->db->query( $sql, $param );
        
        return $result->result_array();
    }//     EOF     function selectBoard_list($limit_start, $limit_end, $search)

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
    PROJECT_YN,
    AUTH_YN,
    NOTICE_COUNT,
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
    function selectBoardForMember( $board_seq, $member_seq ) {
        // 게시판 정보
        $sql = "
SELECT
    A.SEQ,
    A.NAME,
    A.USE_YN,
    A.COMMENT_YN,
    A.ATTACHED_FILE_YN,
    A.ATTACHED_DOCUMENT_YN,
    A.PROJECT_YN, 
    A.AUTH_YN,
    IF(	A.AUTH_YN = 'Y',
		IF( IFNULL(B.ADMIN_YN,'N') = 'Y', 
			'Y',
            IF( IFNULL(B.EDIT_YN,'N') = 'Y', 'Y', IFNULL(B.READ_YN,'Y') )
		),
        'Y'
	)AS READ_YN,
    IF( A.AUTH_YN = 'Y',
		IF( IFNULL(B.ADMIN_YN,'N') = 'Y', 'Y', IFNULL(B.EDIT_YN,'N') ),
        'Y'
	)AS EDIT_YN,
    IF(A.AUTH_YN='Y',IFNULL(B.ADMIN_YN,'N'),IFNULL(B.ADMIN_YN,'N'))AS ADMIN_YN,
    A.NOTICE_COUNT,
    IFNULL(COUNT(C.SEQ),0) AS NOTICE_ARTICLE_COUNT,
    IF( IFNULL(COUNT(C.SEQ),0) >= A.NOTICE_COUNT, 'N', 'Y')AS NOTICE_SET_YN
FROM TB_BOARD A
    LEFT JOIN TB_BOARD_MEMBER_AUTH_LINK B ON B.BOARD_SEQ = A.SEQ AND B.MEMBER_SEQ = ?
    LEFT JOIN TB_ARTICLE C ON C.BOARD_SEQ = A.SEQ AND C.USE_YN = 'Y' AND C.NOTICE_YN = 'Y'
WHERE A.SEQ = ? ";

        $result = $this->db->query( $sql, array( $member_seq, $board_seq ) );
        
        return $result->row_array();
    }//     EOF     function selectBoardForMember( $board_seq, $member_seq )

    /**
     * 게시판 생성
     * - 일반 게시판용 생성 함수
     */
    function insertBoard($name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn='N', $project_yn='N', $notice_count=3) {
        $sql = "
INSERT INTO TB_BOARD( NAME, COMMENT_YN, ATTACHED_FILE_YN, ATTACHED_DOCUMENT_YN, AUTH_YN, PROJECT_YN, NOTICE_COUNT )
VALUES( ?, ?, ?, ?, ?, ?, ? ) ";

        $this->db->query( $sql, array($name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn, $project_yn, $notice_count) );

        return $this->db->insert_id();
    }//     EOF     function insertBoard($name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn='N', $project_yn='N')

    /**
     * 게시판 수정
     */
    function updateBoard($board_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn='N', $notice_count=3) {
        $sql = "
UPDATE TB_BOARD
    SET
        NAME = ?,
        COMMENT_YN = ?,
        ATTACHED_FILE_YN = ?,
        ATTACHED_DOCUMENT_YN = ?,
        AUTH_YN = ?,
        NOTICE_COUNT = ?
WHERE SEQ = ? ";

        $query_result = $this->db->query( $sql, array($name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn, $notice_count, $board_seq) );

        return $query_result;
    }//     EOF     function updateBoard($board_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn, $auth_yn='N')

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

    /**
     * 프로젝트 게시판 생성
     * - 게시판 생성
     * - 프로젝트 게시판 연결
     */
    function insertProjectBoard( $project_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn ) {
        // 게시판 생성
        $project_yn = 'Y';
        $board_seq = $this->insertBoard( $name, $comment_yn, $attached_file_yn, $attached_document_yn, $project_yn );
        // 프로젝트 게시판 연결
        $sql = "
INSERT INTO TB_PROJECT_BOARD_LINK(PROJECT_SEQ, BOARD_SEQ)
VALUES( ?, ? ) ";

        $result = $this->db->query( $sql, array( $project_seq, $board_seq ) );

        return $result;
    }//     EOF     function insertProjectBoard( $project_seq, $name, $comment_yn, $attached_file_yn, $attached_document_yn )

}//     EOC     class BoardModel extends CI_Model