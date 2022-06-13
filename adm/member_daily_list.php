<?php
$sub_menu = "200400";
include_once('./_common.php');

auth_check($auth[$sub_menu], 'r');

$total_members = 0;

function g5_member_month_stats($month, $day, $is_accrue=false, $return_array=false){
    global $g5, $total_members;
    
    if( ! $month || ! $day ){
        return '잘못된전달';
    }

    if( $month < 10 ){
        $month = '0'.$month;
    }
    if( $day < 10 ){
        $day = '0'.$day;
    }

    static $datas = array();

    $year = date('Y', strtotime('-1 year', G5_SERVER_TIME));    // 전년도 10월 11월 12월
    $yymmdd = $year.'-'.$month.'-'.$day;
    $array_key = str_replace('-', '', $yymmdd);
    
    if( !$datas ){

        $begin_yymmdd = $year.'-10-01';
        $begin = new DateTime( $begin_yymmdd );
        $end   = new DateTime( $year.'-12-31' );
        
        // 그누보드5 회원수
        $sql = "select count(*) as total from `{$g5['member_table']}` where mb_leave_date = '' and mb_datetime < '".$begin_yymmdd."' ";
        $tmp = sql_fetch($sql);
        $total = $tmp['total'];

        // 영카트5 비회원 주문수
        if (isset($g5['g5_shop_order_table']) && $g5['g5_shop_order_table']) {
            $sql = "select count(distinct od_b_hp) as total from `{$g5['g5_shop_order_table']}` where mb_id = '' and od_b_hp != '' and od_time < '".$begin_yymmdd."' ";
            $tmp = sql_fetch($sql);
            $shop_total = (int) $tmp['total'];  // 쇼핑몰 숫자

            $total += $shop_total;
        }

        for($i = $begin; $i <= $end; $i->modify('+1 day')){
            $key = $i->format("Y-m-d");
            $yymmdd_key = str_replace('-', '', $key);

            $sql = "select sql_no_cache count(*) as total from `{$g5['member_table']}` where mb_leave_date = '' and mb_datetime BETWEEN '".$key." 00:00:00' AND '".$key." 23:59:59' ";

            $days = sql_fetch($sql);
            
            // 영카트5 비회원 주문수
            if (isset($g5['g5_shop_order_table']) && $g5['g5_shop_order_table']) {
                $sql = "select count(distinct od_b_hp) as total from `{$g5['g5_shop_order_table']}` where mb_id = '' and od_b_hp != '' and od_time BETWEEN '".$key." 00:00:00' AND '".$key." 23:59:59' ";
                $tmp = sql_fetch($sql);
                $shop_days = (int) $tmp['total'];  // 쇼핑몰 숫자

                $days['total'] += $shop_days;
            }

            $datas[$yymmdd_key][0] = $days['total'];
            $datas[$yymmdd_key][1] = $total = $total + $days['total'];
        }
        
        $total_members = $total;
    }
    
    if( $return_array ){
        return $datas;
    }

    if( isset($datas[$array_key]) ){
        if( $is_accrue ){
            return (isset($datas[$array_key][1]) && $datas[$array_key][1]) ? number_format($datas[$array_key][1]) : 0;
        }
        return (isset($datas[$array_key][0]) && $datas[$array_key][0]) ? number_format($datas[$array_key][0]) : 0;
    }

    return '';
}

function g5_member_all_status($datas){

    if ( $datas ){

        $sum = 0;
        foreach($datas as $data){
            if( isset($data[1]) && $data[1] ){
                $sum += (int) $data[1];
            }
        }

        return $sum;
    }
    
    return 0;
}

$sum_datas = g5_member_all_status(g5_member_month_stats('10', 1, false, true));

