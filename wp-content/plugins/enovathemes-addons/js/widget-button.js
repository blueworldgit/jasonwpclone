class ElementorWidgetButton extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               scale: '.et-button.hover-scale',
               smooth: '.et-button.smooth-true.modal-false',
               modal: '.et-button.smooth-true.modal-true',
               background: '.button-back',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $scale: this.$element.find( selectors.scale ),
            $smooth: this.$element.find( selectors.smooth ),
            $modal: this.$element.find( selectors.modal ),
            $background: this.$element.find( selectors.background ),
        };
    }

    bindEvents() {
        this.elements.$scale.on('mouseover', this.scaleMouseover.bind( this ) );
        this.elements.$scale.on('mouseout', this.scaleMouseout.bind( this ) );
        this.elements.$smooth.on('click', this.buttonClick.bind( this ) );
        this.elements.$modal.on('click', this.buttonClick.bind( this ) );
    }

    scaleMouseover() {
        gsap.to(this.elements.$background,0.8, {
            scale:1.05,
            ease:"elastic.out"
        });
    }

    scaleMouseout() {
        gsap.to(this.elements.$background,0.8, {
            scale:1,
            ease:"expo.out"
        });
    }

    buttonClick(e) {
        e.preventDefault();
    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_button', ElementorWidgetButton );
});