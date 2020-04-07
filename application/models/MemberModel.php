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
     * 그룹 목록 조회(selectGroup_list)
     */
    function selectGroup_list($info) {
        $set_sql = '';
        $param = array();
        
        if ( @!is_null($info['P_SEQ']) ) {
            $set_sql .= 'AND P_SEQ = ?';
            array_push($param,$info['P_SEQ']);
        }
        if ( @!is_null($info['DEPTH_NO']) ) {
            $set_sql .= 'AND DEPTH_NO = ?';
            array_push($param,$info['DEPTH_NO']);
        }

        $sql = "
SELECT
    SEQ,
    NAME, 
    DEPTH_NO,
    SORT_NO, 
    ADD_DATE,
    P_SEQ
FROM TB_GROUP
WHERE USE_YN = 'Y'
". $set_sql ."
ORDER BY DEPTH_NO ASC, SORT_NO ASC ";

        $result = $this->db->query( $sql, $param );
        $group_list = $result->result_array();
        
        return $group_list;
    }//     EOF     function selectGroup_list()
    
     
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
    A.TEL,
    A.BIRTHDAY,
    A.COMMENT,
    IFNULL(DATE_FORMAT(A.ADD_DATE,'%Y-%m-%d'),'')AS ADD_DATE,
    IFNULL(DATE_FORMAT(A.MOD_DATE,'%Y-%m-%d'),'')AS MOD_DATE,
    IFNULL(DATE_FORMAT(A.ENTRY_DATE,'%Y-%m-%d'),'')AS ENTRY_DATE,
    ".$add_sql."
    A.PROFILE_FILE_SEQ,
    
    A.MEMBER_STATUS_SEQ,
    AB.NAME AS MEMBER_STATUS_NAME,
    A.MEMBER_TITLE_SEQ,
    AC.NAME AS MEMBER_TITLE_NAME,
    A.MEMBER_GRADE_SEQ,
    AD.NAME AS MEMBER_GRADE_NAME,
    GROUP_CONCAT(AE.GROUP_SEQ)AS MEMBER_GROUP_SEQ,
    GROUP_CONCAT(AEA.NAME)AS MEMBER_GROUP_NAME
                    
