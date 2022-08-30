<?php
if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
?>
<input type="hidden" name="settle_method" id="settle_method" value="">
<?php if ($default['de_tax_flag_use']) { ?>
<input type="hidden" name="comm_tax_mny"      value="<?php echo $comm_tax_mny; ?>">         <!-- 과세금액    -->
<input type="hidden" name="comm_vat_mny"      value="<?php echo $comm_vat_mny; ?>">         <!-- 부가세     -->
<input type="hidden" name="comm_free_mny"     value="<?php echo $comm_free_mny; ?>">        <!-- 비과세 금액 -->
<?php } ?>