<?php
/**
 * filename : FileModel.php
 * author : HyunHee, Cho (enjoysoft@cocoavision.co.kr)
 * filedate : 2019.01.03
 * comments :
 * history :
 */
class FileModel extends CI_Model {

    function __construct() {
        parent::__construct();
        $this->db = $this->load->database(DB_CONFIG_NAME, TRUE);
    }
    
    function selectFileForSeq($files_seq) {
        $sql = "
SELECT
    SEQ,
    LOGICAL_NAME,
    CONCAT('" . DIRECTORY_SEPARATOR . DATA_DIR . DIRECTORY_SEPARATOR . "', PATH)AS PATH,
    PHYSICAL_NAME,
    SIZE,
    MIMETYPE,
    INFO,
    ADD_DATE,
    MEMBER_SEQ
FROM TB_FILE
WHERE SEQ = ? ";

        $param = array( $files_seq );
        $result = $this->db->query( $sql, $param );
        $files_dao = $result->row_array();

        return $files_dao;
    }//       EOF       function selectFileForSeq($files_seq)
    

    /**
     * 파일 입력
     */
    function insertFile($files_info) {
        $sql = "
INSERT INTO TB_FILE(LOGICAL_NAME, PATH, PHYSICAL_NAME, SIZE, MIMETYPE, INFO, ADD_DATE, MEMBER_SEQ)
VALUES(?, ?, ?, ?, ?, ?, NOW(), ? ) ";

        $param = array($files_info['LOGICAL_NAME'],$files_info['PATH'],$files_info['PHYSICAL_NAME'],$files_info['SIZE'],$files_info['MIMETYPE'],@$files_info['INFO'],$files_info['MEMBER_SEQ']);
        if ( $this->db->query( $sql, $param ) ) {
            $file_seq = $this->db->insert_id();
            $sql = "
UPDATE TB_FILE SET INFO = JSON_INSERT(INFO,'$.SEQ',?)
WHERE SEQ = ? ";
            $this->db->query( $sql, array($file_seq,$file_seq) );
            return $file_seq;
        }
        return false;
    }//       EOF       function insertFile($files_info)

     

     function selectFilesForLastSeq() {
        $sql = "
SELECT
    SEQ
FROM
    TB_FILE
ORDER BY
    SEQ
DESC LIMIT 1;";
        $result = $this->db->query( $sql );
        $files_dao = $result->row_array();

        return $files_dao;
     }



}//            EOC       class FileModel extends CI_Model