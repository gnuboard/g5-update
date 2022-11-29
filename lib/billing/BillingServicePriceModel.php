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
     * @return array|null
     */
    public function selectCurrentPrice($serviceId)
    {
        global $g5;
        
        $bindParam = array();
        $sql = "SELECT
                    price
                FROM {$g5["billing_service_price_table"]}
                WHERE service_id = ?
                    AND (application_date <= NOW() OR application_date IS NULL)
                ORDER BY application_date DESC
                LIMIT 1";
        array_push($bindParam, $serviceId);
     
        return $this->g5Mysqli->getOne($sql, $bindParam);
    }

    /**
     * 
     */
    public function insert($requestData = array())
    {
        global $g5;

        $data = array(
            'service_id' => $requestData['service_id'],
            'price' => $requestData['price'],
            'application_date' => $requestData['application_date']
        );

        return $this->g5Mysqli->insertSQL($g5["billing_service_price_table"], $data);
    }

    /**
     * 
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
