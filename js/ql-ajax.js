jQuery(document).ready(function($){
	
	$(document).on('click', '#start, #next, #finish, #back, #resume, #check', function(){
		//$(this).action = 'ql_custom_shortcode';
		var ql_skip = $(this).parent().find("#skip").val();
		var ql_checkcnt = $(this).parent().find("#checkcnt").val();
		if(typeof $(".qlo:checked").val() != 'undefined' || $(this).attr('name') == 'start' || $(this).attr('name') == 'resume' || $(this).attr('name') == 'back' || ql_skip == 1 || (ql_checkcnt == 1 && ($(this).attr('name') == 'next' || $(this).attr('name') == 'finish'))){
			$.ajax({
				type: "POST",
				url: MyAjax.ajaxurl,
				data: $(this).parent().serialize() + '&send=' + $(this).attr("value") + '&singul=' + MyAjax.singul,
				success: function(msg){
					$(".ql-question").replaceWith(msg.slice(0, -1));
					if($(this).attr("value") == 'Check'){
						$("#check").replaceWith('<input type="submit" name="next" value="Next" id="next">');
					}
				},
			});
		}
		else if($(this).attr('name') == 'check'){
			alert("Please check correct answer!");
		}
		return false;
	});
	
	var count = $("#ql-time").html();

	var counter = setInterval(timer, 1000);

	function timer(){
	//console.log(typeof $("#start").val() != 'undefined');
	  if(typeof $("#start").val() != 'undefined'){
	  	count = $("div#ql-time").html();
	  }
	  else{
	  	count = count - 1;
	  }
	  if (count <= 0){
	     clearInterval(counter);
		 $(".ql-question").replaceWith("<h2 class='time-up'>Time is up!</h2>");
		 $(".ql-time").remove();
	     return;
	  }
	  $("p#ql-time").html(count);
	}
});