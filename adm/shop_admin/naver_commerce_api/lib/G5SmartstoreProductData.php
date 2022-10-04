<?php

/**
 * Convert Form Data to commerce API Data
 * 
 * @link https://apicenter.commerce.naver.com/ko/basic/commerce-api
 */
class G5SmartstoreProductData
{
    public $productData = array(
        "originProduct" => array(
            "saleType" => "NEW"
        ),
        // 스마트스토어 채널 상품 (required)
        "smartstoreChannelProduct" => array(
            "naverShoppingRegistration" => false,       // 네이버쇼핑 등록 여부 (required) : 네이버 쇼핑 광고주가 아닌 경우에는 false로 저장됩니다.
            "channelProductDisplayStatusType" => "ON"   // 전시 상태 코드(스마트스토어 채널 전용) (required) : WAIT(전시 대기), ON(전시 중), SUSPENSION(전시 중지)
        )
    );
    public $statusType      = '';       // 상품 판매 상태 코드 (required)
    public $saleType        = '';       // 상품 판매 유형 코드 : NEW(새 상품), OLD(중고 상품)
    public $leafCategoryId  = '';       // 리프 카테고리 ID (상품 등록 시에는 필수)
    public $name            = '';       // 상품명 (required)
    public $images          = array();  // 이미지 정보 (required), 이미지 업로드 API를 사용해 업로드하고 그 반환 값을 업로드해야한다.
    public $detailContent   = '';       // 상품 상세 정보 (required)
    public $salePrice       = '';       // 상품 판매 가격 (required)
    public $stockQuantity   = '';       // 재고 수량
    public $deliveryInfo    = array();  // 배송 정보
    public $detailAttribute = array();  // 원상품 상세 속성 (required)

    public function __construct()
    {
    }

    public function setProductImage($urlList)
    {
        print_r($urlList);
    }
    
    /**
     * Get the value of productData
     */
    public function getProductData()
    {
        return $this->productData;
    }

    /**
     * Set the value of productData
     *
     * @return  self
     */
    public function setProductData($productData)
    {
        $this->productData = $productData;

        return $this;
    }
    
    /**
     * Get the value of statusType
     */
    public function getStatusType()
    {
        return $this->statusType;
    }

    /**
     * Set the value of statusType
     *
     * @return  self
     */
    public function setStatusType($statusType)
    {
        $this->statusType = $statusType;
        $this->productData['originProduct']['statusType'] = $this->statusType;

        return $this;
    }
    
    /**
     * Get the value of saleType
     */ 
    public function getSaleType()
    {
        return $this->saleType;
    }

    /**
     * Set the value of saleType
     *
     * @return  self
     */ 
    public function setSaleType($saleType)
    {
        $this->saleType = $saleType;
        $this->productData['originProduct']['saleType'] = $this->saleType;

        return $this;
    }

    /**
     * Get the value of leafCategoryId
     */ 
    public function getLeafCategoryId()
    {
        return $this->leafCategoryId;
    }

    /**
     * Set the value of leafCategoryId
     *
     * @return  self
     */ 
    public function setLeafCategoryId($leafCategoryId)
    {
        $this->leafCategoryId = $leafCategoryId;
        $this->productData['originProduct']['leafCategoryId'] = $this->leafCategoryId;

        return $this;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->productData['originProduct']['name'] = $this->name;

        return $this;
    }

    /**
     * Get the value of images
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Set the value of images
     * @param string $images    커머스API 이미지 업로드 URL
     * @param string $type      이미지 타입 (representative : 대표이미지, optional : 추가이미지)
     * @return  self
     */
    public function setImages($images, $type)
    {
        if ($type == 'representative') {
            $this->productData['originProduct']['images']['representativeImage']['url'] = $images;
        } elseif ($type == 'optional') {
            $this->productData['originProduct']['images']['optionalImages']['url'][] = $images;
        }
        $this->images = $this->productData['originProduct']['images'];

        return $this;
    }

    /**
     * Get the value of detailContent
     */
    public function getDetailContent()
    {
        return $this->detailContent;
    }

