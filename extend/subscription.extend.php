<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 게시판 진입 시 check
add_event('header_board', 'checkRoute', 10, 1);

/**
 * 구독권한 체크 
 * - 게시판 코드(bo_table)가 등록된 구독서비스의 결제내역이 있는지 체크
 * @todo URL or Hook Code로 체크하는 프로세스
 * @return void
 */
function checkRoute()
{
    global $g5, $member;

    require_once G5_LIB_PATH . '/billing/_setting.php';

    $check      = false;
    $bo_table   = $_GET['bo_table'];
    $service_model      = new BillingServiceModel();
    $information_model  = new BillingInformationModel();

    // 게시판이 설정된 서비스 목록 조회
    $request_data = array(
        "is_use" => 1,
        "service_table" => $bo_table
    );
    $service_list = $service_model->selectList($request_data);

    if (count($service_list) > 0) {
        if (empty($member['mb_id'])) {
            alert('로그인 후 이용하실 수 있습니다.', G5_URL . '/bbs/login.php');
        }

        foreach ($service_list as $service) {
            if ($information_model->checkPermission($member['mb_id'], $service['service_id'])) {
                $check = true;
                break;
            }
        }

        if ($check === false) {
            alert('게시판을 열람하려면 게시판 구독신청이 필요합니다.\n구독 서비스 목록으로 이동합니다.', G5_URL . '/bbs/subscription/view.php?bo_table=' . $bo_table);
        }
    }
}
