<?php
$sub_menu = '400999';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, "r");

$g5['title'] = '네이버 스마트스토어 상품조회';
include_once G5_PATH . '/head.sub.php';

$productNo = isset($productNo) ? clean_xss_tags($_GET['productNo']) : '';
$product = null;
if ($productNo) {
    include_once G5_ADMIN_PATH . '/shop_admin/naver_commerce_api/config.php';
    include_once G5_ADMIN_PATH . '/shop_admin/naver_commerce_api/lib/CommerceApiAutoLoader.php';
    $autoloader = new CommerceApiAutoLoader();
    $autoloader->register();
    
    $commerceApiAuth = new CommerceApiAuth(G5_COMMERCE_API_CRIENT_ID, G5_COMMERCE_API_SECRET, new SignatureGeneratorSimple());
    $productInstance = new G5SmartstoreProduct($commerceApiAuth);
    $product = $productInstance->getChannelProduct($productNo);
}
echo "<br> 상품 번호 샘플 : 7324377112 / 7246247899 / 7246247813 <br>";
?>
<div class="new_win">
    <h1><?php echo $g5['title']; ?></h1>

    <form name="form_search_product" class="local_sch01 local_sch">
        <label for="sel_field" class="sound_only">검색대상</label>
        <select name="sel_field" id="sel_field">
            <option value="channelProductNo" selected="selected">상품ID</option>
        </select>
        <label for="search" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
        <input type="text" name="productNo" id="productNo" value="<?php echo $productNo; ?>" required="" class="required frm_input" autocomplete="off">
        <input type="submit" value="검색" class="btn_submit">
    </form>

    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php echo $g5['title']; ?> 입력</caption>
        <thead>
        <tr>
            <th scope="col" width="15%;">제품 이미지</th>
            <th scope="col" width="35%;">상품명</th>
            <th scope="col" width="20%;">가격</th>
            <th scope="col" width="20%;">재고</th>
            <th scope="col" width="10%;"></th>
        </tr>
        </thead>
        <tbody>
        <?php
            if (isset($product->originProduct)) {
                // 도메인이 포함된 데이터가 없으므로 주석처리
                $productLink = "javascript:;"; // $domain + "/products/" . $productNo;
        ?>
        <tr>
            <td>
                <!-- <a href="<?php echo $productLink; ?>" target="_blank"> -->
                    <img src="<?php echo $product->originProduct->images->representativeImage->url ?>" style="max-width:100px;">
                <!-- </a> -->
            </td>
            <td>
                <!-- <a href="<?php echo $productLink; ?>" target="_blank"> -->
                    <?php echo $product->originProduct->name ?>
                <!-- </a> -->
            </td>
            <td><?php echo number_format($product->originProduct->salePrice) . "원"; ?></td>
            <td><?php echo number_format($product->originProduct->stockQuantity) . "개"; ?></td>
            <td class="td_mngsmall"><button type="button" id="btn_search_smartstore" class="btn_frmline" value="">적용</button></td>
        <tr>
        <?php
        } elseif (isset($product->code)) {
            echo '<tr><td colspan="5">' . $product->message . '</td></tr>';
        }
        ?>
        </tbody>
        </table>
    </div>

    <div class="btn_win01 btn_win">
        <button type="button" onclick="javascript:window.close()">창 닫기</button>
    </div>