    /**
     * Set the value of detailContent
     *
     * @return  self
     */
    public function setDetailContent($detailContent)
    {
        $this->detailContent = $detailContent;
        $this->productData['originProduct']['detailContent'] = $this->detailContent;

        return $this;
    }

    /**
     * Get the value of salePrice
     */
    public function getSalePrice()
    {
        return $this->salePrice;
    }

    /**
     * Set the value of salePrice
     *
     * @return  self
     */
    public function setSalePrice($salePrice)
    {
        $this->salePrice = $salePrice;
        $this->productData['originProduct']['salePrice'] = $this->salePrice;

        return $this;
    }

    /**
     * Get the value of stockQuantity
     */
    public function getStockQuantity()
    {
        return $this->stockQuantity;
    }

    /**
     * Set the value of stockQuantity
     *
     * @return  self
     */
    public function setStockQuantity($stockQuantity)
    {
        $this->stockQuantity = $stockQuantity;
        $this->productData['originProduct']['stockQuantity'] = $this->stockQuantity;

        return $this;
    }

    /**
     * Get the value of deliveryInfo
     */
    public function getDeliveryInfo()
    {
        return $this->deliveryInfo;
    }

    /**
     * Set the value of deliveryInfo
     *
     * @return  self
     */
    public function setDeliveryInfo($formData)
    {
        global $default;

        $info = array(
            "deliveryType" => "DELIVERY",           // 배송 방법 유형 코드 (required) / DELIVERY(택배, 소포, 등기), DIRECT(직접배송(화물배달))
            "deliveryAttributeType" => "NORMAL",    // 배송 속성 타입 코드 (required)
            "deliveryCompany" => 'CJGLS',           // 택배사 (DELIVERY(택배, 소포, 등기)일 때 필수)
            // 배송비 정보 (required)
            "deliveryFee" => array(
                "deliveryFeeType" => "FREE",            // FREE(무료), CONDITIONAL_FREE(조건부 무료), PAID(유료), UNIT_QUANTITY_PAID(수량별), RANGE_QUANTITY_PAID(구간별)
                "deliveryFeePayType" => "PREPAID"       // 배송비 결제 방식 코드 (COLLECT(착불), PREPAID(선결제), COLLECT_OR_PREPAID(착불 또는 선결제))
            ),
            // 클레임(반품/교환) 정보 (required)
            "claimDeliveryInfo" => array(
                "returnDeliveryFee" => $default['de_send_cost_list'],     // 반품 배송비 (required)
                "exchangeDeliveryFee" => $default['de_send_cost_list'],   // 교환 배송비 (required)
            ),
        );
        // 기본 배송비
        $info['deliveryFee']['baseFee'] = isset($formData['it_sc_price']) ? $formData['it_sc_price'] : $default['de_send_cost_list'];

        // 배송비 타입
        switch($formData['it_sc_type']) {
            case 1 :
                $info['deliveryFee']['deliveryFeeType'] = 'FREE';
                break;
            case 2 :
                $info['deliveryFee']['deliveryFeeType'] = 'CONDITIONAL_FREE';
                $info['deliveryFee']['freeConditionalAmount'] = $formData['it_sc_minimum'];     // 무료 조건 금액
                break;
            case 3 :
                $info['deliveryFee']['deliveryFeeType'] = 'PAID';
                break;
            case 4 :
                $info['deliveryFee']['deliveryFeeType'] = 'UNIT_QUANTITY_PAID';
                $info['deliveryFee']['repeatQuantity'] = $formData['it_sc_qty'];    // 기본 배송비 반복 부과 수량
                break;
            default :
                if ($default['de_send_cost_case'] == '차등') {
                    $info['deliveryFee']['deliveryFeeType'] = 'PAID';
                    // 금액별 차등 설정
                    // $info['deliveryFee']['baseFee']
                } else {
                    $info['deliveryFee']['deliveryFeeType'] = 'FREE';
                }
                break;
        }
        
        // 배송비 결제 방식
        switch($formData['it_sc_method']) {
            case 1 :
                $info['deliveryFee']['deliveryFeePayType'] = 'COLLECT';
                break;
            case 2 :
                $info['deliveryFee']['deliveryFeePayType'] = 'COLLECT_OR_PREPAID';
                break;
        }

        $this->deliveryInfo = $info;
        $this->productData['originProduct']['deliveryInfo'] = $this->deliveryInfo;
        
        return $this;
    }

