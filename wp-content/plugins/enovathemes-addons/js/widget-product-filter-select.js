(function($){

    "use strict";

    var ajaxUrl = pfilter_select_opt.ajaxUrl;

    function updateURL(data,url){

        var shopURL = url;

        $.each(data, function(key, value) {

            if (typeof(value) != 'undefined' && value != null) {

                value = value.toString();
                if (value.length) {
                    shopURL += '&'+key+'='+value;
                }
            }
        });

        shopURL = encodeURI(shopURL);

        return shopURL;

    }

    $('div.select-filter').each(function(){

        var $this   = $(this),
            shopURL = $this.attr('data-shop')+'?sel=true',
            filter  = {},
            request = false;

        $this.find('select').on('change',function(){

            if (request) {return;}


            var select = $(this),
                data   = {},
                atts   = {};

            if (!$(this).val()) {return;}

            $this.addClass('loading');

            select.parent().nextAll().find('select option:not(".default")').remove();

            select.parents('form').find('select').each(function(){
                if ($(this).val()) {
                   atts[$(this).attr('name')] = $(this).val();
                }
            });


            data['action'] = 'filter_select';
            data['atts']   = JSON.stringify(atts);
            data['next']   = select.parent().next().children().attr('name');

            if (select.attr('name') == 'category') {
                filter['product_cat'] = select.val();
            } else {
                filter['filter_'+select.attr('name')] = select.val();
            }

            var url = updateURL(filter,shopURL);

            $this.find('button').attr('data-url',url);

            request = true;

            $.ajax({
                url:ajaxUrl,
                type: 'POST',
                data: data,
                success: function(output) {
                    if (output) {
                        output = JSON.parse(output);
                        if (output['terms']) {
                            select.parent().next().children().find('option').not('.default').remove();
                            select.parent().next().children().append(output['terms']);
                        }
                        // if (output['args']) {
                        //     console.log(output['args']);
                        // }
                    }
                },
                complete: function() {
                    $this.removeClass('loading');
                    request = false;
                },
                error:function () {
                    alert(pfilter_select_opt.error);
                }
            });

        });

        $this.find('button').on('click',function(e){
            e.preventDefault();
            if ($this.find('input[type="text"]').val()) {
                window.location.replace($this.attr('data-shop')+'?post_type=product&s='+$this.find('input[type="text"]').val());
            } else
            if ($(this).attr('data-url')) {
                window.location.replace($(this).attr('data-url'));
            }
        })

    });

    


})(jQuery);