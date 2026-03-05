class ElementorWidgetProgress extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               progress: '.et-progress',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            progress: this.$element.find( selectors.progress ),
        };
    }

    bindEvents() {

        var $this    = this.elements.progress,
            type     = ($this.hasClass('circle')) ? 'circle' : 'default',
            delay    = (0.2 + $this.index()*0.01),
            value    = $this.data('percentage'),
            counterV = { var: 0 },
            counter  = $this.find('.percent');

            var tl = new gsap.timeline({paused: true});

            if (type == 'default') {

                tl.from($this.find('.bar'),{
                    duration: 1.6,
                    delay:delay,
                    scaleX:0,
                    force3D:true,
                    transformOrigin:'left top',
                    ease:"expo.out"
                });

                tl.from($this.find('.text'),{
                    duration: 0.8,
                    opacity:0,
                    x:50,
                    transformOrigin:'left top',
                    force3D:true,
                    ease:"expo.out"
                },'-=1.6');

                tl.to(counterV,{
                    var:value,
                    duration:1,
                    onUpdate: function () {
                        $this.find('.bar').html('<span class="percent">'+Math.ceil(counterV.var)+'</span>');
                    },
                },'-=1.4');

            } else {

                var bar           = this.elements.progress.get(0).querySelector('.bar-circle'),
                    circumference = 27 * 2 * Math.PI,
                    offset        = circumference - value / 100 * circumference;

                bar.style.strokeDasharray = circumference+' '+circumference;
                bar.style.strokeDashoffset = circumference;

                tl.to(bar,{
                    duration: 0.2,
                    delay:delay,
                    opacity:1
                });

                tl.to(bar,{
                    duration: 2,
                    strokeDashoffset:offset,
                    ease:"expo.out"
                },'-=0.2');

                tl.from($this.find('.text').children(),{
                    duration: 0.8,
                    opacity:0,
                    y:50,
                    stagger:0.1,
                    transformOrigin:'left top',
                    force3D:true,
                    ease:"expo.out"
                },'-=2');

                tl.to(counterV,{
                    var:value,
                    duration:1,
                    onUpdate: function () {
                        counter.html(Math.ceil(counterV.var));
                    },
                },'-=2');

            }

            $this.addClass('active');
            tl.progress(0);
            tl.play();

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_progress', ElementorWidgetProgress );
});