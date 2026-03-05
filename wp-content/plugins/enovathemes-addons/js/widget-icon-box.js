class ElementorWidgetIconBox extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               box: '.et-icon-box.transform',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            box: this.$element.find( selectors.box ),
        };
    }

    bindEvents() {

        this.elements.box.parent().addClass('et-icon-box-transform');

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_icon_box', ElementorWidgetIconBox );
});