FROM TB_MEMBER A
    INNER JOIN TB_MEMBER_STATUS AB ON A.MEMBER_STATUS_SEQ = AB.SEQ /* 회원상태 */
    INNER JOIN TB_MEMBER_TITLE AC ON A.MEMBER_TITLE_SEQ = AC.SEQ /* 회원직함 */
    INNER JOIN TB_MEMBER_GRADE AD ON A.MEMBER_GRADE_SEQ = AD.SEQ /* 회원등급 */
    LEFT JOIN TB_MEMBER_GROUP_LINK AE ON AE.MEMBER_SEQ = A.SEQ AND AE.USE_YN = 'Y'
        LEFT JOIN TB_GROUP AEA ON AEA.SEQ = AE.GROUP_SEQ AND AEA.USE_YN = 'Y'/* 회원그룹정보 */
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
    A.COMMENT,
    IFNULL(DATE_FORMAT(A.ENTRY_DATE,'%Y-%m-%d'),'')AS ENTRY_DATE,
    IF ( ISNULL(A.PROFILE_FILE_SEQ),
            '',
            CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', B.PATH, B.PHYSICAL_NAME) ) AS PROFILE_IMG,
    IF ( ISNULL(A.PROFILE_FILE_SEQ),
            '',
            CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', B.PATH, IFNULL( JSON_UNQUOTE( JSON_EXTRACT( B.INFO, '$.THUMB_FILE_NAME' ) ), '') ) ) AS PROFILE_THUMB_IMG,
    IFNULL( B.INFO->'$.THUMB_FILE_NAME', '' )AS THUMB_FILE_NAME,
    AB.SEQ AS MEMBER_STATUS_SEQ,
    AB.NAME AS MEMBER_STATUS_NAME,
    AC.SEQ AS MEMBER_GRADE_SEQ,
    AC.NAME AS MEMBER_GRADE_NAME,
    AD.SEQ AS MEMBER_TITLE_SEQ,
    AD.NAME AS MEMBER_TITLE_NAME,
    IFNULL(
        (SELECT 
            GROUP_CONCAT(AEA.NAME SEPARATOR ',')
        FROM TB_MEMBER_GROUP_LINK AE
        INNER JOIN TB_GROUP AEA ON AEA.SEQ = AE.GROUP_SEQ AND AEA.USE_YN = 'Y'
        WHERE AE.MEMBER_SEQ = A.SEQ
        AND AE.USE_YN = 'Y')
    ,'')AS GROUP_NAME_LIST,
    IFNULL(
        (SELECT GROUP_CONCAT(AE.GROUP_SEQ, '') FROM TB_MEMBER_GROUP_LINK AE WHERE AE.MEMBER_SEQ = A.SEQ AND AE.USE_YN = 'Y')
    ,'')AS GROUP_SEQ_LIST
FROM
    TB_MEMBER A
        INNER JOIN TB_MEMBER_STATUS AB ON AB.SEQ = A.MEMBER_STATUS_SEQ AND AB.USE_YN = 'Y'
        INNER JOIN TB_MEMBER_GRADE AC ON AC.SEQ = A.MEMBER_GRADE_SEQ AND AC.USE_YN = 'Y'
        INNER JOIN TB_MEMBER_TITLE AD ON AD.SEQ = A.MEMBER_TITLE_SEQ AND AD.USE_YN = 'Y'
        LEFT JOIN TB_FILE B ON A.PROFILE_FILE_SEQ = B.SEQ
    " . $where_sql . "
ORDER BY A.SEQ ASC ";

        $result = $this->db->query( $sql );
        $member_list = $result->result_array();
        
        return $member_list;
    }//     EOF     function selectMember_list( $member_status_seq )

    
    /*
    * 5. 매니저카운트(selectManagerCount)
    */
    function selectManagerCount() {
        $sql = "
SELECT COUNT(SEQ) AS COUNT FROM TB_MEMBER WHERE MEMBER_GRADE_SEQ = 2 ";
        
        $result = $this->db->query($sql);
        $result_array = $result->row_array();
        
        return $result_array['COUNT'];
    }//       EOF       function selectManagerCount()
    
    /*
    * 6. 회원 상태 목록 조회(selectMemberStatusList)
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
    
    /*
    * 7. 회원 등급 목록 조회(selectMemberGradeList)
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

    function selectMemberGroupList($member_seq) {
        $sql = "
SELECT
    A.SEQ,
    A.NAME,
    A.DEPTH_NO,
    A.P_SEQ
FROM TB_GROUP A
INNER JOIN TB_MEMBER_GROUP_LINK B ON A.SEQ = B.GROUP_SEQ
WHERE A.USE_YN = 'Y'
AND B.USE_YN = 'Y'
AND B.MEMBER_SEQ = ?
ORDER BY DEPTH_NO ASC ";

        $result = $this->db->query( $sql, array($member_seq) );
        $member_group_list = $result->result_array();

        return $member_group_list;
    }//     EOF     function selectMemberGroupList($member_seq)
    
    /*
    * 8. 회원 직함 목록 조회(selectMemberTitle_list)
    */
    function selectMemberTitle_list() {
        $sql = "
SELECT
    SEQ,
    NAME
FROM TB_MEMBER_TITLE
WHERE USE_YN = 'Y'
ORDER BY SORT_NO ASC ";
        
        $result = $this->db->query( $sql );
        $member_title_list = $result->result_array();
        
        return $member_title_list;
    }//       EOF       function selectMemberTitle_list

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
            $member_info['TEL'],
            $member_info['BIRTHDAY'],
            $member_info['COMMENT']
        );

        if ( @!is_null($member_info['ENTRY_DATE']) && nvl($member_info['ENTRY_DATE']) !== '' ) {
            $column_sql .= ', ENTRY_DATE';
            $value_sql .= ', ?';
            array_push($param,$member_info['ENTRY_DATE']);
        }
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
        if ( @!is_null($member_info['MEMBER_TITLE_SEQ']) && nvl($member_info['MEMBER_TITLE_SEQ']) !== ''  ) {
            $column_sql .= ', MEMBER_TITLE_SEQ';
            $value_sql .= ', ?';
            array_push($param,$member_info['MEMBER_TITLE_SEQ']);
        }

        $sql = "