$g5['title'] = '일일평균 이용자수 확인';
include_once('./admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>
        개인정보보호배상책임보험(Ⅱ) 가입을 위한 참고자료입니다.
    </p>
</div>

<div id="mb_daily_list">

    <div class="tbl_wrap tbl_head01 daily_table">
        <div class="title">
            자동계산 데이터
            <a href="https://sir.kr/insurance" target="_blank" class="btn btn_03">보험가입안내</a>
        </div>

        <div class="tbl_unit">(단위 : 명)</div>

        <table>
            <thead>
                <tr>
                    <th scope="col" rowspan="2">구분(일)</th>
                    <th scope="col" colspan="2">10월</th>
                    <th scope="col" colspan="2">11월</th>
                    <th scope="col" colspan="2">12월</th>
                </tr>
                <tr>
                    <th scope="col" class="sub">신규</th>
                    <th scope="col" class="sub">누적</th>
                    <th scope="col" class="sub">신규</th>
                    <th scope="col" class="sub">누적</th>
                    <th scope="col" class="sub">신규</th>
                    <th scope="col" class="sub">누적</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $total_members ){ ?>
                <!-- 10월 1일은 1월 1일부터 10월 1일까지 누적된 이용자 정보를 신규와 누적에 똑같이 표시합니다. -->
                <?php for ($i=1;$i<32;$i++) { ?>
                <tr>
                    <th scope="row"><?php echo $i; ?></th>
                    <td class="td_num_c2"><?php echo g5_member_month_stats('10', $i); ?></td>
                    <td class="td_num_c3"><?php echo g5_member_month_stats('10', $i, true); ?></td>
                    <td class="td_num_c2"><?php echo g5_member_month_stats('11', $i); ?></td>
                    <td class="td_num_c3"><?php echo g5_member_month_stats('11', $i, true); ?></td>
                    <td class="td_num_c2"><?php echo g5_member_month_stats('12', $i); ?></td>
                    <td class="td_num_c3"><?php echo g5_member_month_stats('12', $i, true); ?></td>
                </tr>
                <?php } ?>
                <?php } else { ?>
                <tr>
                    <td colspan="7" class="empty_table">전년도 대상 자료가 없습니다.</td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="row" colspan="5">일일평균 이용자수</th>
                    <td colspan="2">누적합계</td>
                </tr>
                <tr>
                    <th scope="row" colspan="5"><?php echo number_format(round($sum_datas / 92, 2)); ?></th>
                    <td colspan="2"><?php echo number_format($sum_datas); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="daily_desc">

        <div class="title">참고사항</div>

        <div class="sub_title">자동계산 기준 Database Table</div>

        <div class="tbl_wrap tbl_head01">
            <table>
                <thead>
                    <tr>
                        <th scope="col">구분</th>
                        <th scope="col">Table</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($g5['member_table']) { ?>
                    <tr>
                        <th scope="row">회원정보</th>
                        <td><?php echo $g5['member_table']; ?></td>
                    </tr>
                    <?php } ?>
                    <?php if (isset($g5['g5_shop_order_table']) && $g5['g5_shop_order_table']) { ?>
                    <tr>
                        <th scope="row">비회원 주문정보</th>
                        <td><?php echo $g5['g5_shop_order_table']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <ul class="ul_rollback">
            <li>
                아래 수치는 별도 합산하셔야 합니다.
                <ul class="ul_rollback">
                    <li>
                        별도 Table 에 저장, 보관되는 개인정보
                    </li>
                    <li>
                        오프라인에 저장, 보관되는 개인정보
                    </li>
                    <li>
                        이메일, 휴대폰번호 등 개인 식별 가능한 정보를 포함한 비회원 게시물
                    </li>
                </ul>
            </li>
            <li>
                일일평균 이용자수 계산 방법
                <ul class="ul_rollback">
                    <li>전년도 10월 1일부터 12월 31일까지 일별 <b>누계</b> 고객을 더하여 92로 나눕니다.</li>
                    <li>이 때, 10월 1일 이용자수는 1월 1일 ~ 10월 1일까지 이용자수 합입니다.</li>
                </ul>
            </li>
        </ul>

        <div class="sub_title">최저가입금액 기준</div>

        <div class="tbl_wrap tbl_head01">
            <table>
                <thead>
                    <tr>
                        <th scope="row" colspan="2">적용대상 사업자 가입금액 산정요소</th>
                        <th scope="row" rowspan="2">최저가입금액<br>(최소적립금액)</th>
                    </tr>
                    <tr>
                        <th scope="row">이용자수</th>
                        <th scope="row">매출액</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th rowspan="3">100만명 이상</th>
                        <td>800억원 초과</td>
                        <td>10억원</td>
                    </tr>
                    <tr>
                        <td>50억원 초과 800억원 이하</td>
                        <td>5억원</td>
                    </tr>
                    <tr>
                        <td>5천만원 이상 50억원 이하</td>
                        <td>2억원</td>
                    </tr>
                    <tr>
                        <th rowspan="3">10만명 이상 100만명 미만</th>
                        <td>800억원 초과</td>
                        <td>5억원</td>
                    </tr>
                    <tr>
                        <td>50억원 초과 800억원 이하</td>
                        <td>2억원</td>
                    </tr>
                    <tr>
                        <td>5천만원 이상 50억원 이하</td>
                        <td>1억원</td>
                    </tr>
                    <tr>
                        <th rowspan="3">1천명 이상 10만명 미만</th>
                        <td>800억원 초과</td>
                        <td>2억원</td>
                    </tr>
                    <tr>
                        <td>50억원 초과 800억원 이하</td>
                        <td>1억원</td>
                    </tr>
                    <tr>
                        <td>5천만원 이상 50억원 이하</td>
                        <td>5천만원</td>
                    </tr>
                </tbody>
            </table>
            <div class="tbl_unit">(2020년 2월 기준 작성)</div>
        </div>

        <ul class="ul_rollback">
            <li>
                <b>이용자수</b>
                <ul class="ul_rollback">
                    <li>사업자가 개인정보를 저장, 보관하는 이용자수 기준 (휴면, 탈퇴, 비회원도 개인정보가 저장, 보관된다면 모두 포함)</li>
                    <li>일일방문자수를 의미하지 않으며, 페이지뷰(PV:page view), 순방문자수(UV:unique view)와는 무관함</li>
                </ul>
            </li>
            <li>
                <b>매출액</b>
                <ul class="ul_rollback">
                    <li>법인(기업)의 총 매출액을 의미하며, 손익계산서 상 총 매출액임</li>
                </ul>
            </li>
        </ul>

        <p class="desc">
            SIR에서 보험료 계산해보시고 간편 가입신청해보세요. <a href="https://sir.kr/insurance" target="_blank" class="a">보험가입안내</a>
        </p>

    </div>

</div>

<?php
include_once('./admin.tail.php');
?>