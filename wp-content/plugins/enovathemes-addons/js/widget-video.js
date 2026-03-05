function lightImage(src,overlay){

    if (
        src.includes('.jpg') ||
        src.includes('.jpeg') ||
        src.includes('.png') ||
        src.includes('.bmp') ||
        src.includes('.gif') ||
        src.includes('.svg')
    ) {
        
        var img = document.createElement('img');
        img.src = src;

        var loaded = false;

        img.onload = function() {

            if (loaded) {
                return;
            }

            if (overlay.find('img').length == 0) {
                overlay.prepend(img);
            }

            loaded = true;
        }
        
    } else if (src.includes('youtu') || src.includes('vimeo')) {
        var iframe = document.createElement('iframe');

        src = src.replace('watch?v=', 'embed/');
        src = src.replace('//vimeo.com/', '//player.vimeo.com/video/');
        src = (src.indexOf("?") == -1) ? src += '?' : src += '&';

        iframe.src = src+'autoplay=1';
        iframe.frameborder = '0';
        iframe.width  = '1280';
        iframe.height = '720';
        iframe.allow  = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture';
        iframe.allowfullscreen = true;
        overlay.prepend(iframe);
    } else if (src.includes('mp4') || src.includes('webm') || src.includes('ogv')) {
        var video = document.createElement('video');
        video.src = src;
        video.autoplay = true;
        video.controls = true;
        overlay.prepend(video);
    }
}

function gsapLightbox(element,gallery){

    var href = element.attr('href');

    if (
        href.includes('.jpg') ||
        href.includes('.jpeg') ||
        href.includes('.png') ||
        href.includes('.bmp') ||
        href.includes('.gif') ||
        href.includes('.svg') ||
        href.includes('youtu') ||
        href.includes('mp4') ||
        href.includes('webm') ||
        href.includes('ogv')
    ){

        if (!jQuery('.gsap-lightbox-overlay').length) {

            var structure = (gallery == true) ? 
            jQuery('<div class="gsap-lightbox-overlay"><div class="image-wrapper"></div><a href="#" class="gsap-lightbox-controls gsap-lightbox-toggle"></a><a href="#" class="gsap-lightbox-controls gsap-lightbox-nav prev" data-direction="prev"></a><a href="#" class="gsap-lightbox-controls gsap-lightbox-nav next" data-direction="next"></a><svg class="placeholder" viewBox="0 0 20 4"><circle cx="2" cy="2" r="2" /><circle cx="10" cy="2" r="2" /><circle cx="18" cy="2" r="2" /></svg></div>') :
            jQuery('<div class="gsap-lightbox-overlay"><div class="image-wrapper"></div><a href="#" class="gsap-lightbox-controls gsap-lightbox-toggle"></a><svg class="placeholder" viewBox="0 0 20 4"><circle cx="2" cy="2" r="2" /><circle cx="10" cy="2" r="2" /><circle cx="18" cy="2" r="2" /></svg></div>');

            jQuery('body').append(structure);

            var overlay = jQuery('.gsap-lightbox-overlay'),
                wrapper = overlay.find('.image-wrapper'),
                toggle  = overlay.find('.gsap-lightbox-toggle'),
                loading = overlay.find('.gsap-lightbox-toggle');

            var tl = new gsap.timeline({paused: true});

            tl.from(toggle,0.2, {
              opacity:0,
              ease:"expo.out"
            },'+=0.2');

            tl.from(toggle,1.2, {
              x:'-12px',
              ease:"elastic.out(1, 0.5)"
            },'-=0.2');



            if (gallery == true) {

                var nav         = overlay.find('.gsap-lightbox-nav'),
                    next        = overlay.find('.next'),
                    prev        = overlay.find('.prev'),
                    gallerySet  = [],
                    count       = 0,
                    galleryName = element.data('gallery');

                tl.from(nav,0.2, {
                    opacity:0,
                },'-=1.1');

                tl.from(prev,1.2, {
                  x:'-40px',
                  ease:"elastic.out(1, 0.5)"
                },'-=1.1');

                tl.from(next,1.2, {
                  x:'40px',
                  ease:"elastic.out(1, 0.5)"
                },'-=1.2');

                jQuery('a[data-gallery="'+galleryName+'"]').each(function(){
                    gallerySet.push(jQuery(this).attr('href'));
                });

                if (!gallerySet.length) {
                    jQuery('a').each(function(){
                        gallerySet.push(jQuery(this).attr('href'));
                    });
                }

                count = gallerySet.indexOf(element.attr('href'));

                var max = gallerySet.length;

                if (max == 1) {
                    jQuery('.gsap-lightbox-overlay .gsap-lightbox-nav').remove();
                }
                
                nav.on('click',function(e){

                    overlay.find('img').remove();

                    e.preventDefault();

                    count += (jQuery(this).data('direction') == "next") ? 1 : -1;
                    if (count < 0) {count = max - 1;}
                    if (count >= max) {count = 0;}

                    lightImage(gallerySet[count],wrapper);
                });

            }

            tl.add('active');

            tl.to(overlay,0.1, {
                opacity:0,
            });

            setTimeout(function(){
                overlay.addClass('active');
                tl.progress(0);
                tl.tweenTo('active');

                lightImage(element.attr('href'),wrapper);

            },50);

            toggle.on('click',function(e){
                e.preventDefault();
                tl.play();
                overlay.removeClass('active');
                setTimeout(function(){
                    overlay.remove();
                },500);
            });

        }

    }
}

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

class ElementorWidgetVideo extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               video: '.video-btn',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $video: this.$element.find( selectors.video ),
        };
    }

    bindEvents() {

        lazyLoad(document);

        var $this  = this.elements.$video,
            video  = $this.parents('.post-video').find('.video-element'),
            image  = $this.parents('.post-video').find('.image-container'),
            embed  = (video.hasClass('iframevideo')) ? true : false,
            back   = $this.find('.back');

        $this.hover(
            function(){
                gsap.to(back,0.8, {
                  scale:1.15,
                  ease:"elastic.out"
                });
            },
            function(){
                gsap.to(back,0.8, {
                  scale:1,
                  ease:"expo.out"
                });
            }
        );

        $this.unbind('click').on('click',function(e){
            e.preventDefault();

            if (!$this.hasClass('video-modal')) {
                image.toggleClass('playing');
                video.toggleClass('playing');
            }

            if ($this.hasClass('video-modal')) {
                gsapLightbox($this,false);
            } else {
                setTimeout(function(){
                    if (embed) {
                        var src = video.attr('src');
                        src =  (src.indexOf("?") == -1) ? src += '?' : src += '&';
                        video.attr('src',src+'autoplay=1');
                    } else {
                        video.trigger('play');
                    }
                },500);
            }

        });


    }


}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_video', ElementorWidgetVideo );
});