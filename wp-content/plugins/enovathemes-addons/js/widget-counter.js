class ElementorWidgetCounter extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               counter: '.et-counter',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            counter: this.$element.find( selectors.counter ),
        };
    }

    bindEvents() {

        var $this    = this.elements.counter,
            dDelay   = $this.data('delay'),
            delay    = (dDelay) ? dDelay/1000 : (0.2 + $this.index()*0.01),
            value    = $this.data('value'),
            counter  = $this.find('.counter'),
            counterV = { var: 0 };

            var tl = new gsap.timeline({paused: true});

            tl.to($this.find('.in'),{
                duration: 0.8,
                delay:delay,
                opacity:1,
                stagger: 0.1,
                x:0,
                transformOrigin:'left top',
                force3D:true,
                ease:"expo.out"
            });

            tl.to(counterV,{
                var:value,
                duration:1,
                onUpdate: function () {
                    counter.html(Math.ceil(counterV.var));
                },
            },'-=0.85');

            tl.to($this.find('.icon'),{
                duration: 0.2,
                opacity:1,
            },'-=0.6');

            tl.to($this.find('.icon'),{
                duration: 1.6,
                scale:1,
                force3D:true,
                ease:"elastic.out"
            },'-=0.6');

            $this.addClass('active');

            tl.progress(0);
            tl.play();

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_counter', ElementorWidgetCounter );
});