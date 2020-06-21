<?php
/**
 * filename : MemberModel.php
 * author : HyunHee, Cho (enjoysoft@cocoavision.co.kr)
 * filedate : 2019.06.12
 * comments :
 * history :
 */
class MemberModel extends CI_Model {
    function __construct() {
        parent::__construct();
        $this->db = $this->load->database(DB_CONFIG_NAME, TRUE);
    }

    /** 
     * 회원 비밀번호 조회
     * - ID 또는 SEQ로 회원 비밀번호를 조회
     */
    function selectMember_passwordInfo($member_info) {
        $set_sql = '';
        $param = array();
        if ( @!is_null($member_info['ID']) ) {
            $set_sql .= 'ID = ?';
            array_push($param,$member_info['ID']);
        } else if ( @!is_null($member_info['SEQ']) ) {
            $set_sql .= 'SEQ = ?';
            array_push($param,$member_info['SEQ']);
        }
        $sql = "
SELECT
    SEQ,
    PASSWORD
FROM TB_MEMBER
WHERE ". $set_sql;

        $result = $this->db->query( $sql, $param );
        $member_result = $result->row_array();
        
        return $member_result;
    }//     EOF     function selectMember_passwordInfo($member_info)

    /**
     * 회원 조회(selectMember)
     */
    function selectMember( $member_seq, $my_check=FALSE ) {
        $add_sql = "";
        if( $my_check === TRUE ){
            $add_sql = "A.SESSION_ID,";
        }

        $sql = "
SELECT
    A.SEQ,
    A.ID,
    A.NAME,
    A.TITLE,
    A.BIRTHDAY,
    A.COMMENT,
    IFNULL(DATE_FORMAT(A.ADD_DATE,'%Y-%m-%d'),'')AS ADD_DATE,
    IFNULL(DATE_FORMAT(A.MOD_DATE,'%Y-%m-%d'),'')AS MOD_DATE,
    ".$add_sql."
    A.PROFILE_FILE_SEQ,
    A.MEMBER_STATUS_SEQ,
    AB.NAME AS MEMBER_STATUS_NAME,
    A.MEMBER_GRADE_SEQ,
    AD.NAME AS MEMBER_GRADE_NAME
FROM TB_MEMBER A
    INNER JOIN TB_MEMBER_STATUS AB ON A.MEMBER_STATUS_SEQ = AB.SEQ /* 회원상태 */
    INNER JOIN TB_MEMBER_GRADE AD ON A.MEMBER_GRADE_SEQ = AD.SEQ /* 회원등급 */
WHERE A.SEQ = ? ";
     
        $param = array( $member_seq );
        $result = $this->db->query( $sql, $param );
        $member_info = $result->row_array();
        
        return $member_info;
    }//       EOF       function selectMemberAccountCheck( $member_seq )
     
    /** 
     * 회원세션 조회(selectMember_sessionCheck)
     */
    function selectMember_sessionCheck( $member_seq ) {
        $sql = "
SELECT
    SESSION_ID
FROM TB_MEMBER
WHERE SEQ = ? ";
          
        $param = array($member_seq);
        $result = $this->db->query( $sql, $param );
        $member_info = $result->row_array();
        
        return $member_info;
    }//       EOF       function selectMemberSessionCheck( $member_seq )