INSERT INTO TB_MEMBER(ID,PASSWORD,NAME,TEL,BIRTHDAY,COMMENT" . $column_sql . ")
VALUES( ?, ?, ?, ?, ?, ?" . $value_sql . " ) ";

        $result = $this->db->query( $sql, $param );
        
        return $this->db->insert_id();
    }//       EOF       function insertMember($member_info)
    
    
    /**
     * 회원 그룹 정보 입력/수정
     */
    function updateMemberGroupLink($member_group_link_info) {

        $this->db->trans_start();

        // 기존 그룹 정보 변경
        $sql = "        
UPDATE TB_MEMBER_GROUP_LINK SET USE_YN = 'N'
WHERE MEMBER_SEQ = ? ";
        $this->db->query( $sql, array($member_group_link_info['MEMBER_SEQ']) );

        // 변경된 그룹 정보 입력 및 수정
        $group_seq_list =  $member_group_link_info['GROUP_SEQ'];
        $sql = "
INSERT INTO TB_MEMBER_GROUP_LINK(MEMBER_SEQ, GROUP_SEQ)
VALUES( ?, ? )
ON DUPLICATE KEY UPDATE USE_YN = 'Y' ";

        foreach ($group_seq_list as  $group_seq ) {
            $this->db->query( $sql, array($member_group_link_info['MEMBER_SEQ'], $group_seq) );
        }
        
        return $this->db->trans_complete();
    }//       EOF       function updateMemberGroupLink($member_group_link_info)
    
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
        if ( @!is_null($member_info['TEL']) ) {
            $temp_sql = 'TEL = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['TEL']);
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
        if ( @!is_null($member_info['ENTRY_DATE']) ) {
            $temp_sql = 'ENTRY_DATE = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['ENTRY_DATE']);
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
        if ( @!is_null($member_info['MEMBER_TITLE_SEQ']) ) {
            $temp_sql = 'MEMBER_TITLE_SEQ = ?';
            $set_sql .= $set_sql !== '' ? ','.$temp_sql : $temp_sql;
            array_push($param,$member_info['MEMBER_TITLE_SEQ']);
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

    /**
     * 그룹정보 입력
     */
    function insertGroup( $group_info ) {
        $sql = "
INSERT INTO TB_GROUP(NAME, DEPTH_NO, SORT_NO, P_SEQ)
VALUES( ?, ?, ?, ? ) ";

        $result = $this->db->query( $sql, array($group_info['NAME'], $group_info['DEPTH_NO'], $group_info['SORT_NO'], $group_info['P_SEQ']) );
        
        return $this->db->insert_id();
    }//     EOF     function insertGroup( $group_info )

    /**
     * 그룹정보 수정
     */
    function updateGroup( $group_info ) {
        $this->db->trans_start();
        /**
         * 기존 그룹정보 조회
         */
        $sql = "
SELECT
    SEQ,
    DEPTH_NO,
    SORT_NO,
    IFNULL(P_SEQ,'')AS P_SEQ
FROM TB_GROUP
WHERE SEQ = ? ";

        $result = $this->db->query($sql, array($group_info['SEQ']) );
        $temp_group_info = $result->row_array();

        // SORT_NO 변경 시, 변경될 그룹정보 자동 수정
        if( $group_info['SORT_NO'] !== $temp_group_info['SORT_NO'] ) {
            $tempParam = array($temp_group_info['SORT_NO'], $temp_group_info['DEPTH_NO'], $group_info['SORT_NO'] );
            $set_sql = "";
            if( $temp_group_info['P_SEQ'] !== '' ) {
                $set_sql = "AND P_SEQ = ? ";
                array_push( $tempParam, $temp_group_info['P_SEQ'] );
            }
            // 
            $sql = "
UPDATE TB_GROUP
    SET
        SORT_NO = ?
WHERE DEPTH_NO = ?
AND SORT_NO = ? ". $set_sql;
            
            $this->db->query( $sql, $tempParam );
        }

        $set_sql = "";
        $update_param = array();
        if ( @!is_null($group_info['NAME']) ) {
            $set_sql = "NAME = ?";
            array_push( $update_param, $group_info['NAME'] );
        }
        
        if ( @!is_null($group_info['SORT_NO']) ) {
            if ( $set_sql == "" ) {
                $set_sql = "SORT_NO = ?";
            } else { 
                $set_sql .= ", SORT_NO = ?";
            }
            
            array_push( $update_param, $group_info['SORT_NO'] );
        }
        array_push( $update_param, $group_info['SEQ'] );

        $sql = "
        UPDATE TB_GROUP
            SET 
                 " . $set_sql . "
        WHERE SEQ = ?  ";

        $this->db->query( $sql, $update_param );
        
        return  $this->db->trans_complete();
    }//     EOF     function updateGroup( $group_info )

    /**
     * 그룹정보 삭제
     */
    function deleteGroup($group_seq) {
        $sql = "
UPDATE TB_GROUP 
    SET USE_YN = 'N'
WHERE SEQ = ? ";

        $query_result = $this->db->query($sql, array($group_seq) );

        return $query_result;
    }//     EOF     function deleteGroup($group_seq)
    
}//            EOC       class MemberModel extends CI_Model