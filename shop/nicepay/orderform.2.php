<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>
<input type="hidden" name="PayMethod" value="">
<input type="hidden" name="GoodsName" value="<?php echo $goods; ?>">
<?php /* 주문폼 자바스크립트 에러 방지를 위해 추가함 */ ?>
<input type="hidden" name="good_mny"    value="<?php echo $tot_price; ?>">
<input type="hidden" name="Amt" value="<?php echo $tot_price; ?>">
<input type="hidden" name="MID" value="<?php echo $default['de_nicepay_mid']; ?>">
<input type="hidden" name="Moid" value="<?php echo $od_id; ?>">
<input type="hidden" name="BuyerName" value="">
<input type="hidden" name="BuyerEmail" value="">
<input type="hidden" name="BuyerTel" value="">
<input type="hidden" name="ReturnURL" value="<?php echo $nicepay_returnURL; ?>">
<input type="hidden" name="VbankExpDate" value="">
<input type="hidden" name="NpLang" value="KO"/> <!-- EN:English, CN:Chinese, KO:Korean -->
<input type="hidden" name="GoodsCl" value="1"/>	<!-- products(1), contents(0)) -->
<input type="hidden" name="TransType" value="0"/>	<!-- USE escrow false(0)/true(1) --> 
<input type="hidden" name="CharSet" value="utf-8"/>	<!-- Return CharSet -->
<input type="hidden" name="ReqReserved" value=""/>	<!-- mall custom field -->
<input type="hidden" name="EdiDate" value=""/> <!-- YYYYMMDDHHMISS -->
<input type="hidden" name="SignData" value=""/>	<!-- EncryptData -->
<?php if($default['de_tax_flag_use']) { ?>
<input type="hidden" name="tax"         value="<?php echo $comm_vat_mny; ?>">
<input type="hidden" name="taxfree"     value="<?php echo $comm_free_mny; ?>">
<?php }