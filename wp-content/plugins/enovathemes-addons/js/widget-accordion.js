class ElementorWidgetEtAccordion extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               accordion: '.et-accordion',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $accordion: this.$element.find( selectors.accordion ),
        };
    }

    bindEvents() {

        var $this  = this.elements.$accordion;

        console.log($this);

        gsap.set($this.find('.accordion-title.active').next(),{
            opacity: 1,
            height: 'auto'
        });


        $this.find('.accordion-title').unbind('click').on('click', function(){

            var $self = jQuery(this);

                if(!$self.hasClass('active')){
                    if($this.hasClass('collapsible-true')){

                        $self.addClass("active").siblings().removeClass("active");

                        gsap.to($self.next(),0.6, {
                            height:'auto',
                            ease:"expo.out"
                        });

                        gsap.to($self.next(),0.2, {
                            opacity:1,
                        });

                        gsap.to($this.find('.accordion-content').not($self.next()),0.1, {
                            opacity:0,
                        });

                        gsap.to($this.find('.accordion-content').not($self.next()),0.6, {
                            height:0,
                            ease:"expo.out"
                        });

                    } else {
                        $self.addClass("active");

                        gsap.to($self.next(),0.6, {
                            height:'auto',
                            ease:"expo.out"
                        });

                        gsap.to($self.next(),0.2, {
                            opacity:1,
                        });

                    }
                } else {
                    if(!$this.hasClass('collapsible-true')){
                        $self.removeClass("active");
                        $self.removeClass("active");

                        gsap.to($self.next(),0.1, {
                            opacity:0,
                        });

                        gsap.to($self.next(),0.6, {
                            height:0,
                            ease:"expo.out"
                        });
                    }
                }

        });


    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_accordion', ElementorWidgetEtAccordion );
});