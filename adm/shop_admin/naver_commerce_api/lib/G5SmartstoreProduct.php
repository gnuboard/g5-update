<?php

/**
 * 네이버 스마트스토어 상품 Class
 * @todo 남은 API 연동 진행 > https://apicenter.commerce.naver.com/ko/basic/commerce-api
 * 
 */
class G5SmartstoreProduct {

    /**
     * @var CommerceApi
     */
    public $commerceApi;

    /**
     * @var string 채널 상품 조회 URL
     */
    public $urlGetChannelProduct = "https://api.commerce.naver.com/external/v1/products/channel-products/";
    /**
     * @var string 상품 등록 URL
     */
    public static $urlCreateChannelProduct = "https://api.commerce.naver.com/external/v1/products";
    /**
     * @var string 상품 수정 URL
     */
    public static $urlUpdateChannelProduct = "https://api.commerce.naver.com/external/v1/products/channel-products/";
    /**
     * @var string 이미지 다건 업로드 URL
     */
    public static $urlUploadProductImage = "https://api.commerce.naver.com/external/v1/product-images/upload";

    /**
     * @param CommerceApiAuth $commerceApiAuth
     */
    public function __construct($commerceApiAuth)
    {
        $this->commerceApi = new CommerceApi($commerceApiAuth);
    }

    /**
     * 채널 상품 조회
     * @param integer<int64> $channelProductNo  채널상품번호 (스마트스토어 상품번호)
     */
    public function getChannelProduct($channelProductNo)
    {
        try {
            $channelProductNo = trim($channelProductNo);
            $resultData = $this->commerceApi->requestCurl("GET", $this->urlGetChannelProduct . $channelProductNo);
            if (isset($resultData->code)) {
                throw new Exception($resultData->message);
            }
            $resultData->originProduct->detailContent = $this->setHtmlContentJsonParse($resultData->originProduct->detailContent);

            return $resultData;

        } catch (Exception $e) {
            return $resultData;
        }
    }

    /**
     * Html 태그를 Json.parse 하도록 변환
     */
    public function setHtmlContentJsonParse($content)
    {
        return preg_replace('/\r\n|\r|\n/', '', str_replace("\"", "\\\"", $content));
    }

    /**
     * 상품 등록
     * @param array $formData 입력 폼 데이터 ($_POST)
     * @param array $fileData 업로드 이미지 ($_FILE)
     */
    public function createChannerProduct($formData, $fileData)
    {
        /* 상품데이터 입력 START */
        $productData = new G5SmartstoreProductData();
        $productData->setStatusType("SALE");
        $productData->setLeafCategoryId($formData['ss_category_id']);
        $productData->setName($formData['it_name']);
        $productData->setDetailContent($formData['it_explan']);
        $productData->setSalePrice($formData['it_price']);
        $productData->setStockQuantity($formData['it_stock_qty']);
        // 이미지
        if ($fileData) {
            $imageUrlList = $this->uploadProductImage($fileData);
            // echo "<br>==================================productData responseImage()==================================<br>";
            // print_r($imageUrlList);
            if (isset($imageUrlList->images)) {
                $productData->setImages($imageUrlList->images[0]->url, 'representative');
            }
        }
        // 배송정보
        $productData->setDeliveryInfo($formData);
        // 원상품 상세 속성
        $productData->setDetailAttribute($formData);
        /* 상품데이터 입력 END */
        // echo "<br>==================================productData getProductData()==================================<br>";
        // print_r($productData->getProductData());
        // print_r(json_encode($productData->getProductData()));
        $resultData = $this->commerceApi->requestCurl("POST", self::$urlCreateChannelProduct, json_encode($productData->getProductData()));
        // echo "<br>==================================productData createProduct()==================================<br>";
        // print_r($resultData);

        return $resultData;
    }

    /**
     * 상품 등록
     * @param int   $productNo 스마트스토어 채널상품 번호
     * @param array $formData 입력 폼 데이터 ($_POST)
     * @param array $fileData 업로드 이미지 ($_FILE)
     */
    public function updateChannerProduct($channelProductNo, $formData, $fileData)
    {
        /* 상품데이터 입력 START */
        $productData = new G5SmartstoreProductData();

        $channelProductData = $this->getChannelProduct($channelProductNo);
        // echo "<br>==================================productData formData()==================================<br>";
        // print_r($formData);
        $productData->setProductData(json_decode(json_encode($channelProductData), true));
        $productData->setLeafCategoryId($formData['ss_category_id']);
        $productData->setName($formData['it_name']);
        $productData->setDetailContent($formData['it_explan']);
        $productData->setSalePrice($formData['it_price']);
        $productData->setStockQuantity($formData['it_stock_qty']);
        // 이미지
        if ($fileData) {
            $imageUrlList = $this->uploadProductImage($fileData);
            // echo "<br>==================================productData responseImage()==================================<br>";
            // print_r($imageUrlList);
            if (isset($imageUrlList->images)) {
                $productData->setImages($imageUrlList->images[0]->url, 'representative');
            }
        }
        // 배송정보
        $productData->setDeliveryInfo($formData);
        // 원상품 상세 속성
        $productData->setDetailAttribute($formData);
        /* 상품데이터 입력 END */
        // echo "<br>==================================productData getProductData()==================================<br>";
        // print_r($productData->getProductData());
        $resultData = $this->commerceApi->requestCurl("PUT", self::$urlUpdateChannelProduct . $channelProductNo, json_encode($productData->getProductData(), JSON_FORCE_OBJECT));
        // echo "<br>==================================productData updateChannerProduct()==================================<br>";
        // print_r($resultData);

        return $resultData;
    }

    /**
     * 이미지 파일 CURL전송 처리 (1건만 등록 가능)
     * - PHP에서 중복 키를 설정하지 못해 대표 이미지만 업로드 가능. 또한 response도 중복된 Key값으로 리턴됨.
     * @param array $fileData   파일목록
     * @return mixed $response  요청결과
     * @todo PHP >= 5.5.0 제한이 있어 해결해야함. (이전버전 참고용 https://intrepidgeeks.com/tutorial/example-analysis-of-php50-56-compatible-curl-file-upload-function)
     */
    public function uploadProductImage($fileData)
    {
        $FileList = array();
        
        for ($i = 1; $i <= 10; $i++) {
            $tmp_name   = $fileData['it_img' . $i]['tmp_name'];
            $type       = $fileData['it_img' . $i]['type'];
            $filename   = $fileData['it_img' . $i]['name'];

            if (isset($tmp_name) && $tmp_name != '') {
                // PHP >= 5.5.0                
                $file = new CURLFILE($tmp_name, $type, $filename);
                $FileList["imageFiles"] = $file;
                break;
            }
        }
        $resultData = $this->commerceApi->requestCurl("POST", self::$urlUploadProductImage, $FileList);
        /* 이미지 다중 업로드 불가능(중복 키), response 데이터 또한 중복키 값으로 반환됨.
            response 예시
            {
            "images": [
                {"url": "http://shop1.phinf.naver.net/20220930_128/1664524253387s9sOS_GIF/10364219216524991_361910498.gif"},
                {"url": "http://shop1.phinf.naver.net/20220930_19/1664524272317dbS6F_GIF/10364240140268719_1069442997.gif"}
            ]
            }
        */
        return $resultData;
    }
}
