jQuery(document).ready(function($){
    var doing_ajax = false;
    $(document).on('change', 'input#tgm-exchange-getresponse-api-key', function(e){
        var data = {
            action:  'tgm_exchange_getresponse_update_lists',
            api_key: $('#tgm-exchange-getresponse-api-key').val(),
        };

        if ( ! doing_ajax ) {
            doing_ajax = true;
            $('.tgm-exchange-loading').css('display', 'inline');
            $.post(ajaxurl, data, function(res){
                $('.tgm-exchange-getresponse-list-output').html(res);
                $('.tgm-exchange-loading').hide();
                doing_ajax = false;
            });
        }
    });
});