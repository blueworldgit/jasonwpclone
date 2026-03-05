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

function postCarousel(posts){

    var Posts = (posts != null) ? '#'+posts+' > .loop-posts' : '.et-posts > .loop-posts';

	jQuery(Posts).each(function(){

		var $this       = jQuery(this).addClass('enova-carousel'),
			itemsmob    = ($this.parent().hasClass('list') || $this.parent().hasClass('full')) ? 1 : 1.6,
			itemsmob320 = ($this.parent().hasClass('list') || $this.parent().hasClass('full')) ? 1 : 1.4,
			items       = $this.parents('.swiper-container').data('columns'),
			items768    = $this.parents('.swiper-container').data('tab-port-columns'),
			items1024   = $this.parents('.swiper-container').data('tab-land-columns');

			if ($this.parent().hasClass('grid')) {
				items768  += 0.4;
				items1024 += 0.4;
			}

			if ($this.parent().hasClass('grid') && items > 4) {items = 4}
			if (($this.parent().hasClass('list') || $this.parent().hasClass('full')) && items > 2) {items = 2}

		var config = {
			pagination: {
		        el: '#'+$this.parents('.swiper-container').find('.swiper-pagination').attr('id'),
		        clickable: true,
		        renderBullet: function (index, className) {
          			return '<span class="' + className + '"></span>';
		        },
		    },
		    navigation: {
			    nextEl: '#'+$this.parents('.swiper-container').find('.swiper-button-next').attr('id'),
			    prevEl: '#'+$this.parents('.swiper-container').find('.swiper-button-prev').attr('id'),
			},
			spaceBetween: 24,
			slidesPerView: items,
			grabCursor: true,
			autoHeight: true,
			direction:'horizontal',
			loop: false,
			breakpoints: {
				200: {
					slidesPerView: itemsmob320,
					spaceBetween: 8,
				},
				375: {
					slidesPerView: itemsmob,
					spaceBetween: 8,
				},
				425: {
					slidesPerView: itemsmob,
					spaceBetween: 8,
				},
				540: {
					slidesPerView: itemsmob,
					spaceBetween: 8,
				},
				768: {
					slidesPerView: items768,
				},
				1024: {
					slidesPerView: items1024,
				},
				1280: {
					slidesPerView: items,
				}
			}
		};

		if (typeof(autoplay) != "undefined" && autoplay == true) {
			config['autoplay'] = {
			    delay: 2000,
			    disableOnInteraction: false,
	    		pauseOnMouseEnter: true
			};

			config['loop'] = true;
		}

		var swiper = new Swiper('#'+$this.parent().attr('id'), config);

	});
}

class ElementorWidgetPosts extends elementorModules.frontend.handlers.Base {
    
    getDefaultSettings() {
        return {
            selectors: {
               carousel: '.et-shortcode-posts.swiper',
               posts: '.et-shortcode-posts',
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings( 'selectors' );
        return {
            $carousel: this.$element.find( selectors.carousel ),
            $posts: this.$element.find( selectors.posts ),
        };
    }

    bindEvents() {

        var carousel = this.elements.$carousel;
        var posts = this.elements.$posts;
		
		lazyLoad(posts.get(0));

		if (typeof(carousel) != 'undefined' && carousel.length) {
            postCarousel(carousel.attr('id'));
        }

    }

}

jQuery( window ).on( 'elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler( 'et_post', ElementorWidgetPosts );
});