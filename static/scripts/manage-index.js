!function(a){function b(b){a("#reindex").removeAttr("disabled").addClass("complete"),a("#progress").hide(),b?a("#error").show().find(".msg").text(b):a("#complete").show()}function c(d){a.ajax({url:window.ajaxurl,type:"POST",data:{action:"esreindex",page:d},error:function(a){b(a.responseText)},success:function(e){var e=parseInt(e),f=a(".finished");f.text(parseInt(f.text())+e),10==e?c(d+1):b()}})}a(function(){a(".total").text("shitload"),a("#reindex").click(function(){return a(this).attr("disabled","disabled"),a("#progress").show(),a("#complete").hide(),a("#error").hide(),c(1),!1})})}(jQuery);