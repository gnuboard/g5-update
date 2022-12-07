<?php
/**
 * 자동결제 스케쥴러 실행기록 Model Class
 */
class BillingSchedulerHistoryModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 실행기록 목록 조회
     * @param array $requestData
     * @return array
     */
    public function selectList($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *
                FROM {$g5['billing_scheduler_history_table']}
                WHERE 1 = 1";

        /* 검색조건 */
        if ((isset($requestData['sdate']) && !empty($requestData['sdate']))
            && (isset($requestData['edate']) && !empty($requestData['edate']))) {
            $sql .= ' AND DATE_FORMAT(start_time, "%Y-%m-%d") BETWEEN ? AND ? ';
            array_push($bindParam, $requestData['sdate'], $requestData['edate']);
        }
        /* 정렬 */
        if (!empty($requestData['sst'])) {
            $sql .= " ORDER BY {$requestData['sst']} {$requestData['sod']} ";
        }
        /* 반환 결과 수 */
        if (!empty($requestData['offset']) && !empty($requestData['rows'])) {
            $sql .= " LIMIT ?, ?";
            array_push($bindParam, $requestData['offset'], $requestData['rows']);
        }

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 실행기록 전체 건수 조회
     * @param array $requestData
     * @return int
     */
    public function selectTotalCount($requestData = array())
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    COUNT(*) as cnt
                FROM {$g5['billing_scheduler_history_table']}
                WHERE 1 = 1";

        /* 검색조건 */
        if (isset($requestData['sdate']) && !empty($requestData['sdate'])
            && isset($requestData['edate']) && !empty($requestData['edate'])) {
            $sql .= ' AND start_time BETWEEN ? AND ? ';
            array_push($bindParam, $requestData['sdate'], $requestData['edate']);
        }
        
        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        return (int)$result['cnt'];
    }

    

    /**
     * 실행기록 등록
     * @param array $requestData
     * @return bool
     */
    public function insert($requestData = array())
    {
        global $g5;

        $data = array(
            'success_count' => $requestData['success_count'],
            'fail_count' => $requestData['fail_count'],
            'state' => $requestData['state'],
            'start_time' => $requestData['start_time']
        );

        return $this->g5Mysqli->insertSQL($g5["billing_scheduler_history_table"], $data);
    }

    /**
     * 실행기록 수정
     * @param int   $id             실행기록 ID
     * @param array $requestData
     * @return bool
     */
    public function update($id, $requestData = array())
    {
        global $g5;

        $conditional = array(
            "id" => $id
        );

        return $this->g5Mysqli->updateSQL($g5["billing_scheduler_history_table"], $requestData, $conditional);
    }
}
