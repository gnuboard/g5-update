<?php
$sub_menu = '500720';
include_once('./_common.php');
include_once(G5_LIB_PATH.'/xml.lib.php');

check_demo();

auth_check($auth[$sub_menu], "r");

function yc5_get_item_image_url($it_id){
    return G5_DATA_URL.'/item/';
}

if( isset($_POST['action']) && 'export_data' === $_POST['action'] ){

    $items = array();

    $de_keys = array(
        'de_type1_list_use',
        'de_type1_list_skin',
        'de_type1_list_mod',
        'de_type1_list_row',
        'de_type1_img_width',
        'de_type1_img_height',
        'de_type2_list_use',
        'de_type2_list_skin',
        'de_type2_list_mod',
        'de_type2_list_row',
        'de_type2_img_width',
        'de_type2_img_height',
        'de_type3_list_use',
        'de_type3_list_skin',
        'de_type3_list_mod',
        'de_type3_list_row',
        'de_type3_img_width',
        'de_type3_img_height',
        'de_type4_list_use',
        'de_type4_list_skin',
        'de_type4_list_mod',
        'de_type4_list_row',
        'de_type4_img_width',
        'de_type4_img_height',
        'de_type5_list_use',
        'de_type5_list_skin',
        'de_type5_list_mod',
        'de_type5_list_row',
        'de_type5_img_width',
        'de_type5_img_height',
        'de_mobile_type1_list_use',
        'de_mobile_type1_list_skin',
        'de_mobile_type1_list_mod',
        'de_mobile_type1_list_row',
        'de_mobile_type1_img_width',
        'de_mobile_type1_img_height',
        'de_mobile_type2_list_use',
        'de_mobile_type2_list_skin',
        'de_mobile_type2_list_mod',
        'de_mobile_type2_list_row',
        'de_mobile_type2_img_width',
        'de_mobile_type2_img_height',
        'de_mobile_type3_list_use',
        'de_mobile_type3_list_skin',
        'de_mobile_type3_list_mod',
        'de_mobile_type3_list_row',
        'de_mobile_type3_img_width',
        'de_mobile_type3_img_height',
        'de_mobile_type4_list_use',
        'de_mobile_type4_list_skin',
        'de_mobile_type4_list_mod',
        'de_mobile_type4_list_row',
        'de_mobile_type4_img_width',
        'de_mobile_type4_img_height',
        'de_mobile_type5_list_use',
        'de_mobile_type5_list_skin',
        'de_mobile_type5_list_mod',
        'de_mobile_type5_list_row',
        'de_mobile_type5_img_width',
        'de_mobile_type5_img_height',
        'de_rel_list_skin',
        'de_rel_img_width',
        'de_rel_img_height',
        'de_rel_list_mod',
        'de_rel_list_use',
        'de_mobile_rel_list_skin',
        'de_mobile_rel_img_width',
        'de_mobile_rel_img_height',
        'de_mobile_rel_list_mod',
        'de_mobile_rel_list_use',
        'de_search_list_skin',
        'de_search_img_width',
        'de_search_img_height',
        'de_search_list_mod',
        'de_search_list_row',
        'de_mobile_search_list_skin',
        'de_mobile_search_img_width',
        'de_mobile_search_img_height',
        'de_mobile_search_list_mod',
        'de_mobile_search_list_row',
        'de_listtype_list_skin',
        'de_listtype_img_width',
        'de_listtype_img_height',
        'de_listtype_list_mod',
        'de_listtype_list_row',
        'de_mobile_listtype_list_skin',
        'de_mobile_listtype_img_width',
        'de_mobile_listtype_img_height',
        'de_mobile_listtype_list_mod',
        'de_mobile_listtype_list_row',
        'de_simg_width',
        'de_simg_height',
        'de_mimg_width',
        'de_mimg_height',
        );
    
    foreach( $de_keys as $key ){

        if( !empty( $default[$key] ) ){
            $items['default'][$key] = $default[$key];
        }

    }

    $sql = "select * from `{$g5['g5_shop_banner_table']}` ";
    
    $result = sql_query($sql);

    for($i=0; $banner=sql_fetch_array($result); $i++) {
        
        foreach($banner as $k=>$v){
            $items['banners']['banner'.$i][$k] = $v;
        }
    }

    if( isset($items['banners']) ){
        $items['banners']['url_path'] = G5_DATA_URL.'/banner/';
    }

    $sql = "select * from `{$g5['g5_shop_category_table']}` ";
    
    $result = sql_query($sql);

    for($i=0; $cate=sql_fetch_array($result); $i++) {
        
        foreach($cate as $k=>$v){
            $items['categories']['cate'.$i][$k] = $v;
        }
    }

    $sql = " select * from `{$g5['g5_shop_item_table']}` ";

    $item_result = sql_query($sql);

    for($i=0; $item=sql_fetch_array($item_result); $i++) {
        $it_id = $item['it_id'];
        
        $items['items']['item'.$i]['@attributes'] = array('it_id' => $it_id);
        foreach($item as $k=>$v){

            if( preg_match('/^it_img/i', $k) ){
                $v = $v ? yc5_get_item_image_url($it_id).$v : '';
            }

            $items['items']['item'.$i][$k] = $v;
        }

        $sql = "select * from `{$g5['g5_shop_item_option_table']}` where it_id = ".$it_id." order by io_no asc ";
        $op_result = sql_query($sql);
        
        for($j=0; $option=sql_fetch_array($op_result); $j++) {
            
            // https://www.oxygenxml.com/dita/styleguide/webhelp-feedback/Artefact/Syntax_and_Markup/c_Non_Breaking_Spaces.html
            //반드시 chr30문자를 &#x2011로 replace 해야 한다. chr(30) 문자를 xml 문서에서 표시하면 오류가 나기 때문에
            $option['io_id'] = str_replace(chr(30), '&#x2011', $option['io_id']);

            $items['items']['item'.$i]['options']['option'.$j] = $option;
        }
    }

    $sql = " select * from `{$g5['g5_shop_item_use_table']}` ";

    $result = sql_query($sql);

    for($i=0; $use_comment=sql_fetch_array($result); $i++) {
        
        foreach($use_comment as $k=>$v){
            $items['use_comment']['use'.$i][$k] = $v;
        }
    }

    $filename = 'youngcart_data_'.G5_TIME_YMD.'.xml';

    /* Print header */
    header( 'Content-Description: File Transfer' );
    header( 'Content-Disposition: attachment; filename=' . $filename );
    header( 'Content-Type: text/xml; charset=UTF-8', true );

    $xml = new Array2xml();
    $xml->setFilterNumbersInTags(array('item'));
    echo $xml->convert($items);

    exit;

}

$g5['title'] = '상품 내보내기';
include_once (G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_sch02 local_sch">

    <div>

        <form id="export-item-form" method="post">

        <p>
        <label>이미지 출력수 설정, 상품, 사용후기, 이미지, 카테고리와 배너 데이터 를 xml 파일로 내보냅니다.</label>
        <input type="hidden" name="action" value="export_data">
        </p>
        <p style="margin-top:20px">
        <input type="submit" name="submit" class="button shop_etc" value="상품 내보내기">
        </p>
        </form>
        
    </div>

</div>

<script>

jQuery(function($){
});

</script>

<?php
include_once (G5_ADMIN_PATH.'/admin.tail.php');
?>