/**
 * 이미지 업로드 시 formTag에 input (image url) 추가
 * @since 22.08.25
 */
nhn.husky.SE2M_AddImageToForm = jindo.$Class({		
	name : "SE2M_AddImageToForm",
	$init : function(){
	},
	
	$ON_ADD_IMAGE_INPUT : function(imageUrl){
		
		editorId = this.oApp.elPlaceHolder.id
		try {
			window.top.document.getElementById(editorId).insertAdjacentHTML('afterend', '<input type="hidden" name="addImage[]" value="'+imageUrl+'">');
		} catch(e) {
			return false;
		}
	}
});