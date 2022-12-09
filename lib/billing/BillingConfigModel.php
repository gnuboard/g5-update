<?php
/**
 * 자동결제 설정 Model
 * - 설정이 변경될 때마다 새로운 데이터를 입력해서 이력을 확인할 수 있도록 처리한다.
 * 
 */
class BillingConfigModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 자동결제 설정 불러오기
     * - 최신 설정정보 1건을 불러온다.
     * @return array
     */
    public function selectOne()
    {
        global $g5;
        
        $sql = "SELECT
                    *
                FROM {$g5['billing_config_table']}
                ORDER BY id DESC
                LIMIT 1";

        return $this->g5Mysqli->getOne($sql);
    }

    /**
     * billing_config Table 전체 Column 조회
     * @return array
     */
    public function selectColumnList()
    {
        global $g5;

        $bindParam = array();
        $sql = "SELECT
                    column_name
                FROM information_schema.columns
                WHERE table_schema = ?
                    AND table_name = ?";
        array_push($bindParam, G5_MYSQL_DB, $g5['billing_config_table']);

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 자동결제 설정 저장
     * @param array $resultData 빌링 키 발급 요청 API 결과데이터
     * @return int
     */
    public function insert($resultData = array())
    {
        global $g5;

        $bindParam = array(
            'bc_use_cancel_refund'  => $resultData['bc_use_cancel_refund'],
            'bc_use_pause'          => $resultData['bc_use_pause'],
            'bc_pg_code'            => $resultData['bc_pg_code'],
            'bc_kcp_site_cd'        => $resultData['bc_kcp_site_cd'],
            'bc_kcp_group_id'       => $resultData['bc_kcp_group_id'],
            'bc_kcp_cert_path'      => $resultData['bc_kcp_cert_path'],
            'bc_kcp_prikey_path'    => $resultData['bc_kcp_prikey_path'],
            'bc_kcp_prikey_password'=> $resultData['bc_kcp_prikey_password'],
            'bc_kcp_is_test'        => $resultData['bc_kcp_is_test'],
            'bc_kcp_curruncy'       => $resultData['bc_kcp_curruncy'],
            'bc_notice_email'       => $resultData['bc_notice_email'],
            'bc_update_ip'          => $resultData['bc_update_ip'],
            'bc_update_id'          => $resultData['bc_update_id'],
            'bc_update_time'        => $resultData['bc_update_time']
        );

        return $this->g5Mysqli->insertSQL($g5['billing_config_table'], $bindParam);
    }
}
