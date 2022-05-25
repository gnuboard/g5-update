var ShopFixLayer = {
    totalcnt : 0,   //고정값, 고치면 안되요.
    lastindex : 0,  //고정값, 고치면 안되요.
    arr_st : [],    //고정값, 고치면 안되요.
    stickermax : 20,    //수정가능, 생성할수 있는 스티커수
    default_title : "메모",     //수정가능, 스티커를 생성할때 input에 들어갈 default 값
    set_pos_sticker : "0",  //수정가능, 생성된 스티커의 css right
    arr_color : ['#1C59EC','#F04443','#FDC613','#A72660','#E8635D','#46024F','#F902AE','#A95A24','#264F0A','#65CC22'], //수정가능, 생성되는 스티커의 색상값, 인덱스가 없다면 인덱스와 값을 자동으로 생성함
    ret_color : function (idx){
        if( typeof this.arr_color[idx] == 'undefined' ){
            var letters = '0123456789ABCDEF'.split('');
            var color = '#';
            for (var i = 0; i < 6; i++ ) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            this.arr_color[idx] = color;
        }
        return this.arr_color[idx];
    },
    get_st_arr : function(arr){
		for (var i = 0; i < arr.length; i++) {
			if (arr[i] == 0) {
				return i;
				break;
			}
		}
		return 0;
    },
    init_st_set : function (arr) {
		for (var i = 0; i < this.stickermax; i++) {
			arr[i] = 0;
		}
    },
    chk_duplicate_st : function (seletor){
        var seletor_height = parseInt($(seletor).outerHeight(), 10),
            seletor_top = parseInt($(seletor).css("top"), 10),
            success = false;
        
        if($(seletor).prev(".sticker_moving_rayer").length > 0){
            var i = 0,
                chk_int = this.stickermax,
                tmp_seletor_top;
            while (!success){
                if (i == chk_int || i > chk_int) break;
                tmp_seletor_top = seletor_top + ( seletor_height * i );
                success = this.chk_success(seletor, tmp_seletor_top, seletor_height, i);
                i++;
            }
            return tmp_seletor_top;
        } else {
            return $(seletor).css("top");
        }
        
    },
    chk_success : function (seletor, seletor_top, seletor_height, i){
        var success = true;
        $(seletor).siblings(".sticker_moving_rayer").each(function(){
            var othis_top = parseInt($(this).css("top"), 10),
                othis_height = parseInt($(this).outerHeight(), 10);
            
            if ( seletor_top < ( othis_top + othis_height ) && ( seletor_top + seletor_height ) > othis_top) {
                success = false;
            }
        });
        return success;
    },
    clear_st : function(i) {
        jQuery("#input_txt_sticker"+i).val(this.default_title);
    },
    delete_st : function (idx) {

        this.clear_st(idx);

		this.arr_st[idx] = 0;
		this.totalcnt--;

        jQuery("#move_sticker" + idx).hide();
        jQuery("#bookmark_sticker" + idx).hide();

		if (this.totalcnt == 0) {
            jQuery("#bookmark_sticker_list").hide();
            jQuery("#sit_ov").removeClass("is_exist_sticker");
		}

        jQuery("#icon_num_sticker"+idx)
        .qtip('option', 'content.text', this.default_title);
        jQuery("#st_memo_length"+idx).html(this.default_title.length);
	},
    getNumberOnly : function(obj)
    {
        var val = obj;
        val = new String(val);
        var regex = /[^0-9]/g;
        val = val.replace(regex, '');
        
        return val;
    }
};
(function($){
    $("#sit_ov #header_move_stick").autofix_anything({
        customOffset : $("#sit_inf").offset().top
    });
    
    ShopFixLayer.init_st_set(ShopFixLayer.arr_st);

    ShopFixLayer.exist_chk_sticker = function(idx){
        var othis = this,
            defalut_txt_length = othis.default_title.length,
            ms_el = "move_sticker"+idx;
        if( !$("#"+ms_el).length ){ //해당 셀렉터가 있는지 검사하여 없으면 새로 만든다.
            var html = [],
                order = parseInt(idx)+1,
                $innerDiv = $('<div></div>').addClass("sticker_el_contain"),
                $OuterDiv = $('<div></div>')
                .addClass("sticker_moving_rayer")
                .attr({"id":ms_el})
                .css({"right" : othis.set_pos_sticker })
                .hide();

            html.push('<div class="sticker_ic">');
            html.push('<span class="sticker_num" style="background-color:'+this.ret_color(idx)+'" >'+order+'</span>');
            html.push('</div>');
            html.push('<span class="input_memo_box"><input type="text" title="스티커 '+othis.default_title+'" value="'+othis.default_title+'" id="input_txt_sticker'+idx+'" maxlength="10"></span>');
            html.push('<span class="input_memo_length">(<span id="st_memo_length'+idx+'">'+defalut_txt_length+'</span>/10)</span>');
            html.push('<button type="button" class="btn_delete_sticker" onclick="ShopFixLayer.delete_st('+idx+')"><i class="foundicon-remove"></i><span>삭제</span></button>');

            $OuterDiv.append( $innerDiv.append( html.join('') ) );
            $("#sit_ov").append($OuterDiv);
            $("#input_txt_sticker"+idx).on("keyup change", function(e){
                if(e.keyCode == 13) { // Enter submit을 방지한다.
                    return false;
                }
                if( $("#icon_num_sticker"+idx).length ){
                    var txt_value = $(this).val(),
                        txt_length = txt_value.length;
                    $("#icon_num_sticker"+idx).qtip('option', 'content.text', txt_value);
                    $("#st_memo_length"+idx).html(txt_length);
                }
            }).on("keydown", function(e){
                if(e.keyCode == 13) { // Enter submit을 방지한다.
                    return false;
                }
            });
            $("#"+ms_el).draggable({ handle: $("#"+ms_el).find(".sticker_ic"), containment: "body" });
        }
 
        if( !$("#bookmark_sticker"+idx).length ){ //해당 셀렉터가 있는지 검사하여 없으면 새로 만든다.
            var bhtml = [],
                order = parseInt(idx)+1,
                $OuterDiv = $('<li></li>')
                .attr({"id":"bookmark_sticker"+idx})
                .hide(),
                $innerDiv = $('<a></a>')
                .attr({"id":"icon_num_sticker"+idx, "href" : "#", "title" : othis.default_title })
                .addClass("sticker_num")
                .text(order);
            $OuterDiv.append( $innerDiv );
            $("#bookmark_sticker_list ul").append($OuterDiv);
            $("#icon_num_sticker"+idx).qtip({
                position: {
                    my: 'top center',  // Position my top left...
                    at: 'bottom center'
                }
            });
            $("#icon_num_sticker"+idx).on("click", function(e){
                e.preventDefault();
                var move_top = $("#"+ms_el).offset().top - 150;
                $("html, body").animate({ scrollTop: move_top }, 500);
            });
        }
    }

    ShopFixLayer.append_sticker = function () {
        var othis = this;
        if (othis.totalcnt < othis.stickermax) {
	        othis.lastindex = othis.get_st_arr(othis.arr_st);
            var current_idx = othis.lastindex,
                scrollTop = $(document).scrollTop();
            
            othis.exist_chk_sticker( current_idx );
            
            $("#move_sticker" + current_idx).show().css({"top":scrollTop + "px"});
            
            var fixed_this_top = othis.chk_duplicate_st("#move_sticker" + current_idx);
            
            if (typeof fixed_this_top != "undefined" && scrollTop != fixed_this_top) {
                $("#move_sticker" + current_idx).css({"top":fixed_this_top + "px"});
            }

            $("#bookmark_sticker_list").show();
            $("#sit_ov").addClass("is_exist_sticker");

            $("#bookmark_sticker"+current_idx)
            .show()
            .children("a").css({"background-color" : othis.ret_color(current_idx) });

            othis.arr_st[othis.lastindex] = fixed_this_top;
			othis.totalcnt++;

        } else {
			alert("스티커는 최대 "+othis.stickermax+"개까지 사용 할 수 있습니다.");
		}
    }

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
            var totalprice = ShopFixLayer.getNumberOnly( $("#sit_tot_price").html().toString() ),
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