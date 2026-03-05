function lazyLoad(container){

    if (container != null) {

        let lazyImages = [].slice.call(container.querySelectorAll("img.lazy"));
        let lazyVideos = [].slice.call(container.querySelectorAll("video.lazy"));

        if ("IntersectionObserver" in window) {

            // Images

                let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            let lazyImage = entry.target;
                            lazyImage.src = lazyImage.dataset.src;

                            if (lazyImage.classList.contains('single') && window.innerWidth < 768) {
                                let respImg = lazyImage.getAttribute('data-img-resp');
                                respImg = respImg.split('|');
                                lazyImage.src = respImg[0];
                                lazyImage.setAttribute('width',respImg[1]);
                                lazyImage.setAttribute('height',respImg[2]);
                            }

                            lazyImage.onload = function() {
                                lazyImage.classList.remove("lazy");
                                lazyImage.parentElement.classList.add("loaded");
                                lazyImageObserver.unobserve(lazyImage);
                            };
                            
                        }
                    });
                });

                lazyImages.forEach(function(lazyImage) {
                    lazyImageObserver.observe(lazyImage);
                });

            // Videos

                let lazyVideoObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(video) {
                        if (video.isIntersecting) {

                            for (var source in video.target.children) {
                                var videoSource = video.target.children[source];
                                if (typeof videoSource.tagName === "string" && videoSource.tagName === "SOURCE") {
                                    videoSource.src = videoSource.dataset.src;
                                }
                            }

                            video.target.load();
                            video.target.classList.remove("lazy");
                            lazyVideoObserver.unobserve(video.target);
                        }
                    });
                });

                lazyVideos.forEach(function(lazyVideo) {
                    lazyVideoObserver.observe(lazyVideo);
                });

        } else {

            let active = false;

            const lazyLoad = function() {
                if (active === false) {

                    active = true;

                    setTimeout(function() {

                        lazyImages.forEach(function(lazyImage) {

                            if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyImage).display !== "none") {

                                lazyImage.src = lazyImage.dataset.src;

                                lazyImage.onload = function() {
                                    lazyImage.classList.remove("lazy");
                                    lazyImage.parentElement.classList.add("loaded");
                                    lazyImages = lazyImages.filter(function(image) {
                                        return image !== lazyImage;
                                    });
                                };

                                if (lazyImages.length === 0) {
                                    document.removeEventListener("scroll", lazyLoad);
                                    window.removeEventListener("resize", lazyLoad);
                                    window.removeEventListener("orientationchange", lazyLoad);
                                }
                            }
                        });

                        lazyVideos.forEach(function(lazyVideo) {

                            if ((lazyVideo.getBoundingClientRect().top <= window.innerHeight && lazyVideo.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyVideo).display !== "none") {

                                for (var source in lazyVideo.children) {
                                    var videoSource = lazyVideo.children[source];
                                    if (typeof videoSource.tagName === "string" && videoSource.tagName === "SOURCE") {
                                        videoSource.src = videoSource.dataset.src;
                                    }
                                }

                                if (lazyVideos.length === 0) {
                                    document.removeEventListener("scroll", lazyLoad);
                                    window.removeEventListener("resize", lazyLoad);
                                    window.removeEventListener("orientationchange", lazyLoad);
                                }
                            }
                        });

                        active = false;

                    }, 200);
                }
            };

            document.addEventListener("scroll", lazyLoad);
            window.addEventListener("resize", lazyLoad);
            window.addEventListener("orientationchange", lazyLoad);

        }

    }

}

function onCompareWishlistAddToCartComplete(){
	jQuery('.dashboard-tabs').addClass('active');
}

function ajaxAddToCart(){
	jQuery('.loop-products .product, ul.products .product').each(function(){

		var $this = jQuery(this);
		var addToCard = $this.find('.ajax_add_to_cart');
		var productProgress = $this.find('.ajax-add-to-cart-loading');
		var addToCardEvent  = true;

		if (addToCard.hasClass('added')) {
			addToCardEvent  = false;
		}

		if (addToCard.attr('data-product_status') == 'outofstock') {
			addToCardEvent  = false;
		}

		if (addToCard.attr('data-product_type') == 'variable') {
			addToCardEvent  = false;
		}

		addToCard.on('click',function(){

			if (addToCardEvent == true) {
				var $self = jQuery(this);

				productProgress.addClass('active');
				setTimeout(function(){
					productProgress.addClass('load-complete');
					gsap.to(productProgress.find('.tick'),0.2, {
					  opacity:1,
					});
					gsap.to(productProgress.find('.tick'),0.8, {
					  scale:1.15,
					  ease:"elastic.out"
					});
					setTimeout(function(){
						productProgress.removeClass('active').removeClass('load-complete');
						addToCardEvent  = false;
					}, 500);

					onCompareWishlistAddToCartComplete();
				}, 1500);
			} else {
				alert(controller_opt.already);
			}
		});
	});
}

