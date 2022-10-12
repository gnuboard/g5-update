<?php

/**
 * Convert Form Data to commerce API Data
 * 
 * @link https://apicenter.commerce.naver.com/ko/basic/commerce-api
 * @todo 옵션(조합형)+추가상품정보 연동
 */
class G5SmartstoreProductData
{
    public $productData = array(
        "originProduct" => array(
            "saleType" => "NEW"
        ),
        // 배송정보
        "deliveryInfo" => array(
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
                "returnDeliveryFee" => 0,     // 반품 배송비 (required)
                "exchangeDeliveryFee" => 0    // 교환 배송비 (required)
            )
        ),
        // 스마트스토어 채널 상품 (required)
        "smartstoreChannelProduct" => array(
            "naverShoppingRegistration" => false,       // 네이버쇼핑 등록 여부 (required) : 네이버 쇼핑 광고주가 아닌 경우에는 false로 저장됩니다.
            "channelProductDisplayStatusType" => "ON"   // 전시 상태 코드(스마트스토어 채널 전용) (required) : WAIT(전시 대기), ON(전시 중), SUSPENSION(전시 중지)
        )
    );

    /**
     * @var array lib/iteminfo.lib.php > article Key => 커머스API 상품별 필드 Key 배열
     */
    public $convertKeyArray = array(
        "wear" => array(
            "material"          => "material",
            "color"             => "color",
            "size"              => "size",
            "maker"             => "manufacturer",
            "caution"           => "caution",
            // "" => "packDateType",
            "manufacturing_ym"  => "packDateText",
            "warranty"          => "warrantyPolicy",
            "as"                => "afterServiceDirector"
        ),
        "shoes" => array(
            "material"          => "material",
            "color"             => "color",
            "size"              => "size",
            "height"            => "height",
            "maker"             => "manufacturer",
            "madein"            => "",
            "caution"           => "caution",
            "warranty"          => "warrantyPolicy",
            "as"                => "afterServiceDirector"
        ),
        "bag" => array(
            "kind" => "type",
            "material" => "material",
            "color" => "color",
            "size" => "size",
            "maker" => "manufacturer",
            "madein" => "",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "fashionItems" => array(
            "kind" => "type",
            "material" => "material",
            "size" => "size",
            "maker" => "manufacturer",
            "madein" => "",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "sleepingGear" => array(
            "material" => "material",
            "color" => "color",
            "size" => "size",
            "component" => "components",
            "maker" => "manufacturer",
            "madein" => "",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "furniture" => array(
            "product_name" => "itemName",
            "certification" => "certificationType",
            "color" => "color",
            "component" => "components",
            "material" => "material",
            "maker" => "manufacturer",
            "madein" => "producer",
            "size" => "size",
            "delivery" => "installedCharge",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "imageAppliances" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            "energy_efficiency" => "energyEfficiencyRating",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "display_specification" => "displaySpecification",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "homeAppliances" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            "energy_efficiency" => "energyEfficiencyRating",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "seasonAppliances" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            "energy_efficiency" => "energyEfficiencyRating",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "area" => "area",
            "installation_costs" => "installedCharge",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "officeAppliances" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            "energy_efficiency" => "energyEfficiencyRating",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "weight" => "weight",
            "specification" => "specification",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "opticsAppliances" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "weight" => "weight",
            "specification" => "specification",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "microElectronics" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "weight" => "weight",
            "specification" => "specification",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "mobile" => array(
        ),
        "navigation" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "weight" => "weight",
            "specification" => "specification",
            "update_cost" => "updateCost",
            "freecost_period" => "freeCostPeriod",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "carArticles" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "certification" => "certificationType",
            "maker" => "manufacturer",
            "madein" => "",
            "size" => "size",
            "apply_model" => "applyModel",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "pass_number" => "roadWorthyCertification",
            "as" => "afterServiceDirector"
        ),
        "medicalAppliances" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "license_number" => "licenceNo",
            "advertising" => "advertisingCertificationType",
            "rated_voltage" => "ratedVoltage",
            "power_consumption" => "powerConsumption",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "appliances_purpose" => "purpose",
            "appliances_usage" => "usage",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "kitchenUtensils" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "material" => "material",
            "component" => "component",
            "size" => "size",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "producer",
            "import_declaration" => "importDeclaration",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "cosmetics" => array(
            "capacity" => "capacity",
            "specification" => "specification",
            // "" => "expirationDateType",
            // "" => "expirationDate",
            "expiration_date" => "expirationDateText",
            "usage" => "usage",
            "maker" => "manufacturer",
            "distributor" => "distributor",
            "madein" => "producer",
            "mainingredient" => "mainIngredient",
            "certification" => "certificationType",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "as" => "customerServicePhoneNumber"
        ),
        "jewellry" => array(
            "material" => "material",
            "purity" => "purity",
            "band" => "bandMaterial",
            "weight" => "weight",
            "maker" => "manufacturer",
            "madein" => "producer",
            "size" => "size",
            "caution" => "caution",
            "specification" => "specification",
            "provide_warranty" => "provideWarranty",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "food" => array(
            "product_name" => "foodItem",
            "weight" => "weight",
            "quantity" => "amount",
            "size" => "size",
            "producer" => "producer",
            "origin" => "",
            // "" => "packDateType",
            // "" => "packDate",
            "manufacturing_ymd" => "packDateText",
            // "" => "expirationDateType",
            // "" => "expirationDate",
            "expiration_date" => "expirationDateText",
            "law_content" => "relevantLawContent",
            "product_composition" => "productComposition",
            "keep" => "keep",
            "caution" => "adCaution",
            "as" => "customerServicePhoneNumber"
        ),
        "generalFood" => array(
            "product_name" => "productName",
            "food_type" => "foodType",
            "producer" => "producer",
            "location" => "location",
            // "" => "packDateType",
            // "" => "packDate",
            "manufacturing_ymd" => "packDateText",
            // "" => "expirationDateType",
            // "" => "expirationDate",
            "expiration_date" => "expirationDateText",
            "weight" => "weight",
            "quantity" => "amount",
            "ingredients" => "ingredients",
            "nutrition_component" => "nutritionFacts",
            "genetically_modified" => "geneticallyModified",
            "caution" => "consumerSafetyCaution",
            "imported_food" => "importDeclarationCheck",
            "as" => "customerServicePhoneNumber"
        ),
        "dietFood" => array(
            "product_name" => "productName",
            "food_type" => "foodType",
            "producer" => "producer",
            "location" => "location",
            // "" => "packDateType",
            // "" => "packDate",
            "manufacturing_ymd" => "packDateText",
            // "" => "expirationDateType",
            // "" => "expirationDate",
            "expiration_date" => "expirationDateText",
            "waight" => "weight",
            "quantity" => "amount",
            "ingredients" => "ingredients",
            "nutrition" => "nutritionFacts",
            "specification" => "specification",
            "intake" => "cautionAndSideEffect",
            "disease" => "nonMedicinalUsesMessage",
            "genetically_modified" => "geneticallyModified",
            "imported_food" => "importDeclarationCheck",
            "caution" => "consumerSafetyCaution",
            "as" => "customerServicePhoneNumber",
        ),
        "kids" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certification" => "certificationType",
            "size" => "size",
            "weight" => "weight",
            "color" => "color",
            "material" => "material",
            "age" => "recommendedAge",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "caution" => "caution",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "musicalInstrument" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "size" => "size",
            "color" => "color",
            "material" => "material",
            "components" => "components",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "detailed_specifications" => "detailContent",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "sportsEquipment" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "size" => "size",
            "weight" => "weight",
            "color" => "color",
            "material" => "material",
            "components" => "components",
            // "" => "releaseDateType",
            // "" => "releaseDate",
            "released_date" => "releaseDateText",
            "maker" => "manufacturer",
            "madein" => "",
            "detailed_specifications" => "detailContent",
            "warranty" => "warrantyPolicy",
            "as" => "afterServiceDirector"
        ),
        "books" => array(
            "product_name" => "title",
            "author" => "author",
            "publisher" => "publisher",
            "size" => "size",
            "pages" => "pages",
            "components" => "components",
            // "" => "publishDateType",
            // "" => "publishDate",
            "publish_date" => "publishDateText",
            "description" => "description"
        ),
        "rental_etc" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "transfer_of_ownership" => "ownershipTransferCondition",
            "consumer_responsibility" => "payingForLossOrDamage",
            "refund" => "refundPolicyForCancel",
            "as" => "customerServicePhoneNumber"
        ),
        "digitalContents" => array(
            "producer" => "producer",
            "terms_of_use" => "termsOfUse",
            "use_period" => "usePeriod",
            "product_offers" => "medium",
            "minimum_system" => "requirement",
            "maintenance" => "cancelationPolicy",
            "as" => "customerServicePhoneNumber"
        ),
        "giftCard" => array(
            "isseur" => "issuer",
            // "" => "periodStartDate"  string <date> (유효기간 시작일) 'yyyy-MM-dd'
            // "" => "periodEndDate"    string <date> (유효기간 종료일) 'yyyy-MM-dd'
            "expiration_date" => "periodDays", // <int32>
            "terms_of_use" => "termsOfUse",
            "use_store" => "useStorePlace",
            "refund_policy" => "refundPolicy",
            "as" => "customerServicePhoneNumber"
        ),
        "mobileCoupon" => array(
            "isseur" => "issuer",
            "expiration_date" => "usableCondition",
            "use_store" => "usableStore",
            "refund_policy" => "cancelationPolicy",
            "as" => "customerServicePhoneNumber"
        ),
        "movieShow" => array(
            "sponsor"       => "sponsor",
            "actor"         => "actor",
            "rating"        => "rating",
            "showTime"      => "showTime",
            "showPlace"     => "showPlace",
            "cancelationCondition" => "cancelationCondition",
            "cancelationPolicy" => "cancelationPolicy",
            "customerServicePhoneNumber" => "customerServicePhoneNumber"
        ),
        "etcService" => array(
            "serviceProvider"   => "serviceProvider",
            "certificateDetails"=> "certificateDetails",
            "usableCondition"   => "usableCondition",
            "cancelationStandard" => "cancelationStandard",
            "cancelationPolicy"   => "cancelationPolicy",
            "customerServicePhoneNumber"    => "customerServicePhoneNumber"
        ),
        "biochemistry" => array(
            "productName"           => "productName",
            "dosageForm"            => "dosageForm",
            // "" => "packDateType",
            // "" => "packDate",
            "packDateText"          => "packDateText",
            // "" => "expirationDateType",
            // "" => "expirationDate",
            "expirationDateText"    => "expirationDateText",
            "weight"                => "weight",
            "effect"                => "effect",
            "importer"              => "importer",
            "producer"              => "producer",
            "manufacturer"          => "manufacturer",
            "childProtection"       => "childProtection",
            "chemicals"             => "chemicals",
            "caution"               => "caution",
            "safeCriterionNo"       => "safeCriterionNo",
            "customerServicePhoneNumber" => "customerServicePhoneNumber"
        ),
        "biocidal" => array(
            "productName"           => "productName",
            "weight"                => "weight",
            "effect"                => "effect",
            "rangeOfUse"            => "rangeOfUse",
            "importer"              => "importer",
            "producer"              => "producer",
            "manufacturer"          => "manufacturer",
            "childProtection"       => "childProtection",
            "harmfulChemicalSubstance" => "harmfulChemicalSubstance",
            "maleficence"           => "maleficence",
            "caution"               => "caution",
            "approvalNumber"        => "approvalNumber",
            "customerServicePhoneNumber" => "customerServicePhoneNumber"
        ),
        "etc" => array(
            "product_name" => "itemName",
            "model_name" => "modelName",
            "certified_by_law" => "certificateDetails",
            "origin" => "manufacturer",
            "maker" => "afterServiceDirector",
            "as" => "customerServicePhoneNumber"
        ),
    );
    public $containReleaseDateTypeFieldList = array(
        "homeAppliances", "imageAppliances", "seasonAppliances", "officeAppliances", "opticsAppliances",
        "microElectronics","navigation", "carArticles", "medicalAppliances","kitchenUtensils",
        "kids", "musicalInstrument", "sportsEquipment"
    );
    public $containExpirationDateTypeFieldList = array(
        "cosmetics", "food", "generalFood", "dietFood", "biochemistry"
    );
    public $containPackDateTypeFieldList = array(
        "wear", "food", "generalFood", "dietFood", "biochemistry"
    );

    
    public $statusType      = '';       // 상품 판매 상태 코드 (required)
    public $saleType        = '';       // 상품 판매 유형 코드 : NEW(새 상품), OLD(중고 상품)
    public $leafCategoryId  = '';       // 리프 카테고리 ID (상품 등록 시에는 필수)
    public $name            = '';       // 상품명 (required)
    public $images          = array();  // 이미지 정보 (required), 이미지 업로드 API를 사용해 업로드하고 그 반환 값을 업로드해야한다.
    public $detailContent   = '';       // 상품 상세 정보 (required)
    public $salePrice       = '';       // 상품 판매 가격 (required)
    public $stockQuantity   = '';       // 재고 수량
    public $detailAttribute = array();  // 원상품 상세 속성 (required)

    public function __construct()
    {
        global $default;

        // 기본 배송비 default
        $this->productData['deliveryInfo']['claimDeliveryInfo']['returnDeliveryFee'] = $default['de_send_cost_list'];
        $this->productData['deliveryInfo']['claimDeliveryInfo']['exchangeDeliveryFee'] = $default['de_send_cost_list'];
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
     * Set the value of productData->deliveryInfo
     *
     * @return  self
     */
    public function setDeliveryInfo($formData)
    {
        global $default;

        $info = $this->productData['originProduct']['deliveryInfo'];

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
            default :
                $info['deliveryFee']['deliveryFeePayType'] = 'PREPAID';
                break;
        }
        // 클레임(반품/교환) 정보 (required)
        $info['claimDeliveryInfo']['returnDeliveryFee'] = $default['de_send_cost_list'];     // 반품 배송비 (required)
        $info['claimDeliveryInfo']['exchangeDeliveryFee'] = $default['de_send_cost_list'];     // 교환 배송비 (required)

        $this->productData['originProduct']['deliveryInfo'] = $info;
        
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
            // 네이버쇼핑 검색 정보
            "naverShoppingSearchInfo" => array(
                // "modelId" => 0,                              // integer <int64> (모델명 ID)
                "manufacturerName" => $formData['it_maker'],    // string (제조사명)
                "brandName" => $formData['it_brand'],           // string (브랜드명)
                "modelName" => $formData['it_model']            // string (모델명)
            ),
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
            // 옵션 정보 (데이터 확인 필요)
            // "optionInfo" => array(
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
            //             "groupName" => "string",
            //             "name" => "string",
            //             "price" => 999999990,
            //             "stockQuantity" => 99999999,
            //         ),
            //         array(
            //             "groupName" => "string",
            //             "name" => "string",
            //             "price" => 999999990,
            //             "stockQuantity" => 99999999,
            //         ) ..
            //     )
            // ),
            "taxType" => ($formData['it_notax'] == 1) ? "DUTYFREE" : "TAX",     // 부가가치세 타입 코드 : TAX(과세 상품), DUTYFREE(면세 상품), SMALL(영세 상품)
            "minorPurchasable" => true,     // 미성년자 구매 가능 여부 (required)
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

        /* 상품정보제공고시 설정 */
        $detailAttribute['productInfoProvidedNotice'] = $this->setProductInfoProvidedNotice($formData);

        $this->detailAttribute = $detailAttribute;
        $this->productData['originProduct']['detailAttribute'] = $this->detailAttribute;

        return $this;
    }

    /**
     * 상품정보제공고시 설정
     * - 그누보드의 상품정보제공고시를 커머스API에 맞춰 변환시킨다.
     * @param array $formData
     * @return array $this->productInfoProvidedNotice
     */
    public function setProductInfoProvidedNotice($formData)
    {
        $pInfo = array();
        
        $pInfo['productInfoProvidedNoticeType'] = $this->convertGubunToType($formData['it_info_gubun']);

        $field = $this->convertTypeToField($pInfo['productInfoProvidedNoticeType']);

        /* 공통변수 */
        // $pInfo[$typeKey]['returnCostReason']          = '1';  // 제품하자/오배송에 따른 청약철회 조항)
        // $pInfo[$typeKey]['noRefundReason']            = '1';  // 제품하자가 아닌 소비자의 단순변심, 착오구매에 따른 청약철회가 불가능한 경우 그 구체적 사유와 근거
        // $pInfo[$typeKey]['qualityAssuranceStandard']  = '1';  // 재화 등의 교환ㆍ반품ㆍ보증 조건 및 품질 보증 기준
        // $pInfo[$typeKey]['compensationProcedure']     = '1';  // 대금을 환불받기 위한 방법과 환불이 지연될 경우 지연에 따른 배상금을 지급받을 수 있다는 사실 및 배상금 지급의 구체적 조건 및 절차
        // $pInfo[$typeKey]['troubleShootingContents']   = '1';  // 소비자피해보상의 처리, 재화 등에 대한 불만 처리 및 소비자와 사업자 사이의 분쟁 처리에 관한 사항

        /* 상품정보제공고시 타입별 입력필드 */
        $count_ii_article = (isset($formData['ii_article']) && is_array($formData['ii_article'])) ? count($formData['ii_article']) : 0;
        for ($i = 0; $i < $count_ii_article; $i++) {
            $articleKey = isset($formData['ii_article'][$i]) ? strip_tags($formData['ii_article'][$i], '<br><span><strong><b>') : '';
            $value      = isset($formData['ii_value'][$i]) ? strip_tags($formData['ii_value'][$i], '<br><span><strong><b>') : '';

            $convertKey = $this->convertArticleKeyToFieldKey($field, $articleKey);
            if ($convertKey != '') {
                $pInfo[$field][$convertKey] = $value;
            }
        }

        if (in_array($field, $this->containPackDateTypeFieldList)) {
            $pInfo[$field]['packDateType'] = 'DIRECT';
        }
        if (in_array($field, $this->containReleaseDateTypeFieldList)) {
            $pInfo[$field]['releaseDateType'] = 'DIRECT';
        }
        if (in_array($field, $this->containExpirationDateTypeFieldList)) {
            $pInfo[$field]['expirationDateType'] = 'DIRECT';
        }
        
        return $pInfo;
    }

    /**
     * lib/iteminfo.lib.php > article Key => 커머스API 각 필드별 키값으로 변경
     * @param string $field         
     * @param string $articleKey    iteminfo.lib.php > article > 상품정보 키
     */
    function convertArticleKeyToFieldKey($field, $articleKey)
    {
        return $this->convertKeyArray[$field][$articleKey];
    }

    /**
     * @param string $gubun 상품요약정보 > 상품군
     * @return string 스마트스토어 상품정보제공고시 타입
     */
    public function convertGubunToType($gubun)
    {
        global $item_info;

        return $item_info[$gubun]['productInfoProvidedNoticeType'];
    }

    /**
     * 
     * 상품정보제공고시 타입에서 타입별 입력필드 값으로 변환
     * 
     * @param string $type 커머스API 상품정보제공고시 타입
     * @return string 타입별 입력필드 키
     */
    public function convertTypeToField($type)
    {
        return lcfirst(str_replace('_', '', ucwords(strtolower($type), '_')));
    }
}