</div>
<script>
$(function(){
    $("#btn_search_smartstore").on("click", function(){
        const productNo     = '<?php echo trim($productNo) ?>';
        let productInfoJson = '<?php echo json_encode($product, JSON_FORCE_OBJECT) ?>';
        let productInfo     = JSON.parse(productInfoJson);
        let orgProduct      = productInfo.originProduct;
        // console.log(productInfoJson);
        // console.log(productInfo);
        console.log(orgProduct);
        
        /* 기본 상품데이터 */
        opener.document.getElementById("smartstore_product_name").innerHTML = orgProduct.name + " (" + productNo + ")";
        opener.document.getElementById("ss_channel_product_no").value       = productNo;
        opener.document.getElementById("it_name").value                     = orgProduct.name;
        opener.document.getElementById("it_explan").value                   = orgProduct.detailContent;
        opener.document.getElementById("it_mobile_explan").value            = orgProduct.detailContent;
        opener.document.getElementById("it_price").value                    = orgProduct.salePrice;
        opener.document.getElementById("it_stock_qty").value                = orgProduct.stockQuantity;
        // 카테고리
        // opener.document.getElementById("").value = orgProduct.leafCategoryId
        // 대표이미지 => 현재 영카트에서는 파일업로드 방식만 사용가능함
        // opener.document.getElementById("").value = orgProduct.images.representativeImage.url
        // 수정된 내용 에디터 적용
        opener.oEditors.getById["it_explan"].exec("LOAD_CONTENTS_FIELD");
        opener.oEditors.getById["it_mobile_explan"].exec("LOAD_CONTENTS_FIELD");

        /* 네이버쇼핑 검색 정보*/
        let searchInfo = orgProduct.detailAttribute.naverShoppingSearchInfo;
        opener.document.getElementById("it_maker").value = searchInfo.manufacturerName ?? "";
        opener.document.getElementById("it_brand").value = searchInfo.brandName ?? "";
        opener.document.getElementById("it_model").value = searchInfo.modelName ?? "";

        /* 원산지 */
        let originAreaInfo = orgProduct.detailAttribute.originAreaInfo;
        opener.document.getElementById("it_origin").value = originAreaInfo.content ?? "";

        /* 상품과세 유형 */
        if (orgProduct.detailAttribute.taxType == "TAX") {
            selectOpenerSelectBox("it_notax", 0);
        } else if (orgProduct.detailAttribute.taxType == "DUTYFREE") {
            selectOpenerSelectBox("it_notax", 1);
        }

        /* 구매수량 */
        let quantityInfo = orgProduct.detailAttribute.purchaseQuantityInfo;
        opener.document.getElementById("it_buy_min_qty").value = quantityInfo?.minPurchaseQuantity ?? 0;
        opener.document.getElementById("it_buy_max_qty").value = quantityInfo?.maxPurchaseQuantityPerOrder ?? 0;
        // 1인 구매제한
        // quantityInfo.maxPurchaseQuantityPerId

        /* 배송비 */
        let deliveryFee = orgProduct.deliveryInfo.deliveryFee;
        let deliveryFeeType = deliveryFee.deliveryFeeType;
        let deliveryFeePayType = deliveryFee.deliveryFeePayType;

        if (deliveryFeeType == "FREE") {
            selectOpenerSelectBox("it_sc_type", 1);
        } else if (deliveryFeeType == "CONDITIONAL_FREE") {
            selectOpenerSelectBox("it_sc_type", 2);
            selectOpenerDeliveryMethod(deliveryFeePayType);
            opener.document.getElementById("it_sc_price").value = deliveryFee.baseFee;
            opener.document.getElementById("it_sc_minimum").value = deliveryFee.freeConditionalAmount;
        } else if (deliveryFeeType == "PAID") {
            selectOpenerSelectBox("it_sc_type", 3);
            selectOpenerDeliveryMethod(deliveryFeePayType);
            opener.document.getElementById("it_sc_price").value = deliveryFee.baseFee;
        } else if (deliveryFeeType == "UNIT_QUANTITY_PAID") {
            selectOpenerSelectBox("it_sc_type", 4)
            selectOpenerDeliveryMethod(deliveryFeePayType);
            opener.document.getElementById("it_sc_price").value = deliveryFee.baseFee;
            opener.document.getElementById("it_sc_qty").value = deliveryFee.freeConditionalAmount;
        }

        /* 상품 옵션 */
        // 조합형/단독형 둘 중 하나만 실행됨.
        // 재고 및 가격은 가져오지 못함.. (stockQuantity, price) => 데이터를 맞출려면 시간이 좀 더 소요될 듯함
        let optionInfo      = orgProduct.detailAttribute.optionInfo;
        let optionArray     = {};

        // 조합형
        let optionCombinationsLength = Object.keys(optionInfo.optionCombinations).length
        if (optionCombinationsLength !== 0) {
            
            for (let i = 1; i <= 3; i++) {
                if (optionInfo.optionCombinationGroupNames['optionGroupName' + i] != null) {
                    optionArray[i] = {};
                    optionArray[i].name = optionInfo.optionCombinationGroupNames['optionGroupName' + i];
                    optionArray[i].value = [];
                }
            }
            let optionArrayLenth = Object.keys(optionArray).length;
            for (let i = 0; i < optionCombinationsLength; i++) {
                for (let j = 1; j <= optionArrayLenth; j++) {
                    if (!optionArray[j].value.includes(optionInfo.optionCombinations[i]['optionName' + j])) {
                        optionArray[j].value.push(optionInfo.optionCombinations[i]['optionName' + j]);
                    }
                }
            }
        }
        // 단독형
        let optionSimpleLength = Object.keys(optionInfo.optionSimple).length
        if (optionSimpleLength !== 0) {
            let groupName = [];
            let value = {};
            let index = 0;
            for (let i = 0; i < optionSimpleLength; i++) {
                if (!groupName.includes(optionInfo.optionSimple[i].groupName)){
                    groupName.push(optionInfo.optionSimple[i].groupName);
                    (i == 0) ? "" : index++
                    value[index] = [];
                }
                value[index].push(optionInfo.optionSimple[i].name);
            }
            for (let key in groupName) {
                let index = parseInt(key) + 1;
                optionArray[index] = {};
                optionArray[index].name = groupName[key];
                optionArray[index].value = value[key];
            }
        }
        setOptionElements(optionArray);

        /* 추가상품 */
        let supplementArray = {};
        let supplementProducts = orgProduct.detailAttribute.supplementProductInfo.supplementProducts;
        let supplementProductsLength = Object.keys(supplementProducts).length
        if (supplementProductsLength !== 0) {
            let groupName = [];
            let value = {};
            let index = 0;
            for (let i = 0; i < supplementProductsLength; i++) {
                if (!groupName.includes(supplementProducts[i].groupName)){
                    groupName.push(supplementProducts[i].groupName);
                    (i == 0) ? "" : index++
                    value[index] = [];
                }
                value[index].push(supplementProducts[i].name);
            }
            for (let key in groupName) {
                let index = parseInt(key) + 1;
                supplementArray[index] = {};
                supplementArray[index].name = groupName[key];
                supplementArray[index].value = value[key];
            }
        }
        setSupplementElements(supplementArray);

        /**
         * 배송비 결제 선택
         */
        function selectOpenerDeliveryMethod(method)
        {
            // 착불
            if (method == "COLLECT") {
                selectOpenerSelectBox("it_sc_method", 1);
            // 선결제
            } else if (method == "PREPAID") {
                selectOpenerSelectBox("it_sc_method", 0);
            // 착불 또는 선결제
            } else if (method == "COLLECT_OR_PREPAID") {
                selectOpenerSelectBox("it_sc_method", 2);
            }
        }
        /**
         * value값으로 부모 selectbox 선택
         */
        function selectOpenerSelectBox(elementId, value)
        {
            let element = opener.document.getElementById(elementId);

            for (let i = 0; i < element.length; i++) {
                if (element[i].value == value) {
                    element[i].selected = true;
                     // change Event 발생 : 기존의 스크립트를 사용하기 위함
                    element.dispatchEvent(new Event("change"));
                    break;
                }
            }
        }

        /**
         * 선택옵션 폼 생성
         */
        function setOptionElements(optionArray)
        {
            let $option_table           = $(opener.document).find("#sit_option_frm");
            let $option_table_create    = opener.document.getElementById("option_table_create");

            // 옵션 폼 데이터 추가
            opener.document.getElementById("opt1_subject").value    = optionArray[1]?.name ?? "";
            opener.document.getElementById("opt2_subject").value    = optionArray[2]?.name ?? "";
            opener.document.getElementById("opt3_subject").value    = optionArray[3]?.name ?? "";
            opener.document.getElementById("opt1").value            = optionArray[1]?.value.join(",") ?? "";
            opener.document.getElementById("opt2").value            = optionArray[2]?.value.join(",") ?? "";
            opener.document.getElementById("opt3").value            = optionArray[3]?.value.join(",") ?? "";

            // 폼 생성 버튼 클릭
            $option_table_create.dispatchEvent(new Event("click"));
            /*
            let itemOptionArray = { 
                it_id : "",
                w : "",
                opt1_subject : optionArray[1]?.name,
                opt2_subject : optionArray[2]?.name,
                opt3_subject : optionArray[3]?.name,
                opt1 : optionArray[1]?.value.join(","),
                opt2 : optionArray[2]?.value.join(","),
                opt3 : optionArray[3]?.value.join(",")
            }
            $.post(
                "<?php echo G5_ADMIN_URL; ?>/shop_admin/itemoption.php",
                itemOptionArray, 
                function(data) {
                    $option_table.empty().html(data);
                }
            );
            */
        }

        /**
         * 선택옵션 폼 삭제
         */
        function removeOptionElements(element)
        {
            opener.document.getElementById("opt1_subject").value    = "";
            opener.document.getElementById("opt2_subject").value    = "";
            opener.document.getElementById("opt3_subject").value    = "";
            opener.document.getElementById("opt1").value            = "";
            opener.document.getElementById("opt2").value            = "";
            opener.document.getElementById("opt3").value            = "";

            element.empty();
        }

        /**
         * 추가옵션 폼 생성
         */
        function setSupplementElements(supplementArray)
        {
            let $supply_table           = $(opener.document).find("#sit_option_addfrm");
            let $add_supply_row         = opener.document.getElementById("add_supply_row");
            let $supply_table_create    = opener.document.getElementById("supply_table_create");

            for (let key in supplementArray) {
                $add_supply_row.dispatchEvent(new Event("click"));
                console.log(key, supplementArray[key]);

                // 폼 데이터 추가
                opener.document.getElementById("spl_subject_" + key).value  = supplementArray[key]?.name;
                opener.document.getElementById("spl_item_" + key).value     = supplementArray[key]?.value.join(",") ?? "";
            }
            // 폼 생성 버튼 클릭
            $supply_table_create.dispatchEvent(new Event("click"));
        }

        window.close();
    })
})
</script>
<?php
include_once(G5_PATH.'/tail.sub.php');
