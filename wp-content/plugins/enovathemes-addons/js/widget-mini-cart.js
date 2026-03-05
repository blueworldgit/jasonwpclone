function toggleBack(element,toggle,scrollElement){

    var $this  = jQuery(element),
        isOpen = false;

    toggle.unbind('click').on('click',function(){

        toggle.toggleClass('active');

        if (isOpen==false) {

            if (typeof scrollElement != "undefined" && scrollElement != null) {
                // Custom scroll bar
                setTimeout(function(){
                    var scroll = element.querySelector(scrollElement);
                    if (typeof scroll != "undefined" && scroll != null) {
                        SimpleScrollbar.initEl(scroll);
                    }
                },200);
            }

            isOpen=true;

        } else {
            isOpen=false;
        }

        if (toggle.hasClass('active')) {
            jQuery('.header .hbe-toggle.active').not(toggle).not('.mobile-toggle').not('.modal-toggle').each(function(){
                jQuery(this).parent().find('.hbe-toggle').trigger('click');
            });
            jQuery('.footer .hbe-toggle.active').not(toggle).not('.mobile-toggle').not('.modal-toggle').each(function(){
                jQuery(this).parent().find('.hbe-toggle').trigger('click');
            });
        }

    });
}

class ElementorWidgetMiniCart extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               switcher: '.mini-cart',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            switcher: this.$element.find( selectors.switcher ),
        };
    }

    bindEvents() {

        var switcher = this.elements.switcher,
            toggle   = switcher.find('.cart-toggle');

        toggleBack(switcher,toggle);

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_mini_cart', ElementorWidgetMiniCart );
});