    /** 
     * 회원세션 초기화(updateMember_sessionClear)
     */
    function updateMember_sessionClear( $member_seq ) {
        $sql = "
UPDATE TB_MEMBER SET SESSION_ID = ''
WHERE SEQ = ? ";
        $result = $this->db->query( $sql, array($member_seq) );

        return $result;
    }//     EOF     function updateMember_sessionClear( $member_seq )


     
    /**
     * 회원 목록(selectMember_list)
     */
    function selectMember_list( $member_status_seq ) {
        $where_sql = "";
        if( $member_status_seq != "" ) {
            $where_sql = "WHERE A.MEMBER_STATUS_SEQ IN (" . join(',',$member_status_seq) . ") ";
        }

        $sql = "
SELECT 
    A.SEQ,
    A.ID,
    A.NAME,
    A.TITLE,
    A.COMMENT,
    IFNULL(DATE_FORMAT(A.BIRTHDAY,'%Y-%m-%d'),'')AS BIRTHDAY,
    IF ( ISNULL(A.PROFILE_FILE_SEQ),
            '',
            CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', B.PATH, B.PHYSICAL_NAME) ) AS PROFILE_IMG,
    IF ( ISNULL(A.PROFILE_FILE_SEQ),
            '',
            CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', B.PATH, IFNULL( JSON_UNQUOTE( JSON_EXTRACT( B.INFO, '$.THUMB_FILE_NAME' ) ), '') ) ) AS PROFILE_THUMB_IMG,
    IFNULL(JSON_VALUE(B.INFO,'$.THUMB_FILE_NAME'),'')AS THUMB_FILE_NAME,
    AB.SEQ AS MEMBER_STATUS_SEQ,
    AB.NAME AS MEMBER_STATUS_NAME,
    AC.SEQ AS MEMBER_GRADE_SEQ,
    AC.NAME AS MEMBER_GRADE_NAME
FROM
    TB_MEMBER A
        INNER JOIN TB_MEMBER_STATUS AB ON AB.SEQ = A.MEMBER_STATUS_SEQ AND AB.USE_YN = 'Y'
        INNER JOIN TB_MEMBER_GRADE AC ON AC.SEQ = A.MEMBER_GRADE_SEQ AND AC.USE_YN = 'Y'
        LEFT JOIN TB_FILE B ON A.PROFILE_FILE_SEQ = B.SEQ
    " . $where_sql . "
ORDER BY A.SEQ ASC ";

        $result = $this->db->query( $sql );
        $member_list = $result->result_array();
        
        return $member_list;
    }//     EOF     function selectMember_list( $member_status_seq )

    /*
     * 회원 상태 목록 조회(selectMemberStatusList)
     */
    function selectMemberStatusList() {
        $sql = "
SELECT
    SEQ,
    NAME
FROM TB_MEMBER_STATUS
WHERE USE_YN = 'Y'
ORDER BY SEQ ASC ";
        
        $result = $this->db->query( $sql );
        $member_status_list = $result->result_array();
        
        return $member_status_list;
    }//       EOF       function selectMemberStatusList()
    
    /**
     * 회원 등급 목록 조회(selectMemberGradeList)
     */
    function selectMemberGradeList() {
        $sql = "
SELECT
    SEQ,
    NAME
FROM TB_MEMBER_GRADE
WHERE USE_YN = 'Y'
ORDER BY SEQ ASC ";
        
        $result = $this->db->query( $sql );
        $member_grade_list = $result->result_array();
        
        return $member_grade_list;
    }//       EOF       function selectMemberGradeList()

    /**
     * 회원 등급목록 조회
     */
    function selectMemberGrade_list() { 
        $sql = "
SELECT 
    SEQ,
    NAME
FROM TB_MEMBER_GRADE
WHERE USE_YN = 'Y'
AND SET_YN = 'Y' ";

        $result = $this->db->query( $sql );
        $member_grade_list = $result->result_array();
        
        return $member_grade_list;
    }//     EOF function selectMemberGrade_list()
    
/*=================================================================================
*                                 SELECT QUERY END
=================================================================================*/

/*=================================================================================
*                                 INSERT QUERY START
=================================================================================*/
    
    function insertMember($member_info) {
        $column_sql = '';
        $value_sql = '';

        $param = array(
            $member_info['ID'],
            $member_info['PASSWORD'],
            $member_info['NAME'],
            $member_info['BIRTHDAY'],
            $member_info['COMMENT']
        );

        if ( @!is_null($member_info['PROFILE_FILE_SEQ']) && nvl($member_info['PROFILE_FILE_SEQ']) !== ''  ) {
            $column_sql .= ', PROFILE_FILE_SEQ';
            $value_sql .= ', ?';
            array_push($param,$member_info['PROFILE_FILE_SEQ']);
        }
        if ( @!is_null($member_info['MEMBER_STATUS_SEQ']) && nvl($member_info['MEMBER_STATUS_SEQ']) !== ''  ) {
            $column_sql .= ', MEMBER_STATUS_SEQ';
            $value_sql .= ', ?';
            array_push($param,$member_info['MEMBER_STATUS_SEQ']);
        }

        $sql = "
INSERT INTO TB_MEMBER(ID,PASSWORD,NAME,BIRTHDAY,COMMENT" . $column_sql . ")
VALUES( ?, ?, ?, ?, ?" . $value_sql . " ) ";

        $result = $this->db->query( $sql, $param );
        
        return $this->db->insert_id();
    }//       EOF       function insertMember($member_info)    
/*=================================================================================
*                                 INSERT QUERY END
=================================================================================*/
    

/*=================================================================================
*                                 UPDATE QUERY START
=================================================================================*/

    /** 
     * 회원로그인 
     */
    function updateMemberLogin($seq, $session_id) {
        $sql = "
UPDATE TB_MEMBER SET SESSION_ID = ?
WHERE SEQ = ? ";
        
        $param = array($session_id,$seq);
        $result = $this->db->query( $sql, $param );
        
        return $result;
    }//       EOF       function updateMemberLogin($seq, $session_id)

    /** 
     * 회원정보수정
     */
    function updateMember($member_info) {
        
        $set_sql = '';
        $param = array();
        $query_exec = false;
        $result = false;
        
        if ( @!is_null($member_info['ID']) ) {
            $temp_sql = 'ID = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['ID']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['NAME']) ) {
            $temp_sql = 'NAME = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['NAME']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['BIRTHDAY']) ) {
            $temp_sql = 'BIRTHDAY = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['BIRTHDAY']);
            $query_exec = true;
        } 
        if ( @!is_null($member_info['COMMENT']) ) {
            $temp_sql = 'COMMENT = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['COMMENT']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['TITLE']) ) {
            $temp_sql = 'TITLE = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['TITLE']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['PROFILE_FILE_SEQ']) ) {
            $temp_sql = 'PROFILE_FILE_SEQ = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['PROFILE_FILE_SEQ']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['MEMBER_STATUS_SEQ']) ) {
            $temp_sql = 'MEMBER_STATUS_SEQ = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['MEMBER_STATUS_SEQ']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['MEMBER_GRADE_SEQ']) ) {
            $temp_sql = 'MEMBER_GRADE_SEQ = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['MEMBER_GRADE_SEQ']);
            $query_exec = true;
        }
        if ( @!is_null($member_info['PASSWORD']) ) {
            // 비밀번호 변경 시, 세션정보 초기화
            $temp_sql = "PASSWORD = ?, SESSION_ID=''";
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['PASSWORD']);
            $query_exec = true;
        }
        
        if( $query_exec === TRUE ) {
            $sql = "
UPDATE TB_MEMBER
        SET 
            ". $set_sql ."
WHERE SEQ = ? ";

            array_push($param,$member_info['SEQ']);
            
            $result = $this->db->query( $sql, $param );
        }
        
        return $result;
    }//       EOF       function updateMember($member_info)

}//            EOC       class MemberModel extends CI_Model