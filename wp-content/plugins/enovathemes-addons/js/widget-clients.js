class ElementorWidgetClients extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               carousel: '.et-clients.swiper-container',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $carousel: this.$element.find( selectors.carousel ),
        };
    }

    bindEvents() {

        if (this.elements.$carousel.length) {

            var $scope    = this.elements.$carousel,
                id        = $scope.find('.swiper').attr('id'),
                desktop   = $scope.data('carousel-columns'),
                mobile    = $scope.data('carousel-mobile-columns'),
                tabletP   = $scope.data('carousel-tablet-portrait-columns'),
                tabletL   = $scope.data('carousel-tablet-landscape-columns'),
                gatter    = $scope.data('carousel-gatter'),
                autoplay  = $scope.data('carousel-autoplay'),
                navType   = $scope.data('carousel-navigation-type'),
                navPos    = $scope.data('carousel-navigation-position');

            desktop  = (typeof(desktop) == 'undefined') ? 6 : desktop;
            mobile   = (typeof(mobile) == 'undefined') ? 1 : mobile;
            tabletP  = (typeof(tabletP) == 'undefined') ? 2 : tabletP;
            tabletL  = (typeof(tabletL) == 'undefined') ? 3 : tabletL;
            gatter   = (typeof(gatter) == 'undefined') ? 0 : gatter;
            var gatterM  = gatter > 8 ? 8 : gatter;
            autoplay = (typeof(autoplay) == 'undefined') ? false : autoplay;

            var config = {
                pagination: {
                    el: '#'+$scope.find('.swiper-pagination').attr('id'),
                    clickable: true,
                    renderBullet: function (index, className) {
                        return '<span class="' + className + '"></span>';
                    },
                },
                navigation: {
                    nextEl: '#'+$scope.find('.swiper-button-next').attr('id'),
                    prevEl: '#'+$scope.find('.swiper-button-prev').attr('id'),
                },
                spaceBetween: gatter,
                slidesPerView: desktop,
                grabCursor: true,
                autoHeight: false,
                direction:'horizontal',
                breakpoints: {
                    200: {
                        slidesPerView: mobile + 0.2,
                        spaceBetween: gatterM,
                    },
                    375: {
                        slidesPerView: mobile + 0.3,
                        spaceBetween: gatterM,
                    },
                    425: {
                        slidesPerView: mobile + 0.4,
                        spaceBetween: gatterM,
                    },
                    540: {
                        slidesPerView: mobile + 0.6,
                        spaceBetween: gatterM,
                    },
                    768: {
                        slidesPerView: tabletP + 0.4,
                    },
                    1024: {
                        slidesPerView: tabletL,
                    },
                    1280: {
                        slidesPerView: desktop,
                    }
                }
            };

            if (typeof(autoplay) != "undefined" && autoplay == true) {
                config['autoplay'] = {
                    delay: 2000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true
                };
            }

            var swiper = new Swiper('#'+id, config);

        }


    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_clients', ElementorWidgetClients );
});