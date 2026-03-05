(function($){

    "use strict";

    var shopURL     = vehicle_filter_opt.shopURL,
        ajaxUrl     = vehicle_filter_opt.ajaxUrl,
        close       = vehicle_filter_opt.close,
        vehicleParams = JSON.parse(vehicle_filter_opt.vehicleParams),
        vehicleList = false;

        vehicleParams.push('yr');
        vehicleParams.push('vin');

    $.ajax({
        url:ajaxUrl,
        type: 'post',
        data: {'action':'fetch_vehicle_list'},
        success: function(data) {
            try {
                if(data){
                    vehicleList = JSON.parse(data);
                    $('form.product-vehicle-filter .vf-item:first-child select').removeAttr("disabled");
                    $('form.product-vehicle-filter select').each(function(){

                        if ($(this).find('option:selected').not('.default').length) {
                            $(this).removeAttr("disabled");
                        }

                        if (
                            $(this).find('option:selected').not('.default').length && 
                            !$(this).parent().next().find('option:selected').not('.default').length
                        ) {
                            $(this).trigger('change');
                        }
                    });
                }
            } catch(e) {
                console.log(e);
            }
        },
        error:function () {
            console.log(vehicle_filter_opt.error);
        }
    });

    function filterNext(name,value,next,selection){
        var nextValues = [];
        
        Object.values(vehicleList).forEach(val => {

            if(!jQuery.isEmptyObject(selection)){
                var keys = Object.keys(selection),valid = 0;
                
                for (var i = 0; i < keys.length; i++) {

                    let valKeys = val[keys[i]];

                    if(val.hasOwnProperty(keys[i]) && (valKeys == selection[keys[i]] || valKeys.includes(parseInt(selection[keys[i]])))){
                        valid++;
                    }
                }


                if(valid == keys.length){

                    let valName = val[name];

                    if (valName == value  || (Array.isArray(valName) && valName.includes(parseInt(value)))) {

                        if (next == "year") {
                            let years = val[next];
                            for (var i = 0; i < years.length; i++) {
                                nextValues.push(years[i]);
                            }
                        } else {
                            if (val[next]) {
                                nextValues.push(val[next]);
                            }
                        }

                    }

                }
                
            } else {

                let valName = val[name];

                if (valName == value  || (Array.isArray(valName) && valName.includes(parseInt(value)))) {
                    if (next == "year") {
                        let years = val[next];
                        for (var i = 0; i < years.length; i++) {
                            nextValues.push(years[i]);
                        }
                    } else {
                        nextValues.push(val[next]);
                    }
                }
            }
            
        });
        
        if (nextValues) {

            let uniqueValues = [...new Set(nextValues)];

            if (next == "year") {
                uniqueValues.sort((a, b) => b - a);
            } else {
                uniqueValues.sort();
            }

         // console.log(uniqueValues);
            
            var output = '';
            uniqueValues.forEach(function(item, index){
                output += '<option value="'+item+'">'+item+'</option>';
            });
            if (output.length) {
                return output;
            }
        } else {
            return false;
        }
    }

    function selectAttr(name,value,next,selection){
        if (value && typeof(next) != 'undefined' && next != null) {

            var nextValues = filterNext(name,value,next.attr('name'),selection);

            if (nextValues) {
                next.find('option:not(.default)').remove();
                next.nextAll('select').find('option:not(.default)').remove();
                next.append(nextValues);
                next.removeAttr("disabled");
            }
        }
    }

    function clearParams(shopURL) {

        var url = shopURL;
            url = url.split('?');

        var query = url[1];
        var params = '';
        var newshopURL = url[0];

        if (typeof(query) != 'undefined' && query != null) {
            var vars = query.split('&');
            for (var i = 0; i < vars.length; i++) {
                var pair = vars[i].split('=');
                if (vehicleParams && !vehicleParams.includes(pair[0])) {
                    params += '&'+pair[0]+'='+pair[1];
                }
            }

            if (params.length && params.includes('&')) {newshopURL += '?'+params;}

            return newshopURL;
        }

        return false;
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

    function clearURL(shopURL,reload = true){

        var newshopURL = clearParams(shopURL);

        if (newshopURL) {
            shopURL = newshopURL;
        }

        shopURL = shopURL.replace('?&', '?');

        shopURL = encodeURI(shopURL);

        if (reload) {
            window.location.assign(shopURL);
        } else {
            history.pushState({}, null, shopURL);
        }
    }

    function saveVehicle(data){
        $.cookie('vehicle',JSON.stringify(data),{expires: 90,path: '/'});
    }

    function saveFilterData(data){
       localStorage.setItem('vehicleFilterData',JSON.stringify(data));
    }

    function getParams() {

        var url = window.location.href;
            url = url.split('?');

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

    $('form.product-vehicle-filter').each(function(){

        var form = $(this),
            vin = getParams()['vin'],
            currentVehicle = form.attr('data-vehicle');

        if (typeof(vin) != 'undefined' && typeof(currentVehicle) != 'undefined') {
            saveVehicle(JSON.parse(currentVehicle));
        }

        let vehicleToggleText = form.prev('.vehicle-filter-mobile-toggle').text();

        form.prev('.vehicle-filter-mobile-toggle').on('click',function(){
            $(this).toggleClass('active');
            if ($(this).hasClass('active')) {
                $(this).text(close);
            } else {
                $(this).text(vehicleToggleText);
            }
            form.slideToggle();
        });

        $( document ).ajaxComplete(function( event, xhr, settings ) {

                if (settings['type'] != 'POST') {return;}

                var data = decodeURIComponent(settings['data']);

                data = data.split("&");

                var dataObj = [{}];

                for (var i = 0; i < data.length; i++) {
                    var property = data[i].split("=");
                    var key      = (property[0]);
                    var value    = (property[1]);
                    dataObj[key] = value;
                }

                if(dataObj['action'] == "filter_attributes"){
                    let response = $.parseJSON(xhr.responseText);

                    if (typeof(response['vehicle_attributes']) != 'undefined' && response['vehicle_attributes']) {

                        let showReset = false;
                        data = {};

                        $.each(response['vehicle_attributes'], function(key, value) {

                            value = String(value);

                            if (value.length) {


                                if (form.find('select[name="'+key+'"] option[value="'+value+'"]').length) {
                                    showReset = true;
                                    form.find('select[name="'+key+'"]').val(value).trigger('change.select2').removeAttr('disabled');

                                } else {

                                    var newOption = new Option(value, key, true, true);

                                    form.find('select[name="'+key+'"]').append(newOption).trigger('change.select2').removeAttr('disabled');

                                    showReset = true;

                                }

                                if (showReset) {
                                    data[key] = value;
                                }

                            }
                        });

                        if (form.find('input.vin').val()) {
                            showReset = true;
                        }

                        if (showReset) {
                            saveVehicle(data);
                            form.addClass('active');
                        }

                        if (typeof(response['found']) != 'undefined' && response['found'] == '') {
                            $.removeCookie('vehicle', { path: '/' });
                        }
                    }

                    if (typeof(response['vehicle_data']) != 'undefined' && response['vehicle_data']) {

                        let output = '<div class="vin-results"><h5>'+vehicle_filter_opt.vinTitle+'</h5><ul class="vin-decoded-results">';

                        $.each(response['vehicle_data'], function(key, value) {

                            value = String(value);

                            if (value.length) {
                                output += '<li><span>'+key+':</span> <span>'+value+'</span></li>';
                            }
                        });

                        output += '</ul></div>';

                        form.parent().find('.vin-results').remove();
                        form.parent().append(output);
                        
                    } else {
                        form.parent().find('.vin-results').remove();
                    }

                    form.find('.reset').removeClass('clear-all');
                }
        });

        if(typeof($.cookie('vehicle')) != 'undefined' && !$(this).hasClass('active')){
            
            let cookieVehicle           = JSON.parse($.cookie('vehicle'));
            let cookieVehicleFilterData = (typeof(localStorage.getItem('vehicleFilterData')) != 'undefined') ? JSON.parse(localStorage.getItem('vehicleFilterData')) : false;

            $.each(cookieVehicle, function(key, value) {
                if (value.length) {

                    if (form.find('select[name="'+key+'"] [option="'+value+'"]').length) {
                        form.find('select[name="'+key+'"]').removeAttr('disabled').val(value).trigger('change.select2');
                    } else {

                        var toAppend = cookieVehicleFilterData ? cookieVehicleFilterData[key] : '<option value="'+value+'">'+value+'</option>';
                        
                        form.find('select[name="'+key+'"] option').not('.default').remove();
                        form.find('select[name="'+key+'"]').removeAttr('disabled').append(toAppend).val(value).trigger('change.select2');
                    }

                    
                }
            });

        } else {
            var vehicleParamsCore = JSON.parse(vehicle_filter_opt.vehicleParams);

            for (var i = 0; i < vehicleParamsCore.length; i++) {

                var vParamName = vehicleParamsCore[i];
                if (vParamName == 'year') {vParamName = 'yr'}

                var vParam = getParams()[vParamName];

                if (typeof(vParam) != 'undefined') {

                    vParam = decodeURIComponent(vParam);

                    if (form.find('select[name="'+vehicleParamsCore[i]+'"] [option="'+vParam+'"]').length) {
                        form.find('select[name="'+vehicleParamsCore[i]+'"]').removeAttr('disabled').val(value).trigger('change.select2');
                    } else {
                        form.find('select[name="'+vehicleParamsCore[i]+'"]').removeAttr('disabled').append('<option vParam="'+vParam+'">'+vParam+'</option>').val(vParam).trigger('change.select2');
                    }
                }
                
            }
        }


        form.find('.reset').on('click',function(){


            form.removeClass('active');

            form.find('select').each(function(index){
                if (index == 0) {
                    $(this).val('').trigger('change.select2');
                } else {
                    $(this).attr('disabled','disabled').find('option:not(.default)').remove();
                }
            });

            form.find('input.vin').val('');
                
                let activeParams = getParams();

                let reload = ($('.return-to-shop').length || $('.shop-widgets .widget[data-display-type]').length) ? true : false;

                clearURL(window.location.href,reload);

                $.removeCookie('vehicle', { path: '/' });

            if(typeof($.cookie('vehicle')) == 'undefined' && $('.sticky-dashboard').length){
                $('.sticky-dashboard .vehicle-filter-toggle').removeClass('has-vehicle');
            }

        });
 
        form.find('select').each(function(){

            var $this = $(this);

            $(this).select2({
                dir: vehicle_filter_opt.lang,
                dropdownAutoWidth: true,
                dropdownParent:$this.parent()
            });

            $(this).on('change',function(){

                $(this).parent().nextAll().find('select').val('').trigger('change.select2').find('option:not(.default)').remove();
                
                let selection = {};
                
                form.find('select').not($(this)).each(function(){
                    if($(this).val() != ""){
                        selection[$(this).attr('name')] = $(this).val();
                    }
                });

                form.addClass('active');

                if (vehicleList) {
                    selectAttr($(this).attr('name'),$(this).val(),$(this).parents('.vf-item').next().find('select'),selection);
                }

                form.find('input.vin').val('');
            });

        });

        form.find('input[type="submit"]').on('click',function(e){
            e.preventDefault();
            
            var currentUrl = window.location.href;

            if (currentUrl.indexOf("?")) {

                var url = currentUrl.split('?');
                var query = url[1];
                var original = url[0]+'?';
                var params = new Object;

                if (typeof(query) != 'undefined' && query != null) {
                    var vars = query.split('&');
                    for (var i = 0; i < vars.length; i++) {
                        var pair = vars[i].split('=');
                        if (pair[0] != 'vin') {
                            params[pair[0]] = decodeURIComponent(pair[1]);
                        }
                    }
                }

                if (!$.isEmptyObject(params)) {
                    $.each(params, function(key, value) {
                        if (value.length) {
                            original += '&'+key+'='+value;
                        }
                    });

                    currentUrl = original.replace('?&', '?');
                    currentUrl = encodeURI(currentUrl);
                }
                if (currentUrl.includes(shopURL)) {
                    shopURL = currentUrl;
                }

            }

            var data = new Object;
            var filterData = new Object;
            var vin = form.find('input.vin');

            if (vin.length && vin.val()) {
                data['vin'] = vin.val();
                $.removeCookie('vehicle', { path: '/' });

                form.find('select').each(function(){

                    $(this).find('option.default').attr('selected','selected').siblings().removeAttr('selected');
                    $(this).trigger('change');

                });

                form.find('input.vin').val(data['vin']);

            } else {
                form.find('select').each(function(){

                    if ($(this).val()) {data[$(this).attr('name')] = $(this).val();}

                    var optionsHtml = $(this).find('option').not('.default').map(function() {
                        return this.outerHTML;
                    }).get().join('');

                    filterData[$(this).attr('name')] = optionsHtml;
                });
            }

            if(!$.isEmptyObject(data)){

                form.addClass('active');

                let reload = $('.widget_product_filter_widget').length ? false : true;

                if (form.parents('.filter-box').length) {reload = true;}

                if($('.shop-widgets .widget[data-display-type]').length && form.parents('.shop-top-widgets').length){
                    reload = false;
                }

                createURL(shopURL,data,reload);

                if (typeof(data['vin']) == 'undefined') {

                    saveVehicle(data);
                    if (!$.isEmptyObject(filterData)) {
                        saveFilterData(filterData);
                    }
                }

                if (reload == false) {
                    $('.reload-all-attribute').trigger('click');
                }

            } else {
                $.removeCookie('vehicle', { path: '/' });
            }

            
        });
    });

})(jQuery);