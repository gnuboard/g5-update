<?php
/**
 * 구독서비스 가격 Model Class
 */
class BillingServicePriceModel
{
    public $g5Mysqli;

    public function __construct()
    {
        $this->g5Mysqli = G5Mysqli::getInstance();
    }

    /**
     * 구독서비스(상품) 가격목록 조회
     * @param int $serviceId    서비스 ID
     * @return array
     */
    public function selectListByServiceId($serviceId)
    {
        global $g5;

        $bindParam = array();

        $sql = "SELECT 
                    *
                FROM {$g5['billing_service_price_table']}
                WHERE service_id = ?";
        array_push($bindParam, $serviceId);

        return $this->g5Mysqli->execSQL($sql, $bindParam);
    }

    /**
     * 구독서비스(상품) 현재가격 조회
     * @param int $serviceId    서비스 ID
     * @return int
     */
    public function selectCurrentPrice($serviceId)
    {
        global $g5;
        
        $bindParam = array();

        $sql = "SELECT
                    IFNULL(
                        (SELECT
                            price
                        FROM {$g5["billing_service_price_table"]} bsp
                        WHERE bsp.service_id = bs.service_id
                            AND now() BETWEEN application_date AND bsp.application_end_date
                        ORDER BY application_date DESC, price ASC
                        LIMIT 1), 
                        base_price
                    ) AS current_price
                FROM {$g5["billing_service_table"]} bs
                WHERE bs.service_id = ?";
        array_push($bindParam, $serviceId);
     
        $result = $this->g5Mysqli->getOne($sql, $bindParam);

        if (isset($result)) {
            return (int)$result['current_price'];
        } else {
            return 0;
        }
    }

    /**
     * 변경예정 가격 정보 최근 1건 조회
     * @param int $serviceId    구독서비스(상품) ID
     * @return array|null
     */
    public function selectScheduledChangePriceInfo($serviceId)
    {
        global $g5;
        
        $bindParam = array();

        $sql = "SELECT
                    price, application_date
                FROM {$g5['billing_service_price_table']}
                WHERE service_id = ?
                    AND application_date > now()
                ORDER BY application_date ASC
                LIMIT 1";
        array_push($bindParam, $serviceId);

        return $this->g5Mysqli->getOne($sql, $bindParam);
    }

    /**
     * 구독서비스 가격정보 등록
     * @param array $requestData
     * @return bool
     */
    public function insert($requestData = array())
    {
        global $g5;

        $data = array(
            'service_id' => $requestData['service_id'],
            'price' => $requestData['price'],
            'application_date' => $requestData['application_date'],
            'application_end_date' => $requestData['application_end_date']
        );

        return $this->g5Mysqli->insertSQL($g5["billing_service_price_table"], $data);
    }

    /**
     * 구독서비스 가격정보 수정
     * @param int   $id             가격 ID
     * @param array $requestData
     * @return bool
     */
    public function update($id, $requestData = array())
    {
        global $g5;

        $conditional = array(
            "id" => $id
        );

        return $this->g5Mysqli->updateSQL($g5["billing_service_price_table"], $requestData, $conditional);
    }
}
