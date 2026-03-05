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

class ElementorWidgetCurrencySwitcher extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               switcher: '.currency-switcher',
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
            toggle   = switcher.find('.currency-toggle');

        toggleBack(switcher,toggle);

        if (!toggle.find('.highlighted-currency').length) {

            var text = switcher.find('.currency-list a:first-child').text();

            if (switcher.find('.currency-list a:first-child').text() == 'Currency switcher'){
                text = '$ USD';
                switcher.find('.currency-list a:first-child').text(text);
                var second = switcher.find('.currency-list a:first-child').clone().text('€ EUR');
                switcher.find('.currency-list').append(second);
            }

            jQuery('<span class="highlighted-currency">'+text+'</span>').insertBefore(toggle.not('.close-toggle').find('.arrow'));
        }

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_currency_switcher', ElementorWidgetCurrencySwitcher );
});