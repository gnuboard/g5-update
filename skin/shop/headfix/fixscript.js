(function($){
    function getNumberOnly(obj)
    {
        var val = obj;
        val = new String(val);
        var regex = /[^0-9]/g;
        val = val.replace(regex, '');
        
        return val;
    }
    $("#sit_ov #header_move_stick").autofix_anything({
        customOffset : $("#sit_inf").offset().top
    });

    $("#open_order_btn").on("click", function(e){
        var $rayer = $("#sit_ov .sit_ov_body");
        if (!$(this).data('toggle_enable')) {
            $rayer.addClass("is_rayer_view");
            $(this).data('toggle_enable', true)
                .children("i").removeClass("foundicon-plus").addClass("foundicon-minus")
                .end().next("h2").text("주문옵션 닫기");
        } else {
            $rayer.removeClass("is_rayer_view");
            $(this).data('toggle_enable', false)
                .children("i").removeClass("foundicon-minus").addClass("foundicon-plus")
                .end().next("h2").text("주문옵션 열기");
        }
    })
    .next("h2").on("click", function(e){
        $(this).prev().trigger("click");
    });

    price_calculate = (function() {
        var cached_function = price_calculate;

        return function() {
            // your code

            cached_function.apply(this, arguments); // use .apply() to call it
            
            // more of your code
            var totalprice = getNumberOnly( $("#sit_tot_price").html().toString() ),
                total_length = $("#sit_sel_option > > li").length;
            if( total_length > 1 ){
                $("#sit_opt_added").addClass("is_scrolly_hidden");
            } else {
                $("#sit_opt_added").removeClass("is_scrolly_hidden");
            }
            $("#abs_order_optioncnt").text( total_length );
            $("#abs_order_totalprice").text( number_format(totalprice) );
        };
    }());

})(jQuery);