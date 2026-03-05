class ElementorWidgetTabs extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               tabs: '.et-tabs',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $tabs: this.$element.find( selectors.tabs ),
        };
    }

    bindEvents() {

        var $this    = this.elements.$tabs,
            tabs     = $this.find('.tab'),
            tabsQ    = tabs.length,
            tabsDefaultWidth  = 0,
            tabsDefaultHeight = 0,
            tabsContent = $this.find('.tab-content');

            var tabSet = $this.find('.tabset');
            if(!tabSet.find('.active').length){
                tabs.first().addClass('active');
            }
            
            tabs.each(function(){

                var $thiz = jQuery(this);

                if ($thiz.hasClass('active')) {
                    $thiz.siblings()
                    .removeClass("active");
                    tabsContent.removeClass('active');
                    tabsContent.eq($thiz.index()).addClass('active');
                }

                tabsDefaultWidth += jQuery(this).outerWidth();
                tabsDefaultHeight += jQuery(this).outerHeight();
            });

            if(tabsQ >= 2){

                tabs.unbind('click').on('click', function(){
                    var $self = jQuery(this);
                    
                    if(!$self.hasClass("active")){

                        $self.addClass("active");

                        $self.siblings()
                        .removeClass("active");

                        tabsContent.removeClass('active');
                        tabsContent.eq($self.index()).addClass('active');
                    }
                    
                });
            }

            if(tabsDefaultWidth >= $this.outerWidth()  && $this.hasClass('horizontal')){
                $this.addClass('tab-full');
            } else {
                $this.removeClass('tab-full');
            }


    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_tabs', ElementorWidgetTabs );
});