    /**
     * Get the value of detailAttribute
     */
    public function getDetailAttribute()
    {
        return $this->detailAttribute;
    }

    /**
     * Set the value of detailAttribute
     *
     * @return  self
     */
    public function setDetailAttribute($formData)
    {
        global $default;

        $detailAttribute = array(
            // A/S 정보 (required)
            "afterServiceInfo" => array(
                "afterServiceTelephoneNumber" => $default['de_admin_company_tel'],  // A/S 전화번호 (required)
                "afterServiceGuideContent" => $default['de_admin_company_tel']      // A/S 안내 (required)
            ),
            // 원산지 정보 (required)
            "originAreaInfo" => array(
                "originAreaCode" => "00"    // 원산지 상세 지역 코드 (required)
                // "content" => "원산지",    // 원산지 표시 내용, originAreaCode가 '기타: 직접 입력'인 경우 필수
            ),
            // 판매자 코드 정보
            // "sellerCodeInfo" => array(
                // "sellerManagementCode" => "string", // 판매자 관리 코드
                // "sellerBarcode" => "string",        // 판매자 바코드
                // "sellerCustomCode1" => "string",    // 판매자 내부 코드 1
                // "sellerCustomCode2" => "string"     // 판매자 내부 코드 2
            // ),
            // 옵션 정보 (데이터 확인 필요)
            // "optionInfo" => array(
            //     "simpleOptionSortType" => "CREATE",
            //     "optionSimple" => array(
            //         array(
            //             "id" => 0,
            //             "groupName" => "string",
            //             "name" => "string",
            //             "usable" => true
            //         )
            //     ),
            //     "optionCustom" => array(
            //         array(
            //             "id" => 0,
            //             "groupName" => "string",
            //             "name" => "string",
            //             "usable" => true
            //         )
            //     ),
            //     "optionCombinationSortType" => "CREATE",
            //     "optionCombinationGroupNames" => array(
            //         "optionGroupName1" => "string",
            //         "optionGroupName2" => "string",
            //         "optionGroupName3" => "string",
            //         "optionGroupName4" => "string"
            //     ),
            //     "optionCombinations" => array(
            //         array(
            //             "id" => 0,
            //             "optionName1" => "string",
            //             "optionName2" => "string",
            //             "optionName3" => "string",
            //             "optionName4" => "string",
            //             "stockQuantity" => 99999999,
            //             "price" => 999999990,
            //             "sellerManagerCode" => "string",
            //             "usable" => true
            //         )
            //     ),
            //     "standardOptionGroups" => array(
            //         array(
            //             "groupName" => "string",
            //             "standardOptionAttributes" => array(
            //                 array(
            //                     "attributeId" => 0,
            //                     "attributeValueId" => 0,
            //                     "attributeValueName" => "string",
            //                     "imageUrls" => array(
            //                         "string"
            //                     )
            //                 )
            //             )
            //         )
            //     ),
            //     "optionStandards" => array(
            //         array(
            //             "id" => 0,
            //             "optionName1" => "string",
            //             "optionName2" => "string",
            //             "stockQuantity" => 99999999,
            //             "sellerManagerCode" => "string",
            //             "usable" => true
            //         )
            //     ),
            //     "useStockManagement" => true,
            //     "optionDeliveryAttributes" => array(
            //         "string"
            //     )
            // ),
            // 추가 상품 정보
            // "supplementProductInfo" => array(
            //     "sortType" => "CREATE",
            //     "supplementProducts" => array(
            //         array(
            //             "id" => 0,
            //             "groupName" => "string",
            //             "name" => "string",
            //             "price" => 999999990,
            //             "stockQuantity" => 99999999,
            //             "sellerManagementCode" => "string",
            //             "usable" => true
            //         )
            //     )
            // ),
            // 도서 항목 부가 정보
            // "bookInfo" => array(
            //     "publishDay" => "string",   // 출간일 'YYYY-MM-DD'
            //     // 출판사 (required)
            //     "publisher" => array(
            //         "code" => "string",     // 코드
            //         "text" => "string"      // 텍스트 (출판사는 필수값으로 입력)
            //     ),
            //     // 글작가명 (required)
            //     "authors" => array(
            //         array(
            //             "code" => "string",     // 코드
            //             "text" => "string"      // 텍스트
            //         )
            //     ),
            // ),
            // "manufactureDate" => "2019-08-24",  // 제조일자 'yyyy-MM-dd'
            // "validDate" => "2019-08-24",        // 유효일자 'yyyy-MM-dd'
            "taxType" => ($formData['it_notax'] == 1) ? "DUTYFREE" : "TAX",     // 부가가치세 타입 코드 : TAX(과세 상품), DUTYFREE(면세 상품), SMALL(영세 상품)
            // 인증 정보 목록 ('어린이제품 인증 대상' 카테고리 상품인 경우 필수)
            // "productCertificationInfos" => array(
            //     array(
            //         "certificationInfoId" => 0,                     // 인증 유형 ID (required)
            //         "certificationKindType" => "KC_CERTIFICATION",  // 인증 정보 종류 코드 : KC_CERTIFICATION(KC 인증), CHILD_CERTIFICATION(어린이제품 인증), GREEN_PRODUCTS(친환경 인증), OVERSEAS(구매대행(구매대행 선택 시 인증 정보 필수 등록)), PARALLEL_IMPORT(병행수입(병행수입 선택 시 인증 정보 필수 등록)), ETC(기타 인증)
            //         "name" => "string",                             // 인증 기관명 (어린이제품/생활용품/전기용품 공급자적합성 유형인 경우 비필수)
            //         "certificationNumber" => "string",              // 인증번호 (어린이제품/생활용품/전기용품 공급자적합성 유형인 경우 비필수)
            //         "certificationDate" => "2019-08-24"             // 인증일자
            //     )
            // ),
            "minorPurchasable" => true,     // 미성년자 구매 가능 여부 (required)
        );
        $detailAttribute['productInfoProvidedNotice'] = $this->setProductInfoProvidedNotice($formData);

        $this->detailAttribute = $detailAttribute;
        $this->productData['originProduct']['detailAttribute'] = $this->detailAttribute;

        return $this;
    }

    public function setProductInfoProvidedNotice($formData)
    {
        if (isset($formData['ir_info_gubun'])) {

        }

        // 상품정보제공고시 (상품 등록 시 필수)
        $productInfoProvidedNotice = array(
            "productInfoProvidedNoticeType" => "WEAR"      // 상품정보제공고시 타입
            , "wear" => array(
                "returnCostReason" => "1",
                "noRefundReason" => "1",
                "qualityAssuranceStandard" => "1",
                "compensationProcedure" => "1",
                "troubleShootingContents" => "1",
                "material" => "제품소재",
                "color" => "색상",
                "size" => "치수",
                "manufacturer" => "제조자(사)",
                "caution" => "세탁 방법",
                "packDateType" => "DIRECT", // CALENDER
                // "packDate" => array(
                //     "year" => 0,
                //     "month" => "JANUARY",
                //     "leapYear" => true,
                //     "monthValue" => 0
                // ),
                "packDateText" => "2022년 10월 4일",
                "warrantyPolicy" => "품질 보증 기준",
                "afterServiceDirector" => "A/S 책임자와 전화번호"
            ),
            /*
            "shoes" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "material" => "string",
                "color" => "string",
                "size" => "string",
                "height" => "string",
                "manufacturer" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "bag" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "type" => "string",
                "material" => "string",
                "color" => "string",
                "size" => "string",
                "manufacturer" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "fashionItems" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "type" => "string",
                "material" => "string",
                "size" => "string",
                "manufacturer" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "sleepingGear" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "material" => "string",
                "color" => "string",
                "size" => "string",
                "components" => "string",
                "manufacturer" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "furniture" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "certificationType" => "string",
                "color" => "string",
                "components" => "string",
                "material" => "string",
                "manufacturer" => "string",
                "importer" => "string",
                "producer" => "string",
                "size" => "string",
                "installedCharge" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "imageAppliances" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "energyEfficiencyRating" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "displaySpecification" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "homeAppliances" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "energyEfficiencyRating" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "seasonAppliances" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "energyEfficiencyRating" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "area" => "string",
                "installedCharge" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "officeAppliances" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "energyEfficiencyRating" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "weight" => "string",
                "specification" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "opticsAppliances" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "weight" => "string",
                "specification" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "microElectronics" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "weight" => "string",
                "specification" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "navigation" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "weight" => "string",
                "specification" => "string",
                "updateCost" => "string",
                "freeCostPeriod" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "carArticles" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "certificationType" => "string",
                "caution" => "string",
                "manufacturer" => "string",
                "size" => "string",
                "applyModel" => "string",
                "warrantyPolicy" => "string",
                "roadWorthyCertification" => "string",
                "afterServiceDirector" => "string"
            ),
            "medicalAppliances" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "licenceNo" => "string",
                "advertisingCertificationType" => "string",
                "ratedVoltage" => "string",
                "powerConsumption" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "purpose" => "string",
                "usage" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "kitchenUtensils" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "material" => "string",
                "component" => "string",
                "size" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "producer" => "string",
                "importDeclaration" => true,
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "cosmetic" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "capacity" => "string",
                "specification" => "string",
                "expirationDateType" => "CALENDER",
                "expirationDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "expirationDateText" => "string",
                "usage" => "string",
                "manufacturer" => "string",
                "producer" => "string",
                "distributor" => "string",
                "mainIngredient" => "string",
                "certificationType" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "jewellery" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "material" => "string",
                "purity" => "string",
                "bandMaterial" => "string",
                "weight" => "string",
                "manufacturer" => "string",
                "producer" => "string",
                "size" => "string",
                "caution" => "string",
                "specification" => "string",
                "provideWarranty" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "food" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "foodItem" => "string",
                "weight" => "string",
                "amount" => "string",
                "size" => "string",
                "packDateType" => "CALENDER",
                "packDate" => "2019-08-24",
                "packDateText" => "string",
                "expirationDateType" => "CALENDER",
                "expirationDate" => "2019-08-24",
                "expirationDateText" => "string",
                "producer" => "string",
                "relevantLawContent" => "string",
                "productComposition" => "string",
                "keep" => "string",
                "adCaution" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "generalFood" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "productName" => "string",
                "foodType" => "string",
                "producer" => "string",
                "location" => "string",
                "packDateType" => "CALENDER",
                "packDate" => "2019-08-24",
                "packDateText" => "string",
                "expirationDateType" => "CALENDER",
                "expirationDate" => "2019-08-24",
                "expirationDateText" => "string",
                "weight" => "string",
                "amount" => "string",
                "ingredients" => "string",
                "nutritionFacts" => "string",
                "geneticallyModified" => true,
                "consumerSafetyCaution" => "string",
                "importDeclarationCheck" => true,
                "customerServicePhoneNumber" => "string"
            ),
            "dietFood" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "productName" => "string",
                "foodType" => "string",
                "producer" => "string",
                "location" => "string",
                "packDateType" => "CALENDER",
                "packDate" => "2019-08-24",
                "packDateText" => "string",
                "expirationDateType" => "CALENDER",
                "expirationDate" => "2019-08-24",
                "expirationDateText" => "string",
                "weight" => "string",
                "amount" => "string",
                "ingredients" => "string",
                "nutritionFacts" => "string",
                "specification" => "string",
                "cautionAndSideEffect" => "string",
                "nonMedicinalUsesMessage" => "string",
                "geneticallyModified" => true,
                "importDeclarationCheck" => true,
                "consumerSafetyCaution" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "kids" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificationType" => "string",
                "size" => "string",
                "weight" => "string",
                "color" => "string",
                "material" => "string",
                "recommendedAge" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "caution" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "musicalInstrument" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "size" => "string",
                "color" => "string",
                "material" => "string",
                "components" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "detailContent" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "sportsEquipment" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "size" => "string",
                "weight" => "string",
                "color" => "string",
                "material" => "string",
                "components" => "string",
                "releaseDateType" => "CALENDER",
                "releaseDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "releaseDateText" => "string",
                "manufacturer" => "string",
                "detailContent" => "string",
                "warrantyPolicy" => "string",
                "afterServiceDirector" => "string"
            ),
            "books" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "title" => "string",
                "author" => "string",
                "publisher" => "string",
                "size" => "string",
                "pages" => "string",
                "components" => "string",
                "publishDateType" => "CALENDER",
                "publishDate" => "2019-08-24",
                "publishDateText" => "string",
                "description" => "string"
            ),
            "rentalEtc" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "ownershipTransferCondition" => "string",
                "payingForLossOrDamage" => "string",
                "refundPolicyForCancel" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "digitalContents" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "producer" => "string",
                "termsOfUse" => "string",
                "usePeriod" => "string",
                "medium" => "string",
                "requirement" => "string",
                "cancelationPolicy" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "giftCard" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "issuer" => "string",
                "periodStartDate" => "2019-08-24",
                "periodEndDate" => "2019-08-24",
                "periodDays" => 0,
                "termsOfUse" => "string",
                "useStorePlace" => "string",
                "useStoreAddressId" => 0,
                "useStoreUrl" => "string",
                "refundPolicy" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "mobileCoupon" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "issuer" => "string",
                "usableCondition" => "string",
                "usableStore" => "string",
                "cancelationPolicy" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "movieShow" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "sponsor" => "string",
                "actor" => "string",
                "rating" => "string",
                "showTime" => "string",
                "showPlace" => "string",
                "cancelationCondition" => "string",
                "cancelationPolicy" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "etcService" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "serviceProvider" => "string",
                "certificateDetails" => "string",
                "usableCondition" => "string",
                "cancelationStandard" => "string",
                "cancelationPolicy" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "biochemistry" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "productName" => "string",
                "dosageForm" => "string",
                "packDateType" => "CALENDER",
                "packDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "packDateText" => "string",
                "expirationDateType" => "CALENDER",
                "expirationDate" => array(
                    "year" => 0,
                    "month" => "JANUARY",
                    "leapYear" => true,
                    "monthValue" => 0
                ),
                "expirationDateText" => "string",
                "weight" => "string",
                "effect" => "string",
                "importer" => "string",
                "producer" => "string",
                "manufacturer" => "string",
                "childProtection" => "string",
                "chemicals" => "string",
                "caution" => "string",
                "safeCriterionNo" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "biocidal" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "productName" => "string",
                "weight" => "string",
                "effect" => "string",
                "rangeOfUse" => "string",
                "importer" => "string",
                "producer" => "string",
                "manufacturer" => "string",
                "childProtection" => "string",
                "harmfulChemicalSubstance" => "string",
                "maleficence" => "string",
                "caution" => "string",
                "approvalNumber" => "string",
                "customerServicePhoneNumber" => "string"
            ),
            "etc" => array(
                "returnCostReason" => "string",
                "noRefundReason" => "string",
                "qualityAssuranceStandard" => "string",
                "compensationProcedure" => "string",
                "troubleShootingContents" => "string",
                "itemName" => "string",
                "modelName" => "string",
                "certificateDetails" => "string",
                "manufacturer" => "string",
                "afterServiceDirector" => "string",
                "customerServicePhoneNumber" => "string"
            ) */
        );
        /* 구매 수량 설정 정보*/
        // 최소 구매 수량 <= 10000
        if (isset($formData['it_buy_min_qty']) && $formData['it_buy_min_qty'] >= 2) {
            $detailAttribute['purchaseQuantityInfo']['minPurchaseQuantity'] = $formData['it_buy_min_qty'];
        }
        // 1회 최대 구매 수량 <= 10000
        if (isset($formData['it_buy_max_qty']) && $formData['it_buy_max_qty'] >= 1) {
            $detailAttribute['purchaseQuantityInfo']['maxPurchaseQuantityPerOrder'] = $formData['it_buy_max_qty'];
        }
        // 1인 최대 구매 수량 <= 10000
        // $detailAttribute['purchaseQuantityInfo']['maxPurchaseQuantityPerId'] = $formData['it_buy_max_qty'];

        return $productInfoProvidedNotice;
    }

}
