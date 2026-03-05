class ElementorWidgetDesktopMenu extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               nav: '.nav-menu',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $nav: this.$element.find( selectors.nav ),
        };
    }

    bindEvents() {

        var nav = this.elements.$nav;

        if (nav.parents('.header').length) {
            nav.find('.depth-0:first-child').addClass('active');
        }

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_desktop_menu', ElementorWidgetDesktopMenu );
});