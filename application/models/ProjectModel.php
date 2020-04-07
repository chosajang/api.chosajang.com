<?php
class ProjectModel extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database(DB_CONFIG_NAME, TRUE);
    }
    
    /**
     * 프로젝트 목록
     */
    function selectProject_list( $member_seq, $limit_start, $limit_end, $search ) {
        $limit_sql = "";
        $where_sql = "";
        $param = array();

        array_push($param,$member_seq);
        
        if ( $search !== "" ) {
            $where_sql = " AND A.NAME LIKE '%{$search}%'";
        }

        if( $limit_end !== 0 ) {
            $limit_sql = " LIMIT ?, ? ";
            array_push($param,$limit_start);
            array_push($param,$limit_end);
        }

        $sql = "
SELECT
    A.SEQ,
    A.SHEET_SEQ,
    A.NAME,
    A.INITIAL,
    IF( IFNULL(D.MEMBER_SEQ,'')='','N','Y') AS JOIN_YN,
    IFNULL(A.START_DATE,'') AS START_DATE,
    IFNULL(A.COMPLETION_DATE,'') AS COMPLETION_DATE,
    IFNULL(A.DELIBERATION_DATE,'') AS DELIBERATION_DATE,
    IFNULL(A.RELEASE_DATE,'') AS RELEASE_DATE,
    A.OTHER_INFO,
    A.ADD_DATE,
    A.MOD_DATE,
    IF( ISNULL(	A.POSTER_FILE_SEQ),
                '', 
                CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "',C.PATH, C.PHYSICAL_NAME) )AS POSTER_FILE,
    A.STATUS_SEQ,
    B.NAME AS STATUS_NAME,
    A.MEMBER_SEQ
FROM TB_PROJECT A
    INNER JOIN TB_PROJECT_STATUS B 
        ON B.SEQ = A.STATUS_SEQ 
        AND B.USE_YN = 'Y'
    LEFT JOIN TB_FILE C 
        ON C.SEQ = A.POSTER_FILE_SEQ 
        AND C.USE_YN = 'Y'
    LEFT JOIN TB_PROJECT_MEMBER_LINK D
		ON D.PROJECT_SEQ = A.SEQ
        AND D.USE_YN = 'Y'
        AND D.MEMBER_SEQ = ?
WHERE A.USE_YN = 'Y' " . $where_sql . " ORDER BY A.ADD_DATE DESC " . $limit_sql;

        $result = $this->db->query( $sql, $param );
        
        return $result->result_array();
    }//     EOF     function selectProject_list( $member_seq, $limit_start, $limit_end, $search )

    /**
     * 프로젝트 간단 목록
     * - order by : 진행중인 프로젝트 우선, 그 후 대기, 종료 순
     *  > 진행중인 프로젝트 구분을 위해 STATUS_SEQ로 SORT_NO 값을 정함
     */
    function selectProject_participatedList( $member_seq, $project_status_seq_list=array() ) {
        $where_sql = "";
        if( count($project_status_seq_list) > 0 ) {
            $where_sql = "AND C.SEQ IN(" . join(',',$project_status_seq_list) . ")";
        }
        
        $sql = "
SELECT
    G.SEQ,
    G.NAME,
    G.STATUS_NAME,
    G.ADD_DATE
FROM (
SELECT
    A.SEQ,
    A.NAME,
    B.MEMBER_SEQ,
    IF(A.STATUS_SEQ=2,1,2) AS SORT_NO,
    C.NAME AS STATUS_NAME,
    A.STATUS_SEQ,
    A.ADD_DATE
FROM TB_PROJECT A
INNER JOIN TB_PROJECT_MEMBER_LINK B ON B.PROJECT_SEQ = A.SEQ AND B.MEMBER_SEQ = ? AND B.USE_YN = 'Y'
INNER JOIN TB_PROJECT_STATUS C ON C.SEQ = A.STATUS_SEQ AND C.USE_YN = 'Y'
WHERE A.USE_YN = 'Y'
" . $where_sql . "
    )G
ORDER BY G.SORT_NO ASC, G.STATUS_SEQ ASC, G.ADD_DATE DESC ";

        $result = $this->db->query( $sql, array( $member_seq ) );
                
        return $result->result_array();
    }//     EOF     function selectProject_SimpleList()

    /**
     * 프로젝트 등록
     * 
     * 정상 등록 시, 프로젝트 번호(SEQ)를 리턴
     * 실패 시, false 리턴
     */
    function insertProject($member_seq, $project_name) {
        $sql = "
INSERT INTO TB_PROJECT(NAME, MEMBER_SEQ)
VALUES( ?, ? ) ";

        $this->db->query($sql, array($project_name, $member_seq) );
        $project_seq = $this->db->insert_id();

        $sql = "
INSERT INTO TB_PROJECT_MEMBER_LINK(PROJECT_SEQ, MEMBER_SEQ, PROJECT_MEMBER_GRADE_SEQ)
VALUES(?, ?, ?) ";

        $result = $this->db->query($sql, array($project_seq, $member_seq, PMG_MANAGER) );

        return $project_seq;
    }//     EOF     function insertProject($member_seq, $project_name)

    /**
     * 프로젝트 인증
     */
    function selectProjectAuthority( $member_seq, $session_id, $project_seq ) {
        $sql = "
SELECT
    A.SEQ,
    A.NAME,
    A.MEMBER_GRADE_SEQ,
    B.PROJECT_SEQ,
    BB.NAME AS PROJECT_NAME,
    B.PROJECT_MEMBER_GRADE_SEQ,
    BA.NAME AS PROJECT_MEMBER_GRADE_NAME,
    BB.STATUS_SEQ AS PROJECT_STATUS_SEQ,
    BBA.NAME AS PROJECT_STATUS_NAME
FROM TB_MEMBER A
    INNER JOIN TB_PROJECT_MEMBER_LINK B ON A.SEQ = B.MEMBER_SEQ AND B.USE_YN ='Y'
    INNER JOIN TB_PROJECT_MEMBER_GRADE BA ON B.PROJECT_MEMBER_GRADE_SEQ = BA.SEQ AND BA.USE_YN = 'Y'
    INNER JOIN TB_PROJECT BB ON B.PROJECT_SEQ = BB.SEQ AND BB.USE_YN ='Y'
    INNER JOIN TB_PROJECT_STATUS BBA ON BB.STATUS_SEQ = BBA.SEQ AND BBA.USE_YN = 'Y'
WHERE A.MEMBER_STATUS_SEQ = 3 /* 회원 상태(3:승인) */
AND A.SEQ = ?
AND A.SESSION_ID = ?
AND B.PROJECT_SEQ = ? ";

        $result = $this->db->query($sql, array( $member_seq, $session_id, $project_seq ) );

        return $result->row_array();
    }//     EOF     function selectProjectAuthority( $member_seq, $project )

    /**
     * 프로젝트 정보조회
     */
    function selectProject( $member_seq, $project_seq ) {
        $sql = "
SELECT
    A.SEQ,
	A.SHEET_SEQ,
    A.NAME,
    A.INITIAL,
    IFNULL(A.START_DATE,'') AS START_DATE,
    IFNULL(A.COMPLETION_DATE,'') AS COMPLETION_DATE,
    IFNULL(A.DELIBERATION_DATE,'') AS DELIBERATION_DATE,
    IFNULL(A.RELEASE_DATE,'') AS RELEASE_DATE,
    A.OTHER_INFO,
    IFNULL(A.POSTER_FILE_SEQ,'')AS POSTER_FILE_SEQ,
    IF( ISNULL(	A.POSTER_FILE_SEQ ),
                '', 
                CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', D.PATH, D.PHYSICAL_NAME) )AS POSTER_FILE,
    C.BOARD_SEQ
FROM TB_PROJECT A
INNER JOIN TB_PROJECT_MEMBER_LINK B
    ON A.SEQ = B.PROJECT_SEQ 
    AND B.USE_YN = 'Y'
    AND B.MEMBER_SEQ = ?
INNER JOIN TB_PROJECT_BOARD_LINK C
    ON C.PROJECT_SEQ = A.SEQ
    AND C.USE_YN = 'Y'
LEFT JOIN TB_FILE D
	ON D.SEQ = A.POSTER_FILE_SEQ 
    AND D.USE_YN = 'Y'
WHERE A.USE_YN = 'Y'
AND A.SEQ = ?  ";

        $result = $this->db->query($sql, array( $member_seq, $project_seq) );

        return $result->row_array();
    }//     EOF     function selectProject( $member_seq, $project_seq )

    /**
     * 프로젝트 수정
     */
    function updateProject( $project_info ) {
        $sql = "
UPDATE TB_PROJECT
    SET 
        NAME = ?, 
        INITIAL = ?, 
        START_DATE = ?, 
        COMPLETION_DATE = ?, 
        DELIBERATION_DATE = ?, 
        RELEASE_DATE = ?, 
        OTHER_INFO = ?
WHERE SEQ = ? ";

        $param = array();
        array_push( $param, $project_info['NAME'] );
        array_push( $param, $project_info['INITIAL'] );
        array_push( $param, $project_info['START_DATE'] );
        array_push( $param, $project_info['COMPLETION_DATE'] );
        array_push( $param, $project_info['DELIBERATION_DATE'] );
        array_push( $param, $project_info['RELEASE_DATE'] );
        array_push( $param, $project_info['OTHER_INFO'] );
        array_push( $param, $project_info['PROJECT_SEQ'] );
        
        $result = $this->db->query( $sql, $param );
        
        return $result;
    }//     EOF     function updateProject( $project_info )

    /**
     * 프로젝트 수정
     * - 키값으로 지정한 필드만 업데이트
     */
    function updateProject_key( $project_info ) {
        $set_sql = '';
        $param = array();
        $query_exec = false;
        $result = false;
        
        // 포스터 파일 시퀀스
        if ( @!is_null($project_info['POSTER_FILE_SEQ']) ) {
            $temp_sql = 'POSTER_FILE_SEQ = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push( $param, $project_info['POSTER_FILE_SEQ'] );
            $query_exec = true;
        }
        // 프로젝트 상태 시퀀스
        if ( @!is_null($project_info['STATUS_SEQ']) ) {
            $temp_sql = 'STATUS_SEQ = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push( $param, $project_info['STATUS_SEQ'] );
            $query_exec = true;
        }

        if( $query_exec === TRUE ) {
            $sql = "
UPDATE TB_PROJECT
        SET 
            ". $set_sql ."
WHERE SEQ = ? ";

            array_push( $param, $project_info['PROJECT_SEQ'] );
            
            $result = $this->db->query( $sql, $param );
        }
        
        return $result;
    }//     EOF     function updateProject_key( $project_info )

    /**
     * 프로젝트 상태 이력 등록
     */
    function insertProjectStatusHistory( $member_seq, $project_seq, $project_status_seq, $content ) {
        $sql = "
INSERT INTO TB_PROJECT_STATUS_HISTORY( MEMBER_SEQ, PROJECT_SEQ, PROJECT_STATUS_SEQ, CONTENT )
VALUES( ?, ?, ?, ? ) ";

        $this->db->query( $sql, array( $member_seq, $project_seq, $project_status_seq, $content ) );

        $project_status_history_seq = $this->db->insert_id();

        return $project_status_history_seq;
    }//     EOF     function insertProjectStatusHistory( $member_seq, $project_seq, $project_status_seq, $content )

    /**
     * 프로젝트 상태 이력 조회
     */
    function selectProjectStatusHistory( $project_seq, $project_status_history_seq ) {
        $sql = "
SELECT
    A.SEQ,
    B.NAME AS MEMBER_NAME,
    BA.NAME AS TITLE_NAME,
    SG_BB.NAME AS GROUP_NAME,
    CONCAT(SG_BB.NAME, ' ', B.NAME, ' ', BA.NAME) AS WRITER,
    A.ADD_DATE,
    C.NAME AS PROJECT_STATUS_NAME,
    A.CONTENT
FROM TB_PROJECT_STATUS_HISTORY A
    INNER JOIN TB_MEMBER B ON B.SEQ = A.MEMBER_SEQ 
        INNER JOIN TB_MEMBER_TITLE BA ON B.MEMBER_TITLE_SEQ = BA.SEQ
        LEFT JOIN (
            SELECT 
                BB.MEMBER_SEQ,
                BB.GROUP_SEQ,
                BBA.DEPTH_NO,
                BBA.NAME
            FROM TB_MEMBER_GROUP_LINK BB
            INNER JOIN TB_GROUP BBA
                ON BBA.SEQ = BB.GROUP_SEQ
                AND BBA.USE_YN = 'Y'
            WHERE BBA.DEPTH_NO = 1
            AND BB.USE_YN = 'Y'
        ) SG_BB ON SG_BB.MEMBER_SEQ = B.SEQ
    INNER JOIN TB_PROJECT_STATUS C ON C.SEQ = A.PROJECT_STATUS_SEQ
WHERE A.PROJECT_SEQ = ?
AND A.SEQ = ? ";
            
            $result = $this->db->query( $sql, array( $project_seq, $project_status_history_seq ) );
    
            return $result->row_array();
        }///        EOF     function selectProjectStatusHistory( $project_seq, $project_status_history_seq )

    /**
     * 프로젝트 상태 이력 목록 조회
     */
    function selectProjectStatusHistory_list( $project_seq ) {
$sql = "
SELECT
    A.SEQ,
    B.NAME AS MEMBER_NAME,
    BA.NAME AS TITLE_NAME,
    SG_BB.NAME AS GROUP_NAME,
    CONCAT(SG_BB.NAME, ' ', B.NAME, ' ', BA.NAME) AS WRITER,
    A.ADD_DATE,
    C.NAME AS PROJECT_STATUS_NAME,
    A.CONTENT
FROM TB_PROJECT_STATUS_HISTORY A
    INNER JOIN TB_MEMBER B ON B.SEQ = A.MEMBER_SEQ 
        INNER JOIN TB_MEMBER_TITLE BA ON B.MEMBER_TITLE_SEQ = BA.SEQ
        LEFT JOIN (
            SELECT 
				BB.MEMBER_SEQ,
				BB.GROUP_SEQ,
				BBA.DEPTH_NO,
				BBA.NAME
			FROM TB_MEMBER_GROUP_LINK BB
			INNER JOIN TB_GROUP BBA
				ON BBA.SEQ = BB.GROUP_SEQ
				AND BBA.USE_YN = 'Y'
			WHERE BBA.DEPTH_NO = 1
			AND BB.USE_YN = 'Y'
        ) SG_BB ON SG_BB.MEMBER_SEQ = B.SEQ
    INNER JOIN TB_PROJECT_STATUS C ON C.SEQ = A.PROJECT_STATUS_SEQ
WHERE A.PROJECT_SEQ = ?
ORDER BY A.ADD_DATE ASC ";
        $result = $this->db->query( $sql, array( $project_seq ) );

        return $result->result_array();
    }///        EOF     function selectProjectStatusHistory_list( $project_seq )

    /**
     * 프로젝트 상태 목록
     */
    function selectProjectStatusList() {
        $sql = "
SELECT
    SEQ,
    NAME
FROM TB_PROJECT_STATUS
WHERE USE_YN = 'Y' ";

        $result = $this->db->query( $sql );

        return $result->result_array();
    }//     EOF     function selectProjectStatusList() 

    /**
     * 프로젝트 회원 목록(참여여부 정보)
     */
    function selectProjectMemberList($project_seq, $limit_start, $limit_end, $search, $join_yn) {
        $limit_sql = "";
        $project_member_link_sql = "";
        $where_sql = "";
        $param = array();

        $result_info = array();

        array_push( $param, $project_seq );

        if ( $search !== "" ) {
            $where_sql .= $where_sql === "" ? "WHERE" : "AND";
            $where_sql .= " GA.NAME LIKE '%{$search}%' ";
        }
        if ( $join_yn !== "" ) {
            $where_sql .= $where_sql === "" ? "WHERE" : "AND";
            $where_sql .= " GA.JOIN_YN = ? ";
            array_push( $param, $join_yn );
        }

        // 회원 수
        $sql = "
SELECT 
    COUNT(GA.SEQ)AS COUNT
FROM ( 
    SELECT 
        A.SEQ,
        A.NAME,
        IFNULL( AE.USE_YN , 'N' )AS JOIN_YN
    FROM
        TB_MEMBER A
            INNER JOIN TB_MEMBER_STATUS AB ON AB.SEQ = A.MEMBER_STATUS_SEQ AND AB.USE_YN = 'Y' AND AB.SEQ = 3
            INNER JOIN TB_MEMBER_GRADE AC ON AC.SEQ = A.MEMBER_GRADE_SEQ AND AC.USE_YN = 'Y'
            INNER JOIN TB_MEMBER_TITLE AD ON AD.SEQ = A.MEMBER_TITLE_SEQ AND AD.USE_YN = 'Y'
            LEFT JOIN TB_FILE B ON A.PROFILE_FILE_SEQ = B.SEQ
            LEFT JOIN TB_PROJECT_MEMBER_LINK AE ON AE.MEMBER_SEQ = A.SEQ AND AE.PROJECT_SEQ = ? 
    ) GA " . $where_sql;

        $result = $this->db->query( $sql, $param );
        $count_result = $result->row_array();
        $result_info['MEMBER_COUNT'] = $count_result['COUNT'];

        // 회원 목록
        if ( $limit_end !== 0 ) {
            $limit_sql = " LIMIT ?, ? ";
            array_push($param,$limit_start);
            array_push($param,$limit_end);
        }

        $sql = "
SELECT
    *
FROM (
    SELECT 
        A.SEQ,
        A.ID,
        A.NAME,
        AD.NAME AS MEMBER_TITLE_NAME,
        IF ( ISNULL(A.PROFILE_FILE_SEQ),
                '',
                CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', B.PATH, IFNULL( JSON_UNQUOTE( JSON_EXTRACT( B.INFO, '$.THUMB_FILE_NAME' ) ), '') ) ) AS PROFILE_THUMB_IMG,
        IFNULL(
            (SELECT 
                GROUP_CONCAT(AEA.NAME SEPARATOR ' / ')
            FROM TB_MEMBER_GROUP_LINK AE
            INNER JOIN TB_GROUP AEA ON AEA.SEQ = AE.GROUP_SEQ AND AEA.USE_YN = 'Y' AND AEA.DEPTH_NO != 0
            WHERE AE.MEMBER_SEQ = A.SEQ
            AND AE.USE_YN = 'Y' )
        ,'')AS GROUP_INFO,
        IFNULL( AE.USE_YN , 'N' )AS JOIN_YN,
        IFNULL( AE.PROJECT_MEMBER_GRADE_SEQ, 3 )AS PROJECT_MEMBER_GRADE_SEQ
    FROM
        TB_MEMBER A
            INNER JOIN TB_MEMBER_STATUS AB ON AB.SEQ = A.MEMBER_STATUS_SEQ AND AB.USE_YN = 'Y' AND AB.SEQ = 3
            INNER JOIN TB_MEMBER_GRADE AC ON AC.SEQ = A.MEMBER_GRADE_SEQ AND AC.USE_YN = 'Y'
            INNER JOIN TB_MEMBER_TITLE AD ON AD.SEQ = A.MEMBER_TITLE_SEQ AND AD.USE_YN = 'Y'
            LEFT JOIN TB_FILE B ON A.PROFILE_FILE_SEQ = B.SEQ
            LEFT JOIN TB_PROJECT_MEMBER_LINK AE ON AE.MEMBER_SEQ = A.SEQ AND AE.PROJECT_SEQ = ? 
    ) GA " . $where_sql . " ORDER BY BINARY(GA.NAME) ASC " . $limit_sql;
        
        $result = $this->db->query( $sql, $param );
        $result_info['MEMBER_LIST'] = $result->result_array();
        
        return $result_info;
    }//     EOF     function selectProjectMemberList($project_seq, $limit_start, $limit_end, $search)

    /**
     * 프로젝트 참여회원&등급 설정
     */
    function updateProjectMember( $project_seq, $modify_info_list ) {
        $result = false;
        $this->db->trans_begin();

        $sql = "
INSERT INTO TB_PROJECT_MEMBER_LINK(PROJECT_SEQ, MEMBER_SEQ, USE_YN, PROJECT_MEMBER_GRADE_SEQ)
VALUES( ?, ?, ?, ? )
ON DUPLICATE KEY UPDATE USE_YN = ?, PROJECT_MEMBER_GRADE_SEQ = ? ";

        foreach( $modify_info_list as $idx=>$info ){
            if( array_key_exists('member_seq',$info) && array_key_exists('join_yn',$info) && array_key_exists('grade_seq',$info) ) {
                $param = array( $project_seq, $info['member_seq'], $info['join_yn'], $info['grade_seq'], $info['join_yn'], $info['grade_seq'] );
                $this->db->query( $sql, $param );
            }
        }

        if( $this->db->trans_status() === TRUE ) {
            $result = true;
            $this->db->trans_commit();
        }else {
            $this->db->trans_rollback();
        }

        return $result;
    }//     EOF     function updateProjectMember( $project_seq, $modify_info_list )

    /**
     * 컬럼 타입 목록
     */
    function selectColumnTypeList(){
        $sql = "
SELECT 
    SEQ,
    NAME,
    COMMENT,
    INFO
FROM TB_COLUMN_TYPE
WHERE USE_YN = 'Y' ";

        $result = $this->db->query($sql);

        return $result->result_array();
    }//     EOF     function selectColumnTypeList()

    /**
     * 프로젝트 컬럼 정보 입력
     */
    function updateSheet_ColumnInfo( $column_info, $sheet_seq, $project_seq ) {
        $sql = "
UPDATE TB_SHEET SET COLUMN_INFO = ?
WHERE SEQ = ?
AND PROJECT_SEQ = ? ";

        $result = $this->db->query( $sql, array($column_info, $sheet_seq, $project_seq) );

        return $result;
    }//     EOF     function updateSheet_ColumnInfo( $column_info, $project_seq, $sheet_seq )

    /**
     * 시트 생성
     */
    function insertSheet( $name, $project_seq, $column_info ) {
        $sql = "
INSERT INTO TB_SHEET(NAME, PROJECT_SEQ, COLUMN_INFO)
VALUES( ?, ?, ? ) ";

        $result = $this->db->query( $sql, array($name, $project_seq, $column_info) );

        return $result;
    }//     EOF     function insertSheet( $name, $project_seq )

    /**
     * 시트 목록
     */
    function selectSheetList( $project_seq ) {
        $sql = "
SELECT
    SEQ,
    NAME,
    SORT_NO,
    JSON_EXTRACT(COLUMN_INFO, '$.data_list')AS COLUMN_INFO,
    VSHEET_INFO,
    USE_YN,
    ADD_DATE,
    MOD_DATE,
    PROJECT_SEQ
FROM TB_SHEET
WHERE PROJECT_SEQ = ?
AND USE_YN = 'Y'
ORDER BY SORT_NO ASC ";

        $result = $this->db->query( $sql, array($project_seq) );

        return $result->result_array();
    }//     EOF     function selectSheetList( $project_seq )

    /**
     * 시트 정보 조회
     */
    function selectSheet( $sheet_seq, $project_seq ) {
        $sql = "
SELECT
    SEQ,
    NAME,
    SORT_NO,
    COLUMN_INFO,
    VSHEET_INFO,
    USE_YN,
    ADD_DATE,
    MOD_DATE,
    PROJECT_SEQ
FROM TB_SHEET
WHERE SEQ = ? 
AND PROJECT_SEQ = ?
AND USE_YN = 'Y'
ORDER BY SORT_NO ASC ";

        $result = $this->db->query( $sql, array($sheet_seq,$project_seq) );

        return $result->row_array();
    }//     EOF     function selectSheet( $project_seq, $sheet_seq )

    /**
     * 컷 목록 조회
     */
    function selectCutList( $sheet_seq ) {
        $sql = "
SELECT
    SEQ,
    SORT_NO,
    SUB_SORT_NO,
    DATA,
    ADD_DATE,
    MOD_DATE
FROM TB_CUT
WHERE SHEET_SEQ = ?
AND USE_YN = 'Y'
ORDER BY SORT_NO ASC, SUB_SORT_NO ASC";

        $result = $this->db->query( $sql, array( $sheet_seq ) );

        return $result->result_array();
    }//     EOF     function selectCutList( $sheet_seq )
    
    /**
     * 컷 목록 조회(정렬용 목록)
     */
    function selectCutSortGroupList( $sheet_seq, $sort_no, $sub_sort_no, $position ) {
        if( $position == "d" ) {
            $add_sql = "AND SUB_SORT_NO > ?";
        } else {
            $add_sql = "AND SUB_SORT_NO >= ?";
        }
        
        $sql = "
SELECT
    SEQ,
    SORT_NO,
    SUB_SORT_NO,
    DATA,
    ADD_DATE,
    MOD_DATE
FROM TB_CUT
WHERE SHEET_SEQ = ?
AND USE_YN = 'Y'
AND SORT_NO = ?
" . $add_sql . "
ORDER BY SORT_NO ASC, SUB_SORT_NO ASC ";

        $result = $this->db->query( $sql, array( $sheet_seq, $sort_no, $sub_sort_no ) );

        return $result->result_array();
    }//     EOF     function selectCutSortGroupList( $sheet_seq, $sort_no, $sub_sort_no, $position )

    /**
     * 컷 조회
     */
    function selectCut( $cut_seq, $sheet_seq ) {
        $sql = "
SELECT
    SEQ,
    SORT_NO,
    SUB_SORT_NO,
    DATA,
    ADD_DATE,
    MOD_DATE
FROM TB_CUT
WHERE SEQ = ?
AND SHEET_SEQ = ? ";

        $result = $this->db->query( $sql, array( $cut_seq, $sheet_seq ) );

        return $result->row_array();
    }//     EOF     function selectCut( $sheet_seq, $cut_seq )

    /**
     * 마지막 정렬 번호 조회f
     */
    function selectCut_lastSortNo( $sheet_seq ) {
        $sql = "
SELECT
    IFNULL(MAX(SORT_NO)+1,1) AS SORT_NO
FROM TB_CUT
WHERE SHEET_SEQ = ? ";

    $result = $this->db->query( $sql, array( $sheet_seq ) );

    return $result->row_array();
    }//     EOF     function selectCut_lastSortNo( $sheet_seq )

    /**
     * 컷 등록
     */
    function insertCut($sheet_seq, $data, $sort_no, $sub_sort_no) {
        $sql = "
INSERT INTO TB_CUT(SHEET_SEQ, DATA, SORT_NO, SUB_SORT_NO)
VALUES( ?, ?, ?, ? ) ";

        $result = $this->db->query( $sql, array($sheet_seq, $data, $sort_no, $sub_sort_no) );

        return $this->db->insert_id();
    }//     EOF     function insertCut($sheet_seq, $data)

    /**
     * 컷 수정
     * - 예제 쿼리
UPDATE TB_CUT A INNER JOIN TB_SHEET B
	ON A.SHEET_SEQ = B.SEQ AND B.PROJECT_SEQ = ?
		SET 
			DATA = ( 
				CASE A.SEQ 
					WHEN 68 THEN JSON_SET(A.DATA ,'$.cgColKey0001', JSON_OBJECT('data','test01-1','option','none'),'$.cgColKey0002','test002') 
					WHEN 69 THEN JSON_SET(A.DATA ,'$.cgColKey0001','TEXT','$.cgColKey0002','test002') 
				END ) 
			WHERE A.SHEET_SEQ = ?
			AND A.SEQ IN (68,69);
     */
    function updateCut($project_seq, $sheet_seq, $cut_data ) {
        $seq_list = array();
        
        $sql = "UPDATE TB_CUT A INNER JOIN TB_SHEET B ";
        $sql .= "ON A.SHEET_SEQ = B.SEQ AND B.PROJECT_SEQ = ? ";
        $sql .= "SET A.DATA = ";
        $sql .= "( CASE A.SEQ ";
        foreach( $cut_data as $cut_info ) {
            $sql .= "WHEN " . $cut_info['cut_seq'] . " THEN JSON_SET(A.DATA ";
            foreach( $cut_info['data'] as $column_info ) {
                foreach( $column_info as $column_key=>$column_data ) {
                    $sql .= ",'$." . $column_key . "'";
                    // 컬럼 값이 배열인 경우 쿼리 수정(JSON_OBJECT)
                    if( is_array( $column_data ) ) {
                        $json_object_list = array();
                        foreach( $column_data as $data_key=>$data_value ) {
                            array_push( $json_object_list, "'".$data_key."'" );
                            array_push( $json_object_list, "'".$data_value."'" );
                        }
                        $sql .= ", JSON_OBJECT(" . join(',',$json_object_list) .")";
                    } else {
                        // Plain Text
                        $sql .= ",'" . $column_data . "'";
                    }
                }
            }
            $sql .= ") ";
            array_push($seq_list, $cut_info['cut_seq']);
        }
        $sql .= "END ) ";
        $sql .= "WHERE A.SHEET_SEQ = ? ";
        $sql .= "AND A.SEQ IN (" . join(',',$seq_list) . ")";
        
        $result = $this->db->query( $sql, array($project_seq, $sheet_seq) );

        return $result;
    }//     EOF     function updateCut($project_seq, $sheet_seq, $cut_data )

    /**
     * 컷 삭제
     */
    function deleteCut( $project_seq, $sheet_seq, $cut_seq_list ) {
        $sql = "
UPDATE TB_CUT A INNER JOIN TB_SHEET B
ON A.SHEET_SEQ = B.SEQ AND B.PROJECT_SEQ = ?
    SET A.USE_YN = 'N'
    WHERE A.SHEET_SEQ = ?
    AND A.SEQ IN (" . join(',',$cut_seq_list) . ") ";
        
        $this->db->query( $sql, array($project_seq, $sheet_seq) );

        return $this->db->affected_rows();
    }//     EOF     function deleteCut( $project_seq, $sheet_seq, $cut_seq_list )

    /**
     * 컷 정렬 번호 일괄수정
     */
    function updateCutSortInfo( $sheet_seq, $sort_no, $sub_sort_no, $count, $position ) {
        if( $position == "d" ) { // d
            $add_sql = "AND SUB_SORT_NO > ?";
        } else { // u
            $add_sql = "AND SUB_SORT_NO >= ?";
        }

        $sql = "
UPDATE TB_CUT 
    SET SUB_SORT_NO=SUB_SORT_NO+?
WHERE SHEET_SEQ = ?
AND USE_YN = 'Y'
AND SORT_NO = ?
" . $add_sql . "
ORDER BY SORT_NO ASC, SUB_SORT_NO ASC ";

        $result = $this->db->query( $sql, array($count, $sheet_seq, $sort_no, $sub_sort_no) );

        return $result;
    }//     EOF     function updateCutSortInfo( $sheet_seq, $sort_no, $sub_sort_no, $count, $position )

    /**
     * 컷 상태 통계 목록
     */
    function selectStatistics_cutStatusList( $sheet_seq ) {
        $sql = "
SELECT
    A.SEQ,
    A.NAME,
    A.COMMENT,
    IFNULL(G2.CUT_STATUS_COUNT,0)AS CUT_STATUS_COUNT
FROM TB_CUT_STATUS A
LEFT JOIN ( 
    SELECT
        G1.CUT_STATUS_SEQ,
        COUNT( G1.CUT_STATUS_SEQ ) AS CUT_STATUS_COUNT
    FROM ( 
        SELECT
            SEQ,
            IF(JSON_EXTRACT(DATA,'$.cgColKey0001')=\"\",1,CAST(JSON_EXTRACT(DATA,'$.cgColKey0001')AS UNSIGNED))AS CUT_STATUS_SEQ
        FROM TB_CUT
        WHERE SHEET_SEQ = ?
        AND USE_YN = 'Y' )G1
    GROUP BY G1.CUT_STATUS_SEQ )G2 
    ON A.SEQ = G2.CUT_STATUS_SEQ 
    WHERE A.USE_YN = 'Y' ";

        $result = $this->db->query( $sql, array( $sheet_seq ) );

        return $result->result_array();
    }//     EOF     function selectStatistics_cutStatusList( $sheet_seq )

    
    

}//     EOC     class ProjectModel extends CI_Model