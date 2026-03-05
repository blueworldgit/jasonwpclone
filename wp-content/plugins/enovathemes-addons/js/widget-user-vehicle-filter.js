(function($){

    "use strict";

    var shopURL        = user_vehicle_filter_opt.shopURL,
        ajaxUrl        = user_vehicle_filter_opt.ajaxUrl,
        vehicleParams  = JSON.parse(user_vehicle_filter_opt.vehicleParams),
        addMoreClose   = user_vehicle_filter_opt.close,
        addMoreDefault = user_vehicle_filter_opt.addmore,
        vehicleList    = false;

        vehicleParams.push('yr');
        vehicleParams.push('vin');

    function fetchVehicleList(){

        if (vehicleList) {
            $('form.user-vehicle-filter .vf-item:first-child select').removeAttr("disabled");
            $('form.user-vehicle-filter select').each(function(){

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
        } else {
            $.ajax({
                url:ajaxUrl,
                type: 'post',
                data: {'action':'fetch_vehicle_list'},
                success: function(data) {
                    try {
                        if(data){

                            vehicleList = JSON.parse(data);
                            $('form.user-vehicle-filter .vf-item:first-child select').removeAttr("disabled");
                            $('form.user-vehicle-filter select').each(function(){

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
                    console.log(user_vehicle_filter_opt.error);
                }
            });
        }

    }

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

            uniqueValues.sort();
            
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

    function removeVehicle(form,vehicle){

        if (confirm(user_vehicle_filter_opt.removeVehicleMessage)) {

            if ($('body').hasClass('logged-in')) {

                form.parent().addClass('loading');
                
                $.ajax({
                    url:ajaxUrl,
                    type: 'post',
                    data: {
                        'action':'remove_user_vehicle_list',
                        'nonce':form.find('input[name="nonce"]').val(),
                        'vehicle':vehicle
                    },
                    success: function(data) {

                        try {

                            form.parent().removeClass('loading');

                            if(data){
                                $('.user-vehicle-filter').next('.user-vehicle-list').find('li[data-vehicle="'+data+'"]').remove();
                            }

                            if (!form.next('.user-vehicle-list').children().length) {
                                $('.user-vehicle-filter').prev('.add-more').trigger('click').addClass('hidden');
                            }

                        } catch(e) {
                            console.log(e);
                        }
                    },
                    error:function () {
                        console.log(user_vehicle_filter_opt.error);
                    }
                });

            } else {

                if(typeof($.cookie('user-vehicles')) != 'undefined' && $.cookie('user-vehicles') != null){

                    var cookieUserVehicles = $.cookie('user-vehicles');

                    cookieUserVehicles = cookieUserVehicles.split('|');
                    
                    let vehicleAtob = atob(vehicle);

                    if (cookieUserVehicles.includes(vehicleAtob)) {

                        cookieUserVehicles.splice(cookieUserVehicles.indexOf(vehicleAtob), 1);

                        if (cookieUserVehicles.length) {
                            $.cookie('user-vehicles',cookieUserVehicles.join('|'),{expires: 90,path: '/'});
                        } else {
                            $.removeCookie('user-vehicles', { path: '/' });
                        }

                        $('.user-vehicle-filter').next('.user-vehicle-list').find('li[data-vehicle="'+vehicle+'"]').remove();

                        $('.user-vehicle-filter').each(function(){
                            if (!$(this).next('.user-vehicle-list').children().length) {
                                $(this).parent().find('.login-to-save').remove();
                                $(this).removeClass('hidden');
                                $(this).prev('.add-more').addClass('hidden');
                            }
                        });

                        

                    }

                }
                
            }

        }
    }

    function saveVehicle(form,data,guest = false){

        let vehicle = '', alertError = true;

        if (Array.isArray(data)) {
            vehicle = data;
            alertError = false;
        } else {
            vehicle = JSON.stringify(data);
        }

        $.ajax({
            url:ajaxUrl,
            type: 'post',
            data: {
                'action':'save_user_vehicle_list',
                'vehicle':vehicle,
                'nonce':form.find('input[name="nonce"]').val(),
            },
            success: function(data) {
                try {

                    data = JSON.parse(data);

                    $('.user-vehicle-filter').parent().removeClass('loading');
                    $('.user-vehicle-filter').parent().find('.login-to-save').remove();

                    if(data['output']){

                        $('.user-vehicle-filter').next('.user-vehicle-list').prepend(data['output']);

                        setTimeout(function(){
                            $('.user-vehicle-filter').next('.user-vehicle-list').find('.new').removeClass('new').addClass('added');
                        },100);

                        setTimeout(function(){
                            $('.user-vehicle-filter').next('.user-vehicle-list').find('.added').removeClass('added');
                        },1000);

                        $('.user-vehicle-filter').prev('.add-more').removeClass('hidden').text(addMoreDefault);
                        $('.user-vehicle-filter').addClass('hidden');
                        $('.user-vehicle-filter').next('.user-vehicle-list').removeClass('hidden');

                    } else {
                        if (alertError) {
                            alert(data['error']);
                        }
                    }

                    $('.user-vehicle-filter').find('.reset').trigger('click');

                    if (guest) {

                        if (data['vehicle']) {

                            if(typeof($.cookie('user-vehicles')) != 'undefined' && $.cookie('user-vehicles') != null){

                                var cookieUserVehicles = $.cookie('user-vehicles');

                                cookieUserVehicles = cookieUserVehicles.split('|');
                                cookieUserVehicles.push(JSON.stringify(data['vehicle']));
                                cookieUserVehicles = [...new Set(cookieUserVehicles)];

                                $.cookie('user-vehicles',cookieUserVehicles.join('|'),{expires: 90,path: '/'});

                            } else {

                                $.cookie('user-vehicles',JSON.stringify(data['vehicle']),{expires: 90,path: '/'});

                            }

                        }

                        if (!$('.user-vehicle-filter').next('.user-vehicle-list').next('.login-to-save').length) {
                            $('<p class="login-to-save">'+user_vehicle_filter_opt.login+'</p>').insertAfter(form.next('.user-vehicle-list'));
                        }

                    } else {
                        $.removeCookie('user-vehicles', { path: '/' });
                    }


                } catch(e) {
                    console.log(e);
                }
            },
            error:function () {
                console.log(user_vehicle_filter_opt.error);
            }
        });

    }

    function afterSetVehicle(form,fetch = false){

        $('.user-vehicle-filter').each(function(){
            if (!$(this).next('.user-vehicle-list').next('.login-to-save').length) {
                $('<p class="login-to-save">'+user_vehicle_filter_opt.login+'</p>').insertAfter($(this).next('.user-vehicle-list'));
            }
        });

        $('.user-vehicle-filter').parent().removeClass('loading');
        $('.user-vehicle-filter').prev('.add-more').removeClass('hidden').text(addMoreDefault);
        $('.user-vehicle-filter').addClass('hidden');
        $('.user-vehicle-filter').next('.user-vehicle-list').removeClass('hidden');

        setTimeout(function(){
            $('.user-vehicle-filter').next('.user-vehicle-list').find('.new').removeClass('new').addClass('added');
        },100);

        setTimeout(function(){
            $('.user-vehicle-filter').next('.user-vehicle-list').find('.added').removeClass('added');
        },1000);
    }

    function setVehicle(form,data,cookie = true){

        if (shopURL.indexOf("?") == -1){
            shopURL += '?';
        }

        let vehicle = [];

        if (Array.isArray(data)) {

            let toPrepend = '';

            for (var i = 0; i < data.length; i++) {

                let dt      = JSON.parse(data[i]);
                let thisUrl = shopURL;
                let values  = [];

                $.each(dt, function(key, value) {
                    if (value.length) {
                        if (key == 'year') {key = 'yr';}
                        thisUrl += '&'+key+'='+value;

                        values.push(value);

                        thisUrl = thisUrl.replace('?&', '?');
                        thisUrl = encodeURI(thisUrl);

                    }
                });

                toPrepend += '<li data-vehicle="'+btoa(JSON.stringify(dt))+'"><a href="'+thisUrl+'">'+values.join(', ')+'</a><span class="remove"></span></li>';
            }

            if (toPrepend) {
                $('.user-vehicle-filter').each(function(){
                    if (!$(this).next('.user-vehicle-list').children().length) {
                        $(this).next('.user-vehicle-list')
                        .prepend(toPrepend);
                    }
                });
            }

            afterSetVehicle(form,true);

        } else {

            if (data['vin']) {
                saveVehicle(form,data,true);
            } else {

                $.each(data, function(key, value) {
                    if (value.length) {
                        if (key == 'year') {key = 'yr';}
                        shopURL += '&'+key+'='+value;
                        vehicle.push(value);
                    }
                });

                shopURL = shopURL.replace('?&', '?');
                shopURL = encodeURI(shopURL);

                $('.user-vehicle-filter').next('.user-vehicle-list')
                .prepend('<li class="new" data-vehicle="'+btoa(JSON.stringify(data))+'"><a href="'+shopURL+'">'+vehicle.join(', ')+'</a><span class="remove"></span></li>');

                if (cookie) {
                    if(typeof($.cookie('user-vehicles')) != 'undefined' && $.cookie('user-vehicles') != null){

                        var cookieUserVehicles = $.cookie('user-vehicles');

                        cookieUserVehicles = cookieUserVehicles.split('|');
                        cookieUserVehicles.push(JSON.stringify(data));
                        cookieUserVehicles = [...new Set(cookieUserVehicles)];

                        $.cookie('user-vehicles',cookieUserVehicles.join('|'),{expires: 90,path: '/'});

                    } else {

                        $.cookie('user-vehicles',JSON.stringify(data),{expires: 90,path: '/'});

                    }
                }
            
                afterSetVehicle(form);

            }
        }

        $('.user-vehicle-filter').find('.reset').trigger('click');

    }

    function addVehicle(form,data){

        form.parent().addClass('loading');

        if ($('body').hasClass('logged-in')) {
            saveVehicle(form,data);
        } else {
            setVehicle(form,data);
        }

    }

    function fetchUserVehicle(form){

        form.parent().addClass('loading');
        
        if (!form.next('.user-vehicle-list').children().length) {
            $.ajax({
                url:ajaxUrl,
                type: 'post',
                data: {
                    'action':'fetch_user_vehicle_list',
                    'nonce':form.find('input[name="nonce"]').val(),
                },
                success: function(data) {
                    try {

                        if (data && !form.next('.user-vehicle-list').children().length) {

                            data = JSON.parse(data);

                            form.parent().removeClass('loading');
                            form.parent().find('.login-to-save').remove();

                            if(data['output']){

                                form.next('.user-vehicle-list').removeClass('hidden').prepend(data['output']);
                                form.prev('.add-more').removeClass('hidden');
                                form.addClass('hidden');

                            }

                            form.find('.reset').trigger('click');

                        } else {
                            form.parent().removeClass('loading');
                        }

                    } catch(e) {
                        console.log(e);
                    }
                },
                error:function () {
                    console.log(user_vehicle_filter_opt.error);
                }
            });
        }
    }

    function userVehicleFilter(){

        fetchVehicleList();

        $('form.user-vehicle-filter:not(.initialized)').each(function(){

            var form = $(this).addClass('initialized');

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

            });
     
            form.find('select').each(function(){

                $(this).select2();

                $(this).on('change',function(){

                    $(this).parent().nextAll().find('select').val('').trigger('change.select2');
                    
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

            form.find('input[type="submit"]').unbind('click').on('click',function(e){
                e.preventDefault();

                var data = new Object;
                var vin = form.find('input.vin').val();

                if (vin) {
                    data['vin'] = vin;
                } else {
                    form.find('select').each(function(){
                        if ($(this).val()) {data[$(this).attr('name')] = $(this).val();}
                    });
                }

                if(!$.isEmptyObject(data)){
                    addVehicle(form,data);
                }
                
            });

            form.prev('.add-more').unbind('click').on('click',function(e){
                e.preventDefault();

                $(this).toggleClass('active');

                form.toggleClass('hidden');
                form.next('.user-vehicle-list').toggleClass('hidden');

                if (form.hasClass('hidden')) {
                    $(this).text(addMoreDefault);
                } else {
                    $(this).text(addMoreClose);
                }

            });

            if(typeof($.cookie('user-vehicles')) != 'undefined' && $.cookie('user-vehicles') != null){

                var data = $.cookie('user-vehicles');
                    data = data.split('|');

                addVehicle(form,data);

            } else if(!$('body').hasClass('logged-in')) {
                $('.user-vehicle-filter').parent().removeClass('loading');
            } else if($('body').hasClass('logged-in')) {
                fetchUserVehicle(form);
            }

            $('body').on('click','.user-vehicle-list a',function(e){

                e.preventDefault();

                var $this       = $(this),
                    thisVehicle = $this.parent().attr('data-vehicle');

                if (thisVehicle) {
                    thisVehicle = atob(thisVehicle);
                    thisVehicle = thisVehicle.replace(/\\/g, '');
                    $.cookie('vehicle',thisVehicle,{expires: 90,path: '/'});
                }

                window.location.assign($this.attr('href'));

            });

        });

    }

    userVehicleFilter();

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

        if(dataObj['action'] == "megamenu_load"){
            let response = $.parseJSON(xhr.responseText);

            if (typeof(response) != 'undefined' && response != null) {

                $.each(response, function(key, value) {
                    if (value.length && value.includes('user-vehicle-filter')) {
                        userVehicleFilter();
                    }
                });

            }
            
        }
    });

    $(document).on("click", ".user-vehicle-list li span.remove" , function() {
        removeVehicle($(this).parents('.user-vehicle-list').prev('form'),$(this).parent().attr('data-vehicle'));
    });

})(jQuery);