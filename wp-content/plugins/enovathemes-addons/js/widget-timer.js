class ElementorWidgetTimer extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               timer: '.et-timer',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $timer: this.$element.find( selectors.timer ),
        };
    }

    bindEvents() {

        var $this   = this.elements.$timer,
            extend  = $this.data('number'),
            enddate = $this.data('enddate'),
            gmt     = $this.data('gmt'),
            reset   = (typeof(extend) != 'undefined' && extend != null) ? true : false,
            gmt     = (typeof(gmt) != 'undefined' && gmt != null) ? gmt : 0;

        var today   = new Date();
        var enddate = new Date(enddate);

        if (reset && today >= enddate) {
            enddate.setDate(enddate.getDate() + extend);
        }

        $this.find('ul').countdown({
            date: enddate,
            offset: $this.data('gmt'),
        });

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_timer', ElementorWidgetTimer );
});