class ElementorWidgetHeading extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               heading: '.et-heading.animate-true',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $heading: this.$element.find( selectors.heading ),
        };
    }

    bindEvents() {

        var $this = this.elements.$heading,
            delay = '+='+(0.2 + parseInt($this.data('delay'))/1000),
            text  = $this.find('.text');

        if(!$this.hasClass('fired')){

            if (!$this.hasClass('timeline')) {

                var tl = new gsap.timeline({paused: true});

                if ($this.hasClass('curtain')) {

                    var curtain = $this.find('.curtain');

                    tl.to(curtain,0.8, {
                      scaleX:1,
                      transformOrigin:'left top',
                      ease:"power3.out"
                    },delay);

                    tl.to(curtain,0.8, {
                      scaleX:0,
                      transformOrigin:'right top',
                      ease:"power3.out"
                    });

                    tl.to(text,0.2, {
                      opacity:1,
                    },'-=0.8');
                }

                else if ($this.hasClass('letter')) {
                    var letterText = new SplitText($this.find('.text'),{type:"chars"});

                    gsap.set($this,{perspective:500});

                    tl.from(letterText.chars,{
                        duration: 0.2,
                    },delay);

                    tl.from(letterText.chars,{
                        duration: 0.6,
                        opacity:0,
                        scale:3,
                        x:100,
                        y:50,
                        force3D:true,
                        stagger: 0.01,
                        ease:"expo.out"
                    },'-=0.2');

                }

                else if ($this.hasClass('words')) {

                    var wordsText = new SplitText($this.find('.text'),{type:"words"});
                    
                    gsap.set($this,{perspective:500});

                    tl.from(wordsText.words,{
                        duration: 0.2,
                    },delay);

                    tl.from(wordsText.words,{
                        duration: 0.8,
                        opacity:0,
                        scaleY:1.5,
                        transformOrigin:'left top',
                        y:24,
                        force3D:true,
                        stagger: 0.04,
                        ease:"expo.out"
                    },'-=0.2');

                }

                else if ($this.hasClass('rows')) {
                    
                    var rowsText = new SplitText($this.find('.text'),{type:"lines"});
                    
                    gsap.set($this,{perspective:1000});

                    tl.from(rowsText.lines,{
                        duration: 0.4,
                    },delay);

                    tl.from(rowsText.lines,{
                        duration: 1.2,
                        opacity:0,
                        rotationX:8,
                        rotationY:-50,
                        rotationZ:8,
                        y:50,
                        x:-50,
                        z:50,
                        transformOrigin:'left top',
                        force3D:true,
                        stagger: 0.08,
                        ease:"expo.out"
                    },'-=0.2');

                }

                $this.addClass('timeline');

                tl.progress(0);
                tl.play();

                $this.addClass('fired');

            }

        }

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_heading', ElementorWidgetHeading );
});