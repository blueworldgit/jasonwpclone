(function($){

    "use strict";

    var request         = false;
    var firstRequest    = true;
    var shopURLOriginal = pfilter_opt.shopURL;
    var defaultSort     = $('.woocommerce-ordering select option:selected').val();

    function unique(array){
        return array.filter(function (value, index, self) {
            return self.indexOf(value) === index;
        });
    }

    jQuery.fn.inView = function(win,observe) {

        var observe  = (observe) ? observe : 0.6,
            win      = (win) ? win : window,
            height   = jQuery(this).outerHeight(),
            scrolled = jQuery(win).scrollTop(),
            viewed   = scrolled + jQuery(win).height(),
            top      = jQuery(this).offset().top,
            bottom   = top + height;
        return (top + height * observe) <= viewed && (bottom - height * observe) >= scrolled;
        
    };

    function isInArray(value, array) {return array.indexOf(value) > -1;}

    function toggle_hidden_variation_btn() {
        const resetVariationNodes = document.getElementsByClassName('reset_variations');

        if (resetVariationNodes.length) {

            Array.prototype.forEach.call(resetVariationNodes, function (resetVariationEle) {

                let observer = new MutationObserver(function () {

                    if (resetVariationEle.style.visibility !== 'hidden') {

                        resetVariationEle.style.display = 'block';

                    } else {

                        resetVariationEle.style.display = 'none';

                    }

                });

                observer.observe(resetVariationEle, {attributes: true, childList: true});

            })

        }
    }

    function catLayout(){

        $('.pf-item.list[data-attribute="ca"]').each(function(){

            var $this = $(this);

            $this.find('li > a > span.toggle').on('click',function(e){
                e.stopImmediatePropagation();
                $(this).toggleClass('active');
                if ($(this).parent().next('ul').length != 0) {
                    $(this).parent().toggleClass('animate');
                    $(this).parent().next('ul').stop().slideToggle(300);
                    $(this).parents('li').siblings().find('ul').stop().slideUp(300);
                };
                e.preventDefault();
            });

        });

        $('.pf-item.image[data-attribute="ca"]').each(function(){

            var $this = $(this);

            $this.find('li > a').on('click',function(){

                var a = $(this);

                a.parent().toggleClass('active');
                a.parent().siblings().removeClass('active');

                if (a.next('ul').length != 0) {
                    a.parent().addClass('isolate');
                    $this.find('.isolate').not(a.parent()).addClass('disable');
                    a.parent().siblings().addClass('hide');
                    a.parents('ul').addClass('grid-off');
                };
            });

            $this.find('.clear-attribute').on('click',function(){
                $this.find('.isolate').removeClass('isolate').removeClass('disable');
                $this.find('.hide').removeClass('hide');
                $this.find('.grid-off').removeClass('grid-off');
            });

        });

    }

    function compCounter(){
        $('.product').each(function(){
            var $this   = $(this),
                counter = $this.find('.comp-counter input'),
                plus    = $this.find('.comp-counter .plus'),
                minus   = $this.find('.comp-counter .minus');

            plus.on('click',function(){
                var val = parseInt(counter.val());
                val++;
                if (!isNaN(val)) {
                    counter.val(val);
                    $this.find('.add_to_cart_button').attr('data-quantity',val);
                }
            });

            minus.on('click',function(){
                var val = parseInt(counter.val());
                val--;
                if (val <= 0) {val = 1;}
                if (!isNaN(val)) {
                    counter.val(val);
                    $this.find('.add_to_cart_button').attr('data-quantity',val);
                }
            });
        });
    }

    function replaceSort(firstRequest){
        if (firstRequest == false) {return;}
        var sort = $('.woocommerce-ordering > .orderby').clone().removeClass('orderby').addClass('clone').attr('name','orderby_clone');
        $('.woocommerce-ordering > .orderby').remove();
        $('.woocommerce-ordering').append(sort);
    }

    function getParams(url = window.location.href) {

        var url = url.split('?');

        var query = url[1];
        var params = new Object;

        if (typeof(query) != 'undefined' && query != null) {
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                params[pair[0]] = decodeURIComponent(pair[1]);
            }
            return (jQuery.isEmptyObject(params)) ? false : params;
        }

        return false;
    }

    function updateURL(data,url){

        var shopURL = window.location.href;
            shopURL = shopURL.split('?');
            shopURL = shopURL[0];

        var firstIteration = true;

        if (typeof(url) != 'undefined' && url != null && url.length) {
            if (shopURL.indexOf("?") != -1){
                firstIteration = false
            }

            shopURL = url;

        }

        var off = ['attributes','attribute','value','action','display','post_type'];

        $.each(data, function(key, value) {

            if (typeof(value) != 'undefined' && value != null) {

                value = value.toString();
                if (value.length) {
                    if (!off.includes(key)) {
                        var symbol = '&';

                        if (firstIteration) {
                            symbol = '?';
                            firstIteration = false;
                        } else {
                            symbol = '&';
                        }

                        shopURL += symbol+key+'='+value;
                    }
                }
            }
        });

        shopURL = encodeURI(shopURL);

        let activeParams = getParams(shopURL);

        if (activeParams) {

            let activeKeys = Object.keys(activeParams);

            if (activeKeys.length == 1 && activeKeys == "ajax") {
                
                let urlObj = new URL(shopURL);

                urlObj.search = '';

                let result = urlObj.toString();

                shopURL = result;

            }
            
        }

        if (history.pushState) {
            window.history.pushState({path:shopURL}, '', shopURL);
        }

    }

    function socialIcons(){

        var currentLink = encodeURIComponent(window.location.href);

        $('.filter-breadcrumbs .social-share').each(function(){
            var a = $(this);
            if (a.hasClass('facebook')) {
                a.attr('href','//facebook.com/sharer.php?u='+currentLink);
            } else
            if (a.hasClass('twitter')) {
                a.attr('href','//twitter.com/intent/tweet?text='+currentLink);
            } else
            if (a.hasClass('pinterest')) {
                a.attr('href','//pinterest.com/pin/create/button/?url='+currentLink);
            } else
            if (a.hasClass('linkedin')) {
                a.attr('href','//www.linkedin.com/shareArticle?mini=true&url='+currentLink);
            } else
            if (a.hasClass('whatsapp')) {
                a.attr('href','whatsapp://send?text='+currentLink);
            } else
            if (a.hasClass('viber')) {
                a.attr('href','viber://forward?text='+currentLink);
            } else
            if (a.hasClass('telegram')) {
                a.attr('href','tg://msg_url?url='+currentLink);
            }
        });
    }

    function ajaxHandler(data){

        if (request) {return;}

        request = true;

        var attrAJAX = {'action':'filter_attributes'};

        var attributes = [];

        $('.widget_product_filter_widget').first().find('.pf-item').each(function(){

            var item = $(this), atts = new Object;

            atts['name']    = item.attr('data-attribute');
            atts['label']   = item.attr('data-label');
            atts['display'] = item.attr('data-display');
            atts['columns'] = item.attr('data-columns');
            atts['lock']    = item.attr('data-lock');

            if (item.attr('data-category')) {
                atts['category'] = item.attr('data-category');
                atts['children'] = item.attr('data-children');
            }

            if (item.attr('data-category-hide')) {
                atts['category-hide'] = item.attr('data-category-hide');
                atts['children-hide'] = item.attr('data-children-hide');
            }

            attributes.push(atts);

        });

        if (attributes) {
            data['attributes'] = attributes;
        }

        if (data['orderby'] === 'undefined') {
            data['orderby'] = defaultSort;
        }

        data['ajax'] = 'true'; 

        $('.sticky-dashboard').removeClass('active');       

        $.ajax({
            url:pfilter_opt.ajaxUrl,
            type: 'post',
            data: Object.assign(attrAJAX, data),
            success: function(output) {

                output = JSON.parse(output);

                if (output['dev']) {
                    console.log(output['dev']);
                }
                
                if (!$('.product-sidebar.active').length) {
                    $('.sticky-dashboard').addClass('active');       
                }

                var totalFound = (output['total']) ? output['total'] : 0;

                if (totalFound == 0) {
                    $.removeCookie('vehicle', { path: '/' });
                    $('#loop-products').addClass('not-found');
                } else {
                    $('#loop-products').removeClass('not-found');
                }

                $('.universal-title').remove();

                $('.woocommerce-result-count').html(output['found']);

                if ($('ul.page-numbers').length) {
                    $('ul.page-numbers').replaceWith(output['nav']);
                    $('#loop-products').html(output['products']);
                    if (output['total']) {
                        $('.nav-wrapper').removeClass('hidden');
                    }
                } else {

                    if (data['page'] && !$('.woocommerce-pagination').length) {
                        $('#loop-products').append(output['products']);
                    } else {
                        $('#loop-products').html(output['products']);
                    }

                    if (output['total']) {

                        var queriedPostsNum = $('#loop-products .product').length;
                        if (queriedPostsNum == parseInt(output['total'])) {
                            $("#loadmore").addClass('disable').removeClass('loading');
                            $("#infinite").addClass('disable').removeClass('loading');
                        } else {
                            $("#infinite").removeClass('disable');
                            $("#loadmore").removeClass('disable');
                        }

                        $('.nav-wrapper').removeClass('hidden');

                    } else {
                        $("#infinite").addClass('disable');
                        $("#loadmore").addClass('disable');
                    }

                    if (output['max']) {
                        $("#loadmore").attr('data-max',output['max']);
                        $("#infinite").attr('data-max',output['max']);
                    }

                    if (output['next_posts']) {
                        $("#loadmore").attr('data-next',output['next_posts']);
                        $("#infinite").attr('data-next',output['next_posts']);
                    }

                }

                $('.product-sidebar').append('<div class="mobile-total">'+totalFound+' '+pfilter_opt.total+'</div>');

                setTimeout(function(){$('.mobile-total').remove();},1000);

                if (output['breadcrumbs']) {
                    $('.et-breadcrumbs').html(output['breadcrumbs']);
                }

                if (output['bread']) {
                    if ($('.filter-breadcrumbs').length) {
                        $('.filter-breadcrumbs').replaceWith(output['bread']);
                    } else {
                        $(output['bread']).insertBefore($('#loop-products'));
                    }

                    socialIcons();
                    
                } else {$('.filter-breadcrumbs').remove();}


                if (output['cat_description'] && $('.term-description').length) {
                    $('.term-description').html('<p>'+output['cat_description']+'</p>');
                }

                if (output['cat_children'] && $('.loop-categories-container').length) {

                    $('.loop-categories-container').html(output['cat_children']);

                    var columns = 6;

                    var EquipmentSwiper = new Swiper('.loop-categories-wrapper', {
                        direction:'horizontal',
                        loop: false,
                        spaceBetween: 16,
                        slidesPerView: columns,
                        grabCursor: true,
                        autoHeight: true,
                        navigation: {
                            nextEl: '.loop-categories-next',
                            prevEl: '.loop-categories-prev',
                        },
                        breakpoints: {
                            320: {
                                slidesPerView: 2,
                                spaceBetween: 8,
                            },
                            375: {
                                slidesPerView: 2,
                                spaceBetween: 8,
                            },
                            425: {
                                slidesPerView: 3,
                                spaceBetween: 8,
                            },
                            768: {
                                slidesPerView: 4,
                            },
                            1024: {
                                slidesPerView: 5,
                            },
                            1280: {
                                slidesPerView: columns,
                            }
                        }
                    });

                } else if($('.loop-categories-container').length) {
                    $('.loop-categories-container').html('');
                }

                if (output['cat_title'] && $('.title-section-title h1').length) {
                    $('.title-section-title h1').html(output['cat_title']);
                }
                
                compCounter();
                lazyLoad(document.getElementById('loop-products'));
                lazyLoad(document.getElementById('loop-categories'));

                replaceSort(firstRequest);
                updateURL(data,shopURLOriginal);

                if (output['filter_output']) {

                    if(output['filter_output'] instanceof Object){

                        $.each(output['filter_output'], function(key, value) {

                            if (value != null && value.length) {

                                if ($('.pf-item[data-attribute="'+key+'"] .clear-attribute.active').length) {
                                    value = value.replace('clear-attribute','clear-attribute active');
                                }

                                $('.pf-item[data-attribute="'+key+'"]').replaceWith(value);
                                
                            } else {
                                $('.pf-item[data-attribute="'+key+'"]').html('');
                            }
                        });

                    } else {
                        $('form.product-filter').html(output['filter_output']);
                    }

                    var activeParams = getParams();

                    catLayout();
                    widget(false);
                    clearAll($('.widget_product_filter_widget').first());
                    adjustShopByActiveParams();
                }

                showAttrOnCat();
                hideAttrOnCat();
                showAttrIfActive();

                

            },
            complete: function() {
                removeLoading();
                request      = false;
                firstRequest = false;
                hideClearAll();

                $("#loadmore").removeClass('loading');

            },
            error:function () {
                alert(pfilter_opt.error);
            }
        });

    }

    function eventHandler(data){

        var activeParams = getParams();

        if ($('.shop-page.button').length) {
            activeParams = false;
        }

        if (
            ($('body').hasClass('tax-product_cat') && $('.pf-item.cat').length && typeof(data['ca']) == "undefined") || 
            ($('.pf-item.cat').length && typeof(data['ca']) == "undefined" && activeParams && typeof(activeParams['product_cat']) != "undefined")
        ) {
            
            if($('.pf-item.cat').hasClass('select')){
                data['ca'] = $('.pf-item.cat option:selected').val();
            } else {
                data['ca'] = $('.pf-item.cat a.chosen').data('value');
            }

            $('.pf-item.cat .clear-attribute').addClass('active');
           
        }

        if (activeParams) {

            if (typeof(activeParams['rating_filter']) != "undefined" && $('.pf-item.rating').length) {
                data['rating'] = $('.pf-item.rating a.chosen').attr('data-value');
                activeParams['rating_filter'] = '';
            }

            if (typeof(activeParams['product_cat']) != "undefined") {
                activeParams['product_cat'] = '';
            }

            if (typeof(activeParams['vin']) != "undefined" && $('#loop-products').hasClass('not-found')) {
                delete activeParams.vin;
                if ($('form.product-vehicle-filter').length) {

                    $('form.product-vehicle-filter').find('select').each(function(){
                        $(this).find('option.default').attr('selected','selected').siblings().removeAttr('selected');
                        $(this).trigger('change');
                    });

                    $('form.product-vehicle-filter').removeClass('active');

                }
            }

            var on = ['list','image','label','col'];

            if (on.includes(data['display'])) {
                $.each(activeParams, function(key, value) {
                    if (data.hasOwnProperty(key)) {
                        if (key != 'ca') {
                            var dataVal = data[key];
                            if (value.indexOf(dataVal) == -1) {
                                var valueArray = value.split(',');
                                valueArray.push(dataVal);
                                data[key] = valueArray.join(',');
                            }
                        }
                    }
                });
            }

            data = Object.assign(activeParams, data);
        }

        if(!jQuery.isEmptyObject(data)){
            $('.shop-page.button').remove();
            ajaxHandler(data);
        };

    }

    function removeLoading(){
        $('.product-filter-overlay').removeClass('loading');
    }

    function addLoading(){
        $('.product-filter-overlay').addClass('loading');
    }

    function showClear(pfItem){
        pfItem.find('.clear-attribute').addClass('active');
    }

    function showClearAll(){
        $('.clear-all-attribute').addClass('active');
    }

    function hideClearAll(){

        var activeParams = getParams(),
            hide = false;

        if (activeParams) {

            var keys = Object.keys(activeParams);

            if (
                (keys.length == 1 && keys.includes('ajax')) || 
                (keys.length == 2 && keys.includes('ajax') && keys.includes('vin')) || 
                (keys.length == 2 && keys.includes('ajax') && keys.includes('orderby'))
            ) {
                hide = true;
            }

        } else {
            hide = true;
        }

        if (hide) {$('.clear-all-attribute').removeClass('active');}
    }

    function hideClear(pfItem){
        pfItem.find('.clear-attribute').removeClass('active');
    }

    function showAttrOnCat(){

        var activeParams = getParams();

        if (activeParams && (activeParams['ca'] || activeParams['product_cat'])) {
            $('.cat-active').each(function(){

                if (this.hasAttribute('data-category')) {
                    var $this    = $(this);
                    var children = $this.attr('data-children');
                    var categories = $this.attr('data-category');
                    var cats       = categories.split(',');

                    if(typeof(children) != 'undefined'){
                        if (children != 'false') {
                            children = children.split(',');
                            for (var i = 0; i < children.length; i++) {
                                cats.push(children[i]);
                            }
                        }
                    }
                    
                    cats = unique(cats);

                    if (cats.includes(activeParams['ca']) || cats.includes(activeParams['product_cat'])) {
                        $this.addClass('visible');
                    } else {
                        $this.removeClass('visible');
                    }

                }

            });
        } else {
            $('.cat-active').each(function(){

                if (this.hasAttribute('data-category')) {
                    var $this    = $(this);
                    var children = $this.attr('data-children');
                    var categories = $this.attr('data-category');
                    var cats       = categories.split(',');

                    if(typeof(children) != 'undefined'){
                        if (children != 'false') {
                            children = children.split(',');
                            for (var i = 0; i < children.length; i++) {
                                cats.push(children[i]);
                            }
                        }
                    }
                    
                    cats = unique(cats);

                    var classes = [];
                    var bodyClasses = $('body').attr('class');

                    bodyClasses = bodyClasses.split(' ');

                    for (var i = 0; i < cats.length; i++) {
                        classes.push('term-'+cats[i]);
                    }

                    var has = false;

                    for (var i = 0; i < classes.length; i++) {
                        if(bodyClasses.includes(classes[i])){
                            has = true;
                        }
                    }

                    if (activeParams && typeof(activeParams['product_cat']) != 'undefined') {
                        if(classes.includes(activeParams['product_cat'])){
                            has = true;
                        }
                    }

                    if (has) {
                        $this.addClass('visible')
                    } else {
                        $this.removeClass('visible')
                    }

                }

            });
        }
        
    }

    function hideAttrOnCat(){

        var activeParams = getParams();

        if (activeParams && (activeParams['ca'] || activeParams['product_cat'])) {
            $('.cat-hide-active').each(function(){

                if (this.hasAttribute('data-category-hide')) {
                    var $this    = $(this);
                    var children = $this.attr('data-children-hide');
                    var categories = $this.attr('data-category-hide');
                    var cats       = categories.split(',');

                    if(typeof(children) != 'undefined'){
                        if (children != 'false') {
                            children = children.split(',');
                            for (var i = 0; i < children.length; i++) {
                                cats.push(children[i]);
                            }
                        }
                    }
                    
                    cats = unique(cats);

                    if (cats.includes(activeParams['ca']) || cats.includes(activeParams['product_cat'])) {
                        $this.addClass('hide');
                    } else {
                        $this.removeClass('hide');
                    }

                }

            });
        } else {
            $('.cat-hide-active').each(function(){

                if (this.hasAttribute('data-category-hide')) {
                    var $this    = $(this);
                    var children = $this.attr('data-children-hide');
                    var categories = $this.attr('data-category-hide');
                    var cats       = categories.split(',');

                    if(typeof(children) != 'undefined'){
                        if (children != 'false') {
                            children = children.split(',');
                            for (var i = 0; i < children.length; i++) {
                                cats.push(children[i]);
                            }
                        }
                    }
                    
                    cats = unique(cats);

                    var classes = [];
                    var bodyClasses = $('body').attr('class');

                    bodyClasses = bodyClasses.split(' ');

                    for (var i = 0; i < cats.length; i++) {
                        classes.push('term-'+cats[i]);
                    }

                    var has = false;

                    for (var i = 0; i < classes.length; i++) {
                        if(bodyClasses.includes(classes[i])){
                            has = true;
                        }
                    }

                    if (activeParams && typeof(activeParams['product_cat']) != 'undefined') {
                        if(classes.includes(activeParams['product_cat'])){
                            has = true;
                        }
                    }

                    if (has) {
                        $this.addClass('hide')
                    } else {
                        $this.removeClass('hide')
                    }

                }

            });
        }
        
    }

    function showAttrIfActive(){

        var activeParams = getParams();

        if (activeParams && $('.pf-item.attr').length){

            $('.pf-item.attr').each(function(){
                var attr = $(this);
                if (typeof(activeParams['filter_'+attr.data('attribute')]) != 'undefined') {
                    attr.addClass('visible');
                }
            });

        }
    }

    function pfItemPrice(slider){

        var max       = slider.find('.slider').data('max'),
            min       = slider.find('.slider').data('min'),
            step      = slider.find('.slider').data('step'),
            currency  = slider.find('.slider').data('currency'),
            position  = slider.find('.slider').data('position'),
            values    = slider.find('.slider').data('values'),
            handle    = slider.find( ".ui-slider-handle" );

            if (typeof(values) != 'undefined' && values != null && values.length) {
                values = values.split(',');
            } else {
                values = [ min, max ]
            }

        var data = new Object,
            attribute = slider.attr('data-attribute'),
            display = slider.attr('data-display');

        data['attribute'] = attribute;
        data['display'] = display;

        var sliderRange = slider.find('.slider').slider({
            range:true,
            max: max,
            min: min,
            step: step,
            values: values,
            create: function( event, ui ) {

                var price1 = currency+values[0],
                    price2 = currency+values[1];

                if (position == 'right') {
                    price1 = values[0]+currency;
                    price2 = values[1]+currency;
                } else if(position == 'right_space'){
                    price1 = values[0]+' '+currency;
                    price2 = values[1]+' '+currency;
                }

                handle.eq(0).find('.ui-slider-handle-bubble').text( price1 );
                handle.eq(1).find('.ui-slider-handle-bubble').text( price2 );

                slider.find('input[name="min"]').val(values[0]);
                slider.find('input[name="max"]').val(values[1]);

            },
            slide: function( event, ui ) {

                var price = currency+ui.value;

                if (position == 'right') {
                    price = ui.value+currency;
                }

                handle.eq(ui.handleIndex).find('.ui-slider-handle-bubble').text( price );

                slider.find('input[name="min"]').val(ui.values[0]);
                slider.find('input[name="max"]').val(ui.values[1]);

            },
            change: function( event, ui ) {

                var price = currency+ui.value;

                if (position == 'right') {
                    price = ui.value+currency;
                }

                handle.eq(ui.handleIndex).find('.ui-slider-handle-bubble').text( price );

                slider.find('input[name="min"]').val(ui.values[0]);
                slider.find('input[name="max"]').val(ui.values[1]);

            },
            stop: function( event, ui ) {

                var activeParams = getParams();
                var request = true;
                var price = currency+ui.value;

                if (position == 'right') {
                    price = ui.value+currency;
                }

                handle.eq(ui.handleIndex).find('.ui-slider-handle-bubble').text( price );

                slider.find('input[name="min"]').val(ui.values[0]);
                slider.find('input[name="max"]').val(ui.values[1]);


                if (
                    (activeParams &&
                    activeParams.hasOwnProperty('min_price') && activeParams['min_price'] == ui.values[0] &&
                    activeParams.hasOwnProperty('max_price') && activeParams['max_price'] == ui.values[1]) ||
                    (max == ui.values[1] && min == ui.values[0])
                ) {

                    request = false;
                }

                if (request) {

                    addLoading();

                    data['page'] = '';
                    data['min_price'] = ui.values[0];
                    data['max_price'] = ui.values[1];

                    eventHandler(data);

                    showClear(slider);
                    showClearAll();

                }
            }
        });

        slider.find('input[name="min"]').on( "change", function() {
            if (!isNaN($(this).val())) {
                var  values = [parseInt($(this).val()), parseInt(slider.find('input[name="max"]').val())];
                sliderRange.slider( "values", values );

                var activeParams = getParams();
                var request = true;

                if (
                    (activeParams &&
                    activeParams.hasOwnProperty('min_price') && activeParams['min_price'] == values[0] &&
                    activeParams.hasOwnProperty('max_price') && activeParams['max_price'] == values[1]) ||
                    (max == values[1] && min == values[0])
                ) {
                    request = false;
                }

                if (request) {

                    addLoading();

                    data['page'] = '';
                    data['min_price'] = values[0];
                    data['max_price'] = values[1];

                    eventHandler(data);

                    showClear(slider);
                    showClearAll();

                }
            }
        });

        slider.find('input[name="max"]').on( "change", function() {
            if (!isNaN($(this).val())) {
                var  values = [parseInt(slider.find('input[name="min"]').val()),parseInt($(this).val())];
                sliderRange.slider( "values", values );

                var activeParams = getParams();
                var request = true;

                if (
                    (activeParams &&
                    activeParams.hasOwnProperty('min_price') && activeParams['min_price'] == values[0] &&
                    activeParams.hasOwnProperty('max_price') && activeParams['max_price'] == values[1]) ||
                    (max == values[1] && min == values[0])
                ) {
                    request = false;
                }

                if (request) {

                    addLoading();

                    data['page'] = '';
                    data['min_price'] = values[0];
                    data['max_price'] = values[1];

                    eventHandler(data);

                    showClear(slider);
                    showClearAll();

                }
            }
        });

    }

    function pfItemSlider(slider){

        var max       = slider.find('.slider').data('max'),
            min       = slider.find('.slider').data('min'),
            values    = slider.find('.slider').data('values'),
            handle    = slider.find( ".ui-slider-handle" );

            if (typeof(values) != 'undefined' && values != null && values.length) {
                values = values.split(',');
            } else {
                values = [ min, max ]
            }

        var data = new Object,
            attribute = slider.attr('data-attribute'),
            display = slider.attr('data-display');

        data['attribute'] = attribute;
        data['display']   = display;

        var sliderRange = slider.find('.slider').slider({
            range:true,
            max: max,
            min: min,
            values: values,
            create: function( event, ui ) {
                handle.eq(0).find('.ui-slider-handle-bubble').text( values[0] );
                handle.eq(1).find('.ui-slider-handle-bubble').text( values[1] );

                slider.find('input[name="min"]').val(values[0]);
                slider.find('input[name="max"]').val(values[1]);
            },
            slide: function( event, ui ) {
                handle.eq(ui.handleIndex).find('.ui-slider-handle-bubble').text( ui.value );

                slider.find('input[name="min"]').val(ui.values[0]);
                slider.find('input[name="max"]').val(ui.values[1]);
            },
            change: function( event, ui ) {

                handle.eq(ui.handleIndex).find('.ui-slider-handle-bubble').text( ui.value );

            },
            stop: function( event, ui ) {

                handle.eq(ui.handleIndex).find('.ui-slider-handle-bubble').text( ui.value );

                var activeParams = getParams();
                var request = true;

                if (
                    (activeParams &&
                    activeParams.hasOwnProperty(attribute+'_min_value') && activeParams[attribute+'_min_value'] == ui.values[0] &&
                    activeParams.hasOwnProperty(attribute+'_max_value') && activeParams[attribute+'_max_value'] == ui.values[1]) ||
                    (max == ui.values[1] && min == ui.values[0])
                ) {
                    request = false;
                }

                if (request) {

                    addLoading();

                    data['filter_'+attribute] = '';
                    data['page'] = '';
                    data[attribute+'_min_value'] = ui.values[0];
                    data[attribute+'_max_value'] = ui.values[1];

                    slider.find('input[name="min"]').val(ui.values[0]);
                    slider.find('input[name="max"]').val(ui.values[1]);

                    eventHandler(data);

                    showClear(slider);
                    showClearAll();

                }

            }
        });

        slider.find('input[name="min"]').on( "change", function() {
            if (!isNaN($(this).val())) {
                var  values = [parseInt($(this).val()), parseInt(slider.find('input[name="max"]').val())];
                sliderRange.slider( "values", values );

                var activeParams = getParams();
                var request = true;

                if (
                    (activeParams &&
                    activeParams.hasOwnProperty('min_price') && activeParams['min_price'] == values[0] &&
                    activeParams.hasOwnProperty('max_price') && activeParams['max_price'] == values[1]) ||
                    (max == values[1] && min == values[0])
                ) {
                    request = false;
                }

                if (request) {

                    addLoading();

                    data['filter_'+attribute] = '';
                    data['page'] = '';
                    data[attribute+'_min_value'] = values[0];
                    data[attribute+'_max_value'] = values[1];

                    slider.find('input[name="min"]').val(values[0]);
                    slider.find('input[name="max"]').val(values[1]);

                    eventHandler(data);

                    showClear(slider);
                    showClearAll();

                }

            }
        });

        slider.find('input[name="max"]').on( "change", function() {
            if (!isNaN($(this).val())) {
                var  values = [parseInt(slider.find('input[name="min"]').val()),parseInt($(this).val())];
                sliderRange.slider( "values", values );

                var activeParams = getParams();
                var request = true;

                if (
                    (activeParams &&
                    activeParams.hasOwnProperty('min_price') && activeParams['min_price'] == values[0] &&
                    activeParams.hasOwnProperty('max_price') && activeParams['max_price'] == values[1]) ||
                    (max == values[1] && min == values[0])
                ) {
                    request = false;
                }

                if (request) {

                    addLoading();

                    data['filter_'+attribute] = '';
                    data['page'] = '';
                    data[attribute+'_min_value'] = values[0];
                    data[attribute+'_max_value'] = values[1];

                    slider.find('input[name="min"]').val(values[0]);
                    slider.find('input[name="max"]').val(values[1]);

                    eventHandler(data);

                    showClear(slider);
                    showClearAll();

                }
            }
        });
    }

    function pfItemSliderRebuild(pfItem){

        pfItem.find('.slider').slider("destroy");

        var max       = pfItem.find('.slider').data('max'),
            min       = pfItem.find('.slider').data('min');

        pfItem.removeData('values');
        pfItem.find('.slider').append('<div class="ui-slider-handle"><span class="ui-slider-handle-bubble min">'+min+'</span></div><div class="ui-slider-handle"><span class="ui-slider-handle-bubble max">'+max+'</span></div>')

        pfItemSlider(pfItem);
    }

    function pfItemPriceRebuild(pfItem){
        pfItem.find('.slider').slider("destroy");
                    
        var max       = pfItem.find('.slider').data('max'),
            min       = pfItem.find('.slider').data('min'),
            currency  = pfItem.find('.slider').data('currency'),
            position  = pfItem.find('.slider').data('position');

        if (position == 'right') {
            max = max+currency;
            min = min+currency;
        } else {
            max = currency+max;
            min = currency+min;
        }

        pfItem.removeData('values');
        pfItem.find('.slider').append('<div class="ui-slider-handle"><span class="ui-slider-handle-bubble min">'+min+'</span></div><div class="ui-slider-handle"><span class="ui-slider-handle-bubble max">'+max+'</span></div>')

        pfItemPrice(pfItem);
    }

    function clearAttributes(pfItem){
        pfItem.find('.clear-attribute').on('click',function(){

            addLoading();

            var clear = $(this);
            clear.removeClass('active');

            if (pfItem.hasClass('slider')) {

                pfItemSliderRebuild(pfItem);

                var data      = new Object,
                    attribute = pfItem.attr('data-attribute'),
                    display   = pfItem.attr('data-display');

                data['page'] = '';
                data['filter_'+attribute] = '';
                data['pn'] = '';
                data[attribute+'_min_value'] = '';
                data[attribute+'_max_value'] = '';
                eventHandler(data);

            } else
            if (pfItem.hasClass('price')) {

                pfItemPriceRebuild(pfItem);

                var data      = new Object,
                    attribute = pfItem.attr('data-attribute'),
                    display   = pfItem.attr('data-display');

                data['page'] = '';
                data['min_price'] = '';
                data['max_price'] = '';
                eventHandler(data);

            } else
            if (pfItem.hasClass('clickable')) {

                display   = pfItem.attr('data-display');

                pfItem.find('a').each(function(){
                    $(this).removeClass('chosen');
                });

                pfItem.find('.hidden').removeClass('hidden');
                pfItem.find('.isolate').removeClass('isolate');
                pfItem.find('.hide').removeClass('hide');
                pfItem.find('.disable').removeClass('disable');
                pfItem.find('.grid-off').removeClass('grid-off');

                if (pfItem.hasClass('cat') && (display == 'list' || display == 'image-list')) {
                    pfItem.find('.toggle.active').trigger('click');
                }

                pfItem.find('.active').removeClass('active');

                var data      = new Object,
                    attribute = pfItem.attr('data-attribute'),
                    display   = pfItem.attr('data-display');

                data['page'] = '';
                data['filter_'+attribute] = '';
                data['attribute'] = attribute;
                data['display'] = display;
                data['value'] = '';
                data['rating_filter'] = '';
                data['product_cat'] = '';
                data[attribute] = data['value'];

                eventHandler(data);

            } else
            if (pfItem.hasClass('select')) {
                pfItem.find('select').val('');

                var data      = new Object,
                    attribute = pfItem.attr('data-attribute'),
                    display   = pfItem.attr('data-display');

                data['page'] = '';
                data['filter_'+attribute] = '';
                data['attribute'] = attribute;
                data['display'] = display;
                data['value'] = '';
                data[attribute] = data['value'];

                eventHandler(data);
            }

        });
    }

    function clearAll(widget){
        $("body").on('click','.clear-all-attribute',function(e){

            e.preventDefault()

            addLoading();

            var clear = $(this);
            clear.removeClass('active');

            widget.find('.pf-item').each(function(){

                var pfItem = $(this);

                pfItem.find('.clear-attribute').removeClass('active');
                pfItem.find('.hidden').removeClass('hidden');
                pfItem.find('.isolate').removeClass('isolate');
                pfItem.find('.hide').removeClass('hide');
                pfItem.find('.disable').removeClass('disable');
                pfItem.find('.grid-off').removeClass('grid-off');

                if (pfItem.hasClass('cat') && pfItem.data('display') == 'list') {
                    pfItem.find('.toggle.active').trigger('click');
                }

                pfItem.find('.active').removeClass('active');

                if (pfItem.hasClass('slider')) {
                    pfItemSliderRebuild(pfItem);
                } else
                if (pfItem.hasClass('price')) {
                    pfItemPriceRebuild(pfItem);
                } else
                if (pfItem.hasClass('clickable')) {
                    pfItem.find('a').each(function(){
                        $(this).removeClass('chosen');
                    });
                } else
                if (pfItem.hasClass('select')) {
                    pfItem.find('select').val('');
                }
            });

            // Vehicle reset
            $.removeCookie('vehicle', { path: '/' });
            $('form.product-vehicle-filter .reset').addClass('clear-all').trigger('click');

            var activeParams = getParams();

            if (activeParams) {
                $.each(activeParams, function(key, value) {
                    if (key != 'data_shop') {activeParams[key]='';}
                });

                eventHandler(activeParams);
            }

            $('.woocommerce-ordering select option[value="'+defaultSort+'"]').attr('selected','selected').siblings().removeAttr('selected');

            $( document ).ajaxComplete(function( event, xhr, settings ) {

                if (settings['url'].includes('shop')) {

                    wishlistToggle(wishlist);

                } else if (typeof(settings['data']) != 'undefined' && settings['data'] != null) {

                    var data = decodeURIComponent(settings['data']);

                    data = data.split("&");

                    var dataObj = [{}];

                    for (var i = 0; i < data.length; i++) {
                        var property = data[i].split("=");
                        var key      = (property[0]);
                        var value    = (property[1]);
                        if (typeof(value) != 'undefined') {
                            dataObj[key] = value;
                        }
                    }

                    if('filter_attributes' == dataObj['action'] && !getParams()){

                        $('.product-layout')
                        .removeClass('comp')
                        .removeClass('list')
                        .removeClass('grid')
                        .removeClass('small')
                        .removeClass('medium')
                        .removeClass('large')
                        .addClass(defaultLayout)
                        .addClass(defaultSize);

                        $('.sale-products').removeClass('chosen');

                    }

                }
            });

            

        });
    }

    function widget(sendRequest = true){

        var widget = $('.widget_product_filter_widget').first();
        var activeParams = getParams();

        if (sendRequest) {

            if (activeParams && activeParams['ajax'] == 'true') {
                var nav = $('#loop-products').data('nav');

                if (nav != 'pagination') {
                    activeParams['page'] = '';
                }

                addLoading();
                ajaxHandler(activeParams);

                $('.clear-all-attribute').addClass('active');
            }

        }

        widget.find('.pf-item.slider').each(function(){

            var $this = $(this);

            if (activeParams && activeParams['ajax'] == 'true') {

                var name = $this.attr('data-attribute');

                if (typeof(activeParams[name+'_max_value']) != 'undefined') {

                    var values = [activeParams[name+'_min_value'],activeParams[name+'_max_value']];

                    $this.data( "values",values.join(','));

                    $this.find('.clear-attribute').addClass('active');
                }
            }

            pfItemSlider($this);
            clearAttributes($this);

        });

        widget.find('.pf-item.price').each(function(){

            var $this = $(this);

            if (activeParams) {
                if (typeof(activeParams['max_price']) != 'undefined') {

                    var values = [activeParams['min_price'],activeParams['max_price']];

                    $this.data( "values",values.join(','));

                    $this.find('.clear-attribute').addClass('active');
                }
            }

            pfItemPrice($this);
            clearAttributes($this);

        });

        widget.find('.pf-item.clickable').each(function(){

            var data      = new Object,
                item      = $(this),
                attribute = item.attr('data-attribute'),
                display = item.attr('data-display'),
                exists  = false;

            data['page']      = '';
            data['attribute'] = attribute;
            data['display']   = display;

            if (activeParams) {

                if (typeof(activeParams[attribute]) != 'undefined' && activeParams['ajax'] == 'true') {

                    var activeAtts = activeParams[attribute];

                    activeAtts = activeAtts.split(',');

                    for (var i = 0; i < activeAtts.length; i++) {
                        item.find('a[data-value="'+activeAtts[i]+'"]').addClass('chosen');
                    }

                    if (item.hasClass('rating') && item.find('a.chosen').length) {
                        item.find('a.chosen').parent().siblings().addClass('hidden');
                    }

                    if (item.hasClass('cat') && item.find('a.chosen').length) {
                        if (display == 'list' || display == 'image-list') {
                            item.find('a.chosen').parents('ul:not(".category")').prev('a').find('.toggle').trigger('click');
                        } else if(display == 'image') {

                            var a = item.find('a.chosen');
                            a.parents('ul').prev('a').trigger('click');
                            a.trigger('click');

                        }
                    }

                    item.find('.clear-attribute').addClass('active');
                    
                }

                if (typeof(activeParams['filter_'+attribute]) != 'undefined') {
                    exists = true;
                }

                if (exists) {
                    item.find('.clear-attribute').addClass('active');
                }
            }

            item.find('a').on('click',function(e){

                var a = $(this);
                var request = true;

                if (item.hasClass('cat')) {
                    a.addClass('chosen');
                    item.find('.chosen').not(a).removeClass('chosen');
                } else {
                    a.toggleClass('chosen');

                    if (item.hasClass('rating')) {
                        a.parent().siblings().addClass('hidden');
                    }

                }

                e.preventDefault();

                if (activeParams && activeParams.hasOwnProperty(attribute) && activeParams[attribute] == a.attr('data-value')) {
                    request = false;
                }

                if (request) {

                    addLoading();

                    if (a.hasClass('chosen')) {
                        if (exists) {

                            if (a.parents('ul').find('.chosen').length) {

                                var values = [];

                                a.parents('ul').find('.chosen').each(function(){
                                    values.push($(this).attr('data-value'));
                                });

                                if (values.length) {
                                    data['value'] = values.join(',');
                                    data['filter_'+attribute] = '';
                                    showClear(item);
                                    showClearAll();
                                }
                                
                            } else {
                                hideClear(item);
                                data['value'] = '';
                            }
                        } else {
                            data['value'] = a.attr('data-value');
                            showClear(item);
                            showClearAll();
                        }
                        
                    } else {

                        if (a.parents('ul').find('.chosen').length) {

                            var values = [];

                            a.parents('ul').find('.chosen').each(function(){
                                values.push($(this).attr('data-value'));
                            });

                            if (values.length) {
                                data['value'] = values.join(',');
                            }
                            
                        } else {
                            hideClear(item);
                            data['value'] = '';
                            data['filter_'+attribute] = '';
                        }
                        
                    }

                    data[attribute] = data['value'];

                    eventHandler(data);

                }

            });

            clearAttributes(item);

        });

        widget.find('.pf-item.select,.pf-item.selectable').each(function(){

            var data      = new Object,
                item      = $(this),
                attribute = item.attr('data-attribute'),
                display = item.attr('data-display'),
                exists  = false;

            data['page']      = '';
            data['attribute'] = attribute;
            data['display'] = display;

            if (activeParams) {

                if (typeof(activeParams[attribute]) != 'undefined' || typeof(activeParams['filter_'+attribute]) != 'undefined') {
                    item.find('option[value="'+ activeParams[attribute]+'"]').attr('selected','selected');
                    
                }

                if (typeof(activeParams['filter_'+attribute]) != 'undefined') {
                    exists = true;
                }

                if (exists) {
                    item.find('.clear-attribute').addClass('active');
                }

            }

            item.find('select').on('change',function(){


                var thisVal = $(this).val();

                addLoading();

                if (thisVal.length) {
                    showClear(item);
                    showClearAll();
                } else {
                    hideClear(item);
                }

                data['value'] = thisVal;

                data[attribute] = data['value'];

                eventHandler(data);

            });

            clearAttributes(item);

        });

    }

    function adjustShopByActiveParams(){

        var activeParams = getParams();

        if (activeParams) {
            var activeCategory = activeParams['product_cat'];

            if (typeof(activeParams['rating_filter']) != "undefined" && $('.pf-item.rating').length) {
                var chosen = $('.pf-item.rating a[data-value="'+activeParams['rating_filter']+'"]').addClass('chosen');
                chosen.parent().siblings().addClass('hidden');
                $('.pf-item.rating .clear-attribute').addClass('active');
            }


            var activeLayout = activeParams['plt'],
                activeSize   = activeParams['psz'],
                onSale       = activeParams['onsale'];

            if (typeof(activeLayout) != "undefined" && typeof(activeSize) != "undefined") {
                $('.product-layout')
                .removeClass('comp')
                .removeClass('list')
                .removeClass('grid')
                .removeClass('small')
                .removeClass('medium')
                .removeClass('large')
                .addClass(activeLayout)
                .addClass(activeSize);


            }

            if (typeof(onSale) != "undefined") {
                $('.sale-products').addClass('chosen');
            }



        }

        if ($('#loop-categories').length && $('form[name="product-filter"]').length) {

            var shopPage = activeParams ? window.location.href : $('div.product-filter').data('shop');

            shopPage = shopPage.includes('?') ? shopPage + '&ca=' : shopPage + '?ca=';

            $('#loop-categories a').each(function(){
                
                $(this).on('click',function(e){
                    e.preventDefault();

                    var link = ($(this).attr('href')).split('/');

                    link = link[link.length - 2];

                    if (link) {
                        $(this).attr('href',shopPage+link);
                    }

                    if (activeParams == false) {
                        activeParams = {};
                    }

                    activeParams['ca'] = link;

                    addLoading();
                    eventHandler(activeParams);

                });

            });
        }

        if ($('.pf-item.list.cat').length) {
            $('.pf-item.list.cat a').each(function(){

                if (
                    (window.location.href.indexOf('product-category/') > -1 && window.location.href.indexOf('/'+$(this).data('value')) > -1) || 
                    (typeof(activeCategory) != 'undefined') && activeCategory == $(this).data('value')
                ) {
                    $(this).addClass('chosen');
                    $(this).parents('ul').prev('a').find('.toggle').trigger('click');
                }

            });
        }else if ($('.pf-item.image.cat').length) {
            $('.pf-item.image[data-attribute="ca"]').each(function(){

                var $this = $(this);

                $this.find('a').each(function(){

                    var a = $(this);

                    if ((window.location.href.indexOf('product-category/') > -1 && window.location.href.indexOf('/'+$(this).data('value')) > -1) || 
                        (typeof(activeCategory) != 'undefined') && activeCategory == $(this).data('value')) {

                        a.parents('li').addClass('isolate').siblings().addClass('hide');
                        $this.find('.isolate').not(a.parent()).addClass('disable');
                        a.parents('ul').addClass('grid-off');

                        if (a.next('ul').length != 0) {
                            // a.trigger('click');
                        } else {
                            a.parent().addClass('active').removeClass('isolate');
                            a.parent().siblings().removeClass('hide');
                            a.parent().parent().removeClass('grid-off');
                            a.parent().parent().parent().removeClass('disable');
                        }

                    }
                });

            });
        }else if ($('.pf-item.select.cat').length) {
            $('.pf-item.select[data-attribute="ca"]').each(function(){

                var $this = $(this);

                $this.find('select option').each(function(){

                    var option = $(this);

                    if (
                        (window.location.href.indexOf('product-category/') > -1 && window.location.href.indexOf('/'+$(this).data('value')) > -1) || 
                        (typeof(activeCategory) != 'undefined') && activeCategory == $(this).val()) {
                        option.attr('selected','selected').siblings().removeAttr('selected');
                    }
                });

            });
        }

        if ($('.pf-item.attr').length) {

            $('.pf-item.attr').each(function(){
                var attr = $(this);

                if(attr.hasClass('clickable')){
                    attr.find('a').each(function(){
                        if ($('body').hasClass('term-'+$(this).data('value')) || (typeof(activeParams['filter_'+attr.data('attribute')]) != 'undefined') && activeParams['filter_'+attr.data('attribute')] == $(this).data('value')) {
                            $(this).addClass('chosen');
                        }
                    });
                } else if (attr.hasClass('select')){
                    attr.find('option').each(function(){
                        if ($('body').hasClass('term-'+$(this).val()) || (typeof(activeParams['filter_'+attr.data('attribute')]) != 'undefined') && activeParams['filter_'+attr.data('attribute')] == $(this).val()) {
                            $(this).attr('selected','selected');
                        }
                    });
                }
            });
            
        }

        if ($('.pf-item.cat').length && window.location.href.indexOf('product-category/') > -1) {
            if ((
                    (
                        ($('.pf-item.cat').hasClass('image') && $('.pf-item.cat li.active').length) || 
                        ($('.pf-item.cat').hasClass('list')) && $('.pf-item.cat a.chosen').length)
                    ) || 
                    ($('.pf-item.cat').hasClass('select') && $('.pf-item.cat option:selected').val())
                ) {
                $('.pf-item.cat').find('.clear-attribute').addClass('active');
            }
        }

        $('.filter-breadcrumbs').addClass('cs');

        $('.reload-all-attribute').on('click',function(e){
            e.preventDefault()

            var activeParams = getParams();

            if (activeParams) {
                addLoading();
                eventHandler(activeParams);
            }
            
        });

    }

    function createURL(shopURL,data,reload = true){

        var newshopURL = clearParams(shopURL);

        if (newshopURL) {
            shopURL = newshopURL;
        }

        if (shopURL.indexOf("?") == -1){
            shopURL += '?';
        }

        $.each(data, function(key, value) {
            if (value.length) {
                if (key == 'year') {key = 'yr';}
                shopURL += '&'+key+'='+value;
            }
        });

        shopURL = shopURL.replace('?&', '?');

        shopURL = encodeURI(shopURL);

        if (reload) {
            window.location.assign(shopURL);
        } else {
            history.pushState({}, null, shopURL);
        }

    }

    catLayout();
    widget();
    compCounter();
    clearAll($('.widget_product_filter_widget').first());
    showAttrOnCat();
    hideAttrOnCat();
    showAttrIfActive();
    adjustShopByActiveParams();


    // On click dynamic

    $("body").on('change','.woocommerce-ordering > .clone',function(){
        var activeParams = getParams();
        if (activeParams['ajax'] == "true") {
            activeParams['orderby'] = $(this).val();

            var nav = $('#loop-products').data('nav');

            if (nav != 'pagination' && activeParams['page']) {
                activeParams['page'] = '';
            }

            addLoading();
            eventHandler(activeParams);
        }
    });

    

    $('.sale-products').on('click',function(){

        var $this  = $(this),
            data   = new Object;

        $this.toggleClass('chosen');


        var activeParams = getParams();

        if (activeParams) {
            data = activeParams;
        }

        data['onsale'] = ($this.hasClass('chosen')) ? 1 : '';

        addLoading();
        eventHandler(data);

    });

    $("body").on('click','.page-numbers a',function(e){
        var activeParams = getParams();
        if (activeParams['ajax'] == "true") {

            e.preventDefault();

            var link = $(this);
            var current = (typeof(activeParams['page']) != 'undefined' && activeParams['page'] != null) ? parseInt(activeParams['page']) : 1;

            activeParams['page'] = (link.hasClass('next')) ? current + 1 : (link.hasClass('prev')) ? current - 1 : (typeof(link.attr('data-page')) != 'undefined') ? link.data('page') : parseInt(link.attr('href').split('/').filter(Boolean).pop());


            addLoading();
            ajaxHandler(activeParams);

        }
    });

    $("body").on('keyup','input.attribute-search',function(e){
        var filter = $(this).val();
        $(this).next('ul').find('li').each(function(){
            if ($(this).find('a').attr('title').search(new RegExp(filter, "i")) < 0) {
                $(this).hide(0);
            } else {
                $(this).show();
            }
        });
    });

    $("body").on('click','.filter-breadcrumbs .share > a',function(e){
        e.preventDefault();
        $(this).parent().toggleClass('show');
    });
    
    if ($("#loadmore").length) {

        $("#loadmore").on('click',function(e){
            e.preventDefault();
            var activeParams = getParams();
            if (activeParams) {
                if (activeParams['ajax'] == "true") {

                    $(this).addClass('loading');

                    var current = (typeof(activeParams['page']) != 'undefined' && activeParams['page'] != null) ? parseInt(activeParams['page']) : 1;

                    activeParams['page'] = current + 1;

                    ajaxHandler(activeParams);

                }
            }
        });

    }else if ($("#infinite").length) {

        var infinite = $("#infinite");

        $(window).scroll(function(){

            if (!infinite.hasClass('disable') && !infinite.hasClass('hidden')) {

                var activeParams = getParams();

                if (activeParams) {
                    if (activeParams['ajax'] == "true") {
                        if  (infinite.inView()){
                            infinite.addClass('loading');

                            var current = (typeof(activeParams['page']) != 'undefined' && activeParams['page'] != null) ? parseInt(activeParams['page']) : 1;

                            activeParams['page'] = current + 1;

                            ajaxHandler(activeParams);
                        }
                    }
                }

            }
        });

    }

    window.addEventListener('popstate', function(event){
        var activeParams = getParams();
        if (activeParams && activeParams['ajax'] == "true") {
            event.preventDefault();
            history.pushState({}, null, shopURLOriginal);
            window.location.assign(shopURLOriginal);
        }
    });

})(jQuery);