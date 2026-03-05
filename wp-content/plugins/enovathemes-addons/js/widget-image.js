function lazyLoad(container){

    if (container != null) {

        let lazyImages = [].slice.call(container.querySelectorAll("img.lazy"));
        let lazyVideos = [].slice.call(container.querySelectorAll("video.lazy"));

        if ("IntersectionObserver" in window) {

            // Images

                let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            let lazyImage = entry.target;
                            lazyImage.src = lazyImage.dataset.src;

                            if (lazyImage.classList.contains('single') && window.innerWidth < 768) {
                                let respImg = lazyImage.getAttribute('data-img-resp');
                                respImg = respImg.split('|');
                                lazyImage.src = respImg[0];
                                lazyImage.setAttribute('width',respImg[1]);
                                lazyImage.setAttribute('height',respImg[2]);
                            }

                            lazyImage.onload = function() {
                                lazyImage.classList.remove("lazy");
                                lazyImage.parentElement.classList.add("loaded");
                                lazyImageObserver.unobserve(lazyImage);
                            };
                            
                        }
                    });
                });

                lazyImages.forEach(function(lazyImage) {
                    lazyImageObserver.observe(lazyImage);
                });

            // Videos

                let lazyVideoObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(video) {
                        if (video.isIntersecting) {

                            for (var source in video.target.children) {
                                var videoSource = video.target.children[source];
                                if (typeof videoSource.tagName === "string" && videoSource.tagName === "SOURCE") {
                                    videoSource.src = videoSource.dataset.src;
                                }
                            }

                            video.target.load();
                            video.target.classList.remove("lazy");
                            lazyVideoObserver.unobserve(video.target);
                        }
                    });
                });

                lazyVideos.forEach(function(lazyVideo) {
                    lazyVideoObserver.observe(lazyVideo);
                });

        } else {

            let active = false;

            const lazyLoad = function() {
                if (active === false) {

                    active = true;

                    setTimeout(function() {

                        lazyImages.forEach(function(lazyImage) {

                            if ((lazyImage.getBoundingClientRect().top <= window.innerHeight && lazyImage.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyImage).display !== "none") {

                                lazyImage.src = lazyImage.dataset.src;

                                lazyImage.onload = function() {
                                    lazyImage.classList.remove("lazy");
                                    lazyImage.parentElement.classList.add("loaded");
                                    lazyImages = lazyImages.filter(function(image) {
                                        return image !== lazyImage;
                                    });
                                };

                                if (lazyImages.length === 0) {
                                    document.removeEventListener("scroll", lazyLoad);
                                    window.removeEventListener("resize", lazyLoad);
                                    window.removeEventListener("orientationchange", lazyLoad);
                                }
                            }
                        });

                        lazyVideos.forEach(function(lazyVideo) {

                            if ((lazyVideo.getBoundingClientRect().top <= window.innerHeight && lazyVideo.getBoundingClientRect().bottom >= 0) && getComputedStyle(lazyVideo).display !== "none") {

                                for (var source in lazyVideo.children) {
                                    var videoSource = lazyVideo.children[source];
                                    if (typeof videoSource.tagName === "string" && videoSource.tagName === "SOURCE") {
                                        videoSource.src = videoSource.dataset.src;
                                    }
                                }

                                if (lazyVideos.length === 0) {
                                    document.removeEventListener("scroll", lazyLoad);
                                    window.removeEventListener("resize", lazyLoad);
                                    window.removeEventListener("orientationchange", lazyLoad);
                                }
                            }
                        });

                        active = false;

                    }, 200);
                }
            };

            document.addEventListener("scroll", lazyLoad);
            window.addEventListener("resize", lazyLoad);
            window.addEventListener("orientationchange", lazyLoad);

        }

    }

}

function disableParallax(){
    if (jQuery(window).width() <= 1200) {
        jQuery('.et-image.parallax').each(function(){
            jQuery(this).addClass('parallax-off');
        });
    } else {
        jQuery('.et-image.parallax').each(function(){
            jQuery(this).removeClass('parallax-off');
        });
    }
}

class ElementorWidgetImage extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               parallax: '.et-image.parallax',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $parallax: this.$element.find( selectors.parallax ),
        };
    }

    bindEvents() {

        lazyLoad(document);
        
        var $this = this.elements.$parallax;;
        var x     = $this.data('coordinatex'),
            y     = $this.data('coordinatey'),
            limit = $this.data('limit');

        if (typeof(limit) == 'undefined') {limit = 0}

        jQuery(window).scroll(function(){

            if (!$this.hasClass('parallax-off')) {

                var yPos   = Math.round((0-jQuery(window).scrollTop()) / $this.data('speed'))  +  y;
                var scroll = (Math.sign(y) == -1) ? Math.round((0-jQuery(window).scrollTop()) / $this.data('speed')) : yPos;

                if (Math.abs(scroll) > limit && limit > 0) {
                    yPos = (Math.sign(y) == -1) ? Math.sign(yPos)*(limit+Math.abs(y)) : Math.sign(yPos)*limit;
                }

                gsap.to($this,0.8,{
                    x:x,
                    y:yPos,
                    force3D:true,
                });

            } else {
                $this.removeAttr('style');
            }

        });

        disableParallax();  
        jQuery(window).resize(disableParallax);

    }


}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_image', ElementorWidgetImage );
});