<?php
include_once('./_common.php');

if(!$board['bo_table']) die('err99');

if(!$write['wr_id']) die('err98');

// 이미지 리스트
$sql = " select *
            from {$g5['board_file_table']}
            where bo_table = '$bo_table'
              and wr_id = '$wr_id'
              and bf_type in ( '1', '2', '3', '6' )
            order by bf_no ";
			
		


$result = sql_query($sql);

$imglist = '';
$link = G5_URL.'/bbs/board.php?bo_table='.$bo_table.'&wr_id='.$wr_id;

for($i=0; $row=sql_fetch_array($result); $i++) {
    if(!$row['bf_file'])
        continue;

    $file = G5_DATA_PATH.'/file/'.$bo_table.'/'.$row['bf_file'];
    if(!is_file($file))
        continue;

    $src = str_replace(G5_PATH, G5_URL, $file);
    $imglist .= '<li><a href="'.$link.'"><img src="'.$src.'" alt="'.($row['bf_content'] ? get_text($row['bf_content']) : get_text($row['bf_source'])).'"></a></li>';
}






if($imglist) {
	echo '<div class="more_wrap">';
	echo '<span class="close"></span>';
    echo '<ul class="more">';	
    echo  $imglist;
    echo '</ul>';	
	echo '</div>';	


} else {
    die('err00');
}



?>
<script>
    $(function () {


            $('.more_wrap').css('top', $(window).scrollTop());



        $('.wrap').outerHeight($(document).outerHeight() - $('#hd').outerHeight());

        if ($('.more li').length > 1) {
            var arrow = "<span class='arrow prev'></span> <span class='arrow next'></span> "
            $('.more li').prepend(arrow);
        }
        $('.more').outerWidth($('.more li').length * 460 + 'px');

        var li_w = $('.more li').width();


        $(".arrow").click(function () {

            var v = $(this).parents('.more_wrap').index();

            var point = $(".more_wrap").find("ul"); // ul의 위치 
            var ulValue = parseInt(point.css("margin-left")); // ul의 현재 margin-left 값
            var liW = point.children("li").width() + parseInt(point.children("li").css("margin-right")); // li의 width 값

            var prevV = parseInt(ulValue) + liW;
            var nextV = parseInt(ulValue) - liW;
            // alert(nextV);


            if ($(this).hasClass("next")) {
                if (ulValue > -li_w && $(".more_wrap").eq(v).find("ul:animated").length < 1) {
                    point.animate({
                        "margin-left": nextV
                    });
                }
            } else {
                if (ulValue < 0 && $(".box").eq(v).find("ul:animated").length < 1) {
                    point.animate({
                        "margin-left": prevV
                    });
                }
            }

            return false;


            $(window).resize(function () {
                if ($(window).width() < 480) {

                    $('.more').outerWidth($('.more li').length * 320 + 'px');


                    if ($(this).hasClass("next")) {
                        if (ulValue > -li_w && $(".more_wrap").eq(v).find("ul:animated").length < 1) {
                            point.animate({
                                "margin-left": nextV
                            });
                        }
                    } else {
                        if (ulValue < 0 && $(".box").eq(v).find("ul:animated").length < 1) {
                            point.animate({
                                "margin-left": prevV
                            });
                        }
                    }


                }

            });
        });


        $('.close').click(function () {
            $('.wrap').css('display', 'none');
            $('.more_wrap').remove();


        });
    });
</script>