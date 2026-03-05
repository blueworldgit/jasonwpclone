class ElementorWidgetProductSearch extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               toggle: '.search-toggle',
               box: '.search-box',
               off: '.search-toggle-off'
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $toggle: this.$element.find( selectors.toggle ),
            $box: this.$element.find( selectors.box ),
            $off: this.$element.find( selectors.off )
        };
    }

    bindEvents() {

        this.elements.$toggle.on('click', this.toggleON.bind( this ) );
        this.elements.$off.on('click', this.toggleOFF.bind( this ) );

    }

    toggleON() {
        this.elements.$box.addClass('active');
        this.elements.$box.find('input[type="search"]').focus();
    }

    toggleOFF() {
        this.elements.$box.removeClass('active');
    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_product_search', ElementorWidgetProductSearch );
});