jQuery(document).ready(function($){

	$('#tabs').tabs();
	//$(":button, :submit").button();
	$("#addquiz").click(function(){
		if($("#tabs-5 #title").val() == ''){
			$("#dialog").dialog({modal: true});
			return false;
		}
	})
	
	$("#addquestion").click(function(){
		if($("#text").val() == ''){
			$("#dialog2").dialog({modal: true});
			return false;
		}
		
		var t = 0;
		$(".answers :text").each(function(){
			if($(this).val() == ''){t++;}
		});
		if(t != 0){
			$("#dialog3").dialog({modal: true});
			return false;
		}
		
		var v = 0;
		$(".rw").each(function(){
			if($(this).is(':checked')){v++;}
		});
		if(v == 0){
			$("#dialog4").dialog({modal: true});
			return false;
		}
	})
	$(".ui-button-text").attr('style', 'padding: .25em .25em;');
	$(document).on('click', '.delete', function(){
		
		$(this).parent().remove();
		$("label.answer").html(function(){
			return "Answer " + ($(this).parent().index()+1)
		});
		$("label.answer").attr('for', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		$("input.answer").attr('name', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		$("input.answer").attr('id', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		$(".rw").attr('id', function(){
			return "rw" + ($(this).parent().index()+1)
		});
		$(".rw").attr('value', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		if($("div.answers").children().length == 2){
			$("button.delete").remove();
		}
		return false;
	});
	$(document).on('click', '.addanswer', function(){
		$(this).parent().after($(this).parent()[0].outerHTML);
		if($("div.answers").children().length == 3){
			$('.addanswer').before("<button class='delete'><span class='ui-icon ui-icon-trash'></span></button>");
		}
		if(!$('.delete').hasClass('ui-button')){
			$(".delete").button();
			$(".delete .ui-button-text").attr('style', 'padding: .25em .25em;');
		}
		$("label.answer").html(function(){
			return "Answer " + ($(this).parent().index()+1)
		});
		$("label.answer").attr('for', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		$("input.answer").attr('name', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		$("input.answer").attr('id', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		$(".rw").attr('id', function(){
			return "rw" + ($(this).parent().index()+1)
		});
		$(".rw").attr('value', function(){
			return "answer" + ($(this).parent().index()+1)
		});
		return false;
	});
	$("#anstype").change(function(){
		if($("#anstype option:selected").val() == 'multiple'){
			$(".rw").attr('type', 'checkbox');
			$(".rw").attr('name', 'rw[]');
		}
		else{
			$(".rw").attr('type', 'radio');
			$(".rw").attr('name', 'rw');
		}
	});
	$("#delall").click(function(){
		if ($(this).is(':checked')){
			$('#show input').attr('checked', true);
		}
		else{
			$('#show input').attr('checked', false);
		}
	})
	
});