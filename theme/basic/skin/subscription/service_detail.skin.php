<?php

if (!defined("_GNUBOARD_")) exit; // 개별 페이지 접근 불가
require_once dirname(__FILE__) . '/service_head.skin.php';

$service_id = isset($service_id) ? clean_xss_tags($service_id) : '';  // 구독 서비스 ID
if(empty($service_id)) {
    alert('해당 서비스가 없습니다.');
}
$service_info = get_service_detail($service_id);

if(empty($service_info)){
    alert('해당 서비스가 없습니다.');
}

$convertYMDUnit = array('y' => '년', 'm' => '월', 'w' => '주', 'd' => '일');
$convertYMDUnit2 = array('y' => '년', 'm' => '개월', 'w' => '주', 'd' => '일');

?>
    <div class="item_box remove_border">
        <ul>
            <li class="title_area">
                <div class="service_name"><?php echo  $service_info['name'] ?></div>
                <div class="service_summary"><?php echo  $service_info['summary'] ?></div>
            </li>
            <li class="price_area">
                <div class="price"><?php echo $convertYMDUnit[$service_info['recurring_unit']] ?> <?php echo  number_format($service_info['price']) ?>원 (부가세 포함)</div>
                <div><?php echo $service_info['recurring']?><?php echo $convertYMDUnit2[$service_info['recurring_unit']] ?> 마다 정기 결제</div>
            </li>
            <li class="button_area">
                <button type="button" class="btn_frmline btn_payment" data-service_id=<?php echo $service_info['service_id']?>>구독</button>
            </li>
        </ul>
    </div>

    <h2>서비스 설명</h2>
    <div class="section">

        <div>
            <?php echo $service_info['explan'] ?>
        </div>
    </div>
    <div>
        서비스 이미지영역
        <?php
        if(empty($service_info)) {
            echo "<img src='{$service_info['service_image']}' class='service_info_image'>";
        }
        ?>
    </div>
    <script>
        $('.btn_payment').on('click', function(){
            location.href = '<?php echo G5_BBS_URL ?>/regular_payment.php?service_id=' + $(this).data('service_id');
        })
    </script>
<?php
include_once(G5_PATH . '/tail.sub.php');