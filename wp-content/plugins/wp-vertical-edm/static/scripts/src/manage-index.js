(function($){

  function done(failed){
    $("#reindex").removeAttr('disabled').addClass('complete');
    $('#progress').hide();

    if(failed){
      $('#error').show().find('.msg').text(failed);
    }else{
      $('#complete').show();
    }
  }

  function index(page){
    $.ajax({
      url: window.ajaxurl,
      type: 'POST',
      data: {
        'action': 'esreindex',
        'page': page
      },
      error: function(xhr){
        done(xhr.responseText);
      },
      success: function(indexed){
        var indexed = parseInt(indexed);

        var total = $('.finished');

        total.text(parseInt(total.text()) + indexed);

        if(indexed == 10){
          index(page + 1);
        }else{
          done();
        }
      }
    });
  }

  $(function(){
    $('.total').text( 'shitload' );

    $("#reindex").click(function(){
      $(this).attr('disabled', 'disabled');
      $('#progress').show();
      $('#complete').hide();
      $('#error').hide();

      index(1);

      return false;
    });
  });

})(jQuery);