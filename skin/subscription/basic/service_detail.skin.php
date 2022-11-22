<?php

//if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once('../../../common.php');
require_once('../../../head.php');
require_once('../../../head.sub.php');

require_once(G5_BBS_PATH . '/subscription/subscription_service.php'); //TODO subscription head

require_once G5_PATH . '/bbs/kcp-batch/KcpBatch.php';

$sql = "SELECT * FROM {$g5['batch_service_table']} WHERE service_id = '{$service_id}'";
$service = sql_fetch($sql);
?>
<button type="button" class="btn_frmline btn_payment" data-service_id="6">구매</button>
<?php echo html_purifier(str_replace('\"', '', $service['service_explan'])); ?>
<script>
$('.btn_payment').on('click', function(){
    location.href = '<?php echo G5_BBS_URL ?>/regular_payment.php?service_id=' + $(this).data('service_id');
})
</script>
<?php
include_once(G5_PATH . '/tail.php');