(function($){

    "use strict";

    function unique(array){
        return array.filter(function (value, index, self) {
            return self.indexOf(value) === index;
        });
    }

    function isInArray(value, array) {return array.indexOf(value) > -1;}

    function wishlistCountUpdate(mult){
        var wishlist_count = parseInt($('.wishlist-contents').html());
        wishlist_count += mult;
        if (wishlist_count < 0) {wishlist_count = 0;}
        $('.wishlist-contents').html(wishlist_count);
        if (wishlist_count > 0) {
            $('.wishlist-contents').addClass('count');
        } else {
            $('.wishlist-contents').removeClass('count');
        }
    }

    function onWishlistComplete(target, title){

        setTimeout(function(){

            target
            .removeClass('loading')
            .addClass('active')
            .attr('title',title);
            target.next('.wishlist-title').html(title);

            wishlistCountUpdate(1);

        },800);
    }

    function processWishlist(table = true){

        if (wishlist.length) {

            $('.wishlist-contents').addClass('count').html(wishlist.length);

            if (table && $('.wishlist-table').length) {

                $.ajax({
                    url:wishlist_opt.ajaxUrl,
                    type: 'post',
                    data: { action: 'wishlist_fetch', wishlist:wishlist.join(',')},
                    success: function(data) {
                        $('.wishlist-table').each(function(){
                            $(this).html(data).removeClass('loading');
                            lazyLoad(this);
                            ajaxAddToCart();
                        });
                    },
                    fail:function(){
                        $('.wishlist-table').each(function(){
                            $(this).removeClass('loading');
                        });
                    }
                });

            }

        }
    }

    function wishlistToggle(wishlist){

        if (typeof(wishlist) != "undefined" && wishlist != null) {

            $('.wishlist-toggle').each(function(){

                var $this = $(this);

                var currentProduct = $this.attr('data-product');

                currentProduct = currentProduct.toString();

                if (!loggedIn && isInArray(currentProduct,wishlist)) {
                    $this.addClass('active').attr('title',inWishlist);
                    $this.next('.wishlist-title').html(addedWishlist).attr('title',inWishlist);
                }
                
            });

        }
    }

    var shopName       = wishlist_opt.shopName+'-wishlist',
        inWishlist     = wishlist_opt.inWishlist,
        addedWishlist  = wishlist_opt.addedWishlist,
        wishlist   = new Array,
        cookie     = $.cookie(shopName),
        loggedIn   = ($('body').hasClass('logged-in')) ? true : false,
        userData   = '';

    if (typeof(cookie) != 'undefined' && cookie != null) {
        if (cookie.length) {
            cookie = cookie.split(',');
            cookie = unique(cookie);
            wishlist = cookie;
        }
    }

    if(loggedIn) {

        // Fetch current user data
        $.ajax({
            type: 'POST',
            url: wishlist_opt.ajaxUrl,
            data: {
                'action' : 'fetch_user_data',
                'dataType': 'json'
            },
            success:function(data) {

                userData = JSON.parse(data);
                if (typeof(userData['wishlist']) != 'undefined' && userData['wishlist'] != null && userData['wishlist'] != "") {

                    var userWishlist = userData['wishlist'];
                    userWishlist = userWishlist.split(',');

                    if (wishlist.length) {

                        wishlist =  wishlist.concat(userWishlist);
                        wishlist = unique(wishlist);

                        $.ajax({
                            type: 'POST',
                            url:wishlist_opt.ajaxPost,
                            data:{
                                action:'user_wishlist_update',
                                user_id :userData['user_id'],
                                wishlist :wishlist.join(',')
                            },
                            success:function() {
                                processWishlist();
                            }
                        });

                    } else {
                        wishlist = userWishlist;
                        processWishlist();
                    }

                    $.removeCookie(shopName, { path: '/' });


                } else {

                    if (wishlist.length) {
                        $.ajax({
                            type: 'POST',
                            url:wishlist_opt.ajaxPost,
                            data:{
                                action:'user_wishlist_update',
                                user_id :userData['user_id'],
                                wishlist :wishlist.join(',')
                            },
                            success:function() {
                                processWishlist();
                                $.removeCookie(shopName, { path: '/' });
                            }
                        });
                    }  else if ($('.wishlist-table').length) {
                        $('.wishlist-table').removeClass('loading').html(wishlist_opt.noWishlist);
                    }
                    
                }
            },
            error: function(){
                console.log('No user data returned');
            }
        });
    } else {
        processWishlist(false);
    }

    wishlistToggle();

    $(document).on('click', '.wishlist-toggle', function(e){

        let $this          = $(this);
        let currentProduct = $this.data('product');

        currentProduct = currentProduct.toString();

        if (!$this.hasClass('active') && !$this.hasClass('loading')) {
            e.preventDefault();
            $this.addClass('loading');

            wishlist.push(currentProduct);
            wishlist = unique(wishlist);

            if (loggedIn) {
                // get user ID
                if (userData['user_id']) {
                    $.ajax({
                        type: 'POST',
                        url:wishlist_opt.ajaxPost,
                        data:{
                            action:'user_wishlist_update',
                            user_id :userData['user_id'],
                            wishlist :wishlist.join(','),
                        }
                    })
                    .done(function(response) {
                        onWishlistComplete($this, inWishlist);
                    })
                    .fail(function(data) {
                        alert(wishlist_opt.error);
                    });
                }
            } else {

                $.cookie(shopName,wishlist.toString(),{expires: 90,path: '/'});

                onWishlistComplete($this, inWishlist);

            }

            // Wishlist count

            $.ajax({
                type: 'POST',
                url:wishlist_opt.ajaxPost,
                data:{
                    action:'wishlist_count_update',
                    product_id :currentProduct,
                }
            })
            .done(function() {
                var wishlistCount = $('.single-product .wishlist-count').html();
                wishlistCount++;
                $('.single-product .wishlist-count').html(wishlistCount);
            });

        }
    });

    $(document).on('click', '.wishlist-remove', function(e){

        e.preventDefault();

        if (confirm(wishlist_opt.confirm)) {

            var $this = $(this);

            $this.closest('.wishlist-table').addClass('loading');

            wishlist = [];

            $this.closest('.wishlist-table').find('li').each(function(){
                if ($(this).data('product') != $this.closest('li').data('product')) {
                    wishlist.push($(this).attr('data-product'));
                }
            });

            if (loggedIn) {
                // get user ID
                if (userData['user_id']) {
                    $.ajax({
                        type: 'POST',
                        url:wishlist_opt.ajaxPost,
                        data:{
                            action:'user_wishlist_update',
                            user_id :userData['user_id'],
                            wishlist :wishlist.join(','),
                        }
                    })
                    .done(function() {
                        $this.closest('li').remove();
                        if (wishlist.length == 0) {
                            $('.wishlist-table.loading').html(wishlist_opt.noWishlist);
                        }
                        $('.wishlist-table.loading').removeClass('loading');
                        wishlistCountUpdate(-1);

                    })
                    .fail(function() {
                        alert(wishlist_opt.error);
                    });
                }
            } else {

                $.cookie(shopName,wishlist.toString(),{expires: 90,path: '/'});

                setTimeout(function(){
                    $this.closest('li').remove();
                    if (wishlist.length == 0) {
                        $('.wishlist-table.loading').append(wishlist_opt.noWishlist);
                    }
                    $('.wishlist-table.loading').removeClass('loading');
                    wishlistCountUpdate(-1);
                },500);
            }

        }

    });

    $('body').on('click','.comp .wishlist-title, .entry-summary .wishlist-title',function(){
        var $this = $(this);
        if ($this.html() != inWishlist) {
            $this.prev('.wishlist-toggle').trigger('click');
        } else {
            window.location.replace($this.prev('.wishlist-toggle').attr('href'));
        }
    });

    let actions = [
        'quick_view',
        'woo_products_ajax',
        'megamenu_load',
        'footer_load',
        'filter_attributes'
    ];

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

            if(actions.includes(dataObj['action'])){
                wishlistToggle(wishlist);
            }

        }
    });

})(jQuery);