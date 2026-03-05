(function($){

    "use strict";


    function unique(array){
        return array.filter(function (value, index, self) {
            return self.indexOf(value) === index;
        });
    }

    function isInArray(value, array) {return array.indexOf(value) > -1;}

    function compareCountUpdate(mult){
        
        if(!$('.sticky-dashboard.manual .et-compare-icon .compare-contents').length){
            $('.sticky-dashboard.manual .et-compare-icon').prepend('<span class="compare-contents" />');
        }

        $('.compare-contents').each(function(){

            var compare_count = ($(this).html()) ? parseInt($(this).html()) : 0;

            if (mult == 0) {
                $(this).removeClass('count').html('0');
                $(this).parents('li.compare').addClass('hidden');
            } else {
                compare_count += mult;
                if (compare_count < 0) {compare_count = 0;}
                    $(this).html(compare_count);
                if (compare_count > 0) {
                    $(this).addClass('count');
                    $(this).parents('li.compare').removeClass('hidden');
                } else {
                    $(this).removeClass('count');
                    $(this).parents('li.compare').addClass('hidden');
                }
            }


        });

        
        
    }

    function responsiveCompareTable(){

        let columnModal  = parseInt($('.cbt-wrapper.modal .compare-table').attr('data-length'));
        let columnSingle = parseInt($('.cbt-wrapper.single .compare-table').attr('data-length'));

        if (window.matchMedia("(max-width: 374px)").matches) {

            if (columnModal > 2) {
                $('.compare-table-wrapper.modal').addClass('max');
                $('.cbt-wrapper.modal .compare-table').css({
                    'width':((columnModal*100)/2)+'%'
                });
            }

            if (columnSingle > 2) {
                $('.compare-table-wrapper.single').addClass('max');
                $('.cbt-wrapper.single .compare-table').css({
                    'width':((columnSingle*100)/2.2)+'%'
                });
            }

        } else if (window.matchMedia("(max-width: 767px)").matches) {

            if (columnModal > 2) {
                
                $('.compare-table-wrapper.modal').addClass('max');
                $('.cbt-wrapper.modal .compare-table').css({
                    'width':((columnModal*100)/2)+'%'
                });
            }

            if (columnSingle > 2) {
                $('.compare-table-wrapper.single').addClass('max');
                $('.cbt-wrapper.single .compare-table').css({
                    'width':((columnSingle*100)/2.4)+'%'
                });
            }

        } else if (window.matchMedia("(min-width: 768px) and (max-width: 1023px)").matches) {

            if (columnModal > 3) {
                $('.compare-table-wrapper.modal').addClass('max');
                $('.cbt-wrapper.modal .compare-table').css({
                    'width':((columnModal*100)/3)+'%'
                });
            }

            if (columnSingle > 2) {
                $('.compare-table-wrapper.single').addClass('max');
                $('.cbt-wrapper.single .compare-table').css({
                    'width':((columnSingle*100)/3.4)+'%'
                });
            }

        }  else if (window.matchMedia("(min-width: 1024px) and (max-width: 1279px)").matches) {
            
            if (columnModal > 4) {
                $('.compare-table-wrapper.modal').addClass('max');
                $('.cbt-wrapper.modal .compare-table').css({
                    'width':((columnModal*100)/4)+'%'
                });
            }

            let inc = $('.cbt-wrapper.single').hasClass('sidebar-active') ? 3 : 4;

            if (columnSingle > inc) {
                $('.compare-table-wrapper.single').addClass('max');
                $('.cbt-wrapper.single .compare-table').css({
                    'width':((columnSingle*100)/(inc + 0.4))+'%'
                });
            }

        } else {

            if (columnModal > 5) {
                $('.compare-table-wrapper.modal').addClass('max');
                $('.cbt-wrapper.modal .compare-table').css({
                    'width':((columnModal*100)/6)+'%'
                });
            } else {
                $('.compare-table-wrapper.modal').removeClass('max');
            }

            let inc = $('.cbt-wrapper.single').hasClass('sidebar-active') ? 4 : 5;

            if (columnSingle > inc) {
                $('.compare-table-wrapper.single').addClass('max');
                $('.cbt-wrapper.single .compare-table').css({
                    'width':((columnSingle*100)/(inc + 1))+'%'
                });
            } else {
                $('.compare-table-wrapper.single').removeClass('max');
            }

        }

        
    }

    function fetchCompareProducts(compare){

        if (compare.length && !$('.compare-table-wrapper.modal').length) {

            $('.quick-view-wrapper-close').trigger('click');
            $('.sticky-dashboard').removeClass('active');

            if (!$('.compare-table-wrapper-shadow').length) {
                $('body').append('<div class="compare-table-wrapper-shadow loading"></div>');
            }
        
            $.ajax({
                url:compare_opt.ajaxUrl,
                type: 'post',
                data: { action: 'compare_products_fetch', compare:compare.join(','), aj:'true'},
                success: function(data) {
                    if (data != 0) {
                        $('.compare-table-wrapper-shadow').removeClass('loading');
                        $(data).insertAfter('.compare-table-wrapper-shadow');
                        responsiveCompareTable();
                    } else {
                        $('.compare-table-wrapper-shadow').next().remove();
                        $('.compare-table-wrapper-shadow').remove();
                    }
                },
                fail:function(){
                    $('.compare-table-wrapper-shadow').removeClass('loading');
                    alert(compare_opt.noCompare);
                }
            });

        } else {
            $('.compare-table-wrapper-shadow').next().remove();
            $('.compare-table-wrapper-shadow').remove();
        }
    }

    function animateProductFlow(toggle){

        if ($('.sticky-dashboard').hasClass('hidden')) {

            alert(compare_opt.inCompare);

        } else {

            let clone   = toggle.parents('li.product').find('.image-container.loaded img').clone().addClass('compare-product-animate'),
                posL    = toggle.parents('li.product').find('.image-container.loaded img')[0].getBoundingClientRect().left,
                posT    = toggle.parents('li.product').find('.image-container.loaded img')[0].getBoundingClientRect().top,
                Iwidth  = toggle.parents('li.product').find('.image-container.loaded img').width(),
                Iheight = toggle.parents('li.product').find('.image-container.loaded img').height();

            $('body').append(clone);

            $('.compare-product-animate').css({
                'transform':'translate('+posL+'px,'+posT+'px)',
                'width':Iwidth,
                'height':Iheight
            });

            $('.sticky-dashboard li.compare').removeClass('hidden');

            gsap.to('.compare-product-animate', {
                x:$('.sticky-dashboard .compare')[0].getBoundingClientRect().left,
                y:$('.sticky-dashboard .compare')[0].getBoundingClientRect().top,
                scale:0.3,
                opacity:0
            });

            setTimeout(function(){
                compareCountUpdate(1);
                $('.compare-product-animate').remove();
            },500);

        }
    }


    $(window).resize(responsiveCompareTable);

    var shopName      = compare_opt.shopName+'-compare',
        inCompare     = compare_opt.inCompare,
        addedCompare  = compare_opt.addedCompare,
        compare       = new Array,
        ls            = localStorage.getItem(shopName),
        sidebar       = $('.compare-table-single').data('sidebar');
        sidebar       = (sidebar == "none") ? false : true;

    var addToCart = $('.single_variation_wrap').length ? $('.single_variation_wrap') : $('form.cart:not(.variations_form)');
    

    if (typeof(ls) != 'undefined' && ls != null) {
        if (ls.length) {
            ls = ls.split(',');
            ls = unique(ls);
            compare = ls;
        }
    }

    if (compare.length) {
        $('.compare-contents').html(compare.length);
        $('.compare-contents').addClass('count');
        $('.sticky-dashboard .compare').removeClass('hidden');
    } else {
        $('.sticky-dashboard .compare').addClass('hidden');
    }

    $('.et-compare-icon').on('click',function(e){

        if (addToCart.length) {
            addToCart.addClass('transform');
        }

        e.preventDefault();
        fetchCompareProducts(compare);
    });

    $(document).on('click', '.compare-title', function(e){
        $(this).prev('.compare-toggle').trigger('click');
    });

    $(document).on('click', '.cbt-nav a', function(e){
        e.preventDefault();
        var $this = $(this),
            direction = $this.hasClass('next') ? 1 : 0,
            scrollSize = $this.parent().next().find('td').outerWidth();
            scrollSize = (direction) ? scrollSize : -1*scrollSize;
            scrollSize = Math.round(scrollSize + $this.parent().next().scrollLeft());

        $this.parent().find('.disabled').removeClass('disabled');

        gsap.to($this.parent().next(), {
          scrollTo: {
            x: scrollSize,
            autoKill: false,
          },
          duration: 0.2
        });

    });

    $(document).on('click', '.compare-toggle', function(e){

        e.preventDefault();

        var $this = $(this);

        var currentProduct = $this.data('product');

        currentProduct = currentProduct.toString();

        if (!isInArray(currentProduct,compare)) {

            $this.addClass('loading');

            compare.push(currentProduct);
            compare = unique(compare);
            localStorage.setItem(shopName, compare.toString());

            setTimeout(function(){

                $this.removeClass('loading').addClass('active');

                if (!$this.parents('.summary-details').length) {
                    animateProductFlow($this);
                } else {
                    compareCountUpdate(1);
                }

            },500);

        } else {
            fetchCompareProducts(compare);
        }

    });

    $(document).on('click', '.compare-remove', function(e){

        e.preventDefault();

        if (compare.length && confirm(compare_opt.confirm)) {

            var $this = $(this);

            var index = compare.indexOf($this.attr('data-product'));

            if (index > -1) {
                compare.splice(index,1);
            }

            if (compare.length) {
                localStorage.setItem(shopName, compare.toString());
            } else {
                localStorage.removeItem(shopName);
            }

            $('.compare-table-wrapper').remove();
            $('.compare-table-wrapper-shadow').addClass('loading');

            fetchCompareProducts(compare);
            compareCountUpdate(-1);

        }

    });

    $(document).on('click', '.compare-table-toggle, .compare-table-wrapper-shadow', function(e){
        $('.compare-table-wrapper-shadow').next().remove();
        $('.compare-table-wrapper-shadow').remove();
        $('.sticky-dashboard').addClass('active');
        if (addToCart.length) {
            addToCart.removeClass('transform');
        }
    });

    $(document).on('click', '.compare-table-wrapper .clear', function(e){
        e.preventDefault();
        if (addToCart.length) {
            addToCart.removeClass('transform');
        }
        $('.sticky-dashboard').addClass('active');
        $('.compare-table-wrapper-shadow').next().remove();
        $('.compare-table-wrapper-shadow').remove();
        localStorage.removeItem(shopName);
        compareCountUpdate(0);
        compare = [];
    });

})(jQuery);