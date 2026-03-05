function waitForElement(selector, callback) {
    // If it is already there, run immediately
    const elem = document.querySelector(selector);
    if (elem) {
        callback(elem);
        return;
    }

    // Otherwise watch the DOM for added nodes
    const observer = new MutationObserver((mutations, obs) => {
        const el = document.querySelector(selector);
        if (el) {
            obs.disconnect();
            callback(el);
        }
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true
    });
}

function uniqueID() {return Math.floor((Math.random() * 1000000) + 1);}

function etSmoothScrollTo($target, scrollOffset = 0, duration = 500) {
    if (!$target || !$target.length) return;

    // Use the actual scrolling element (works in all browsers)
    const scrollingEl = document.scrollingElement || document.documentElement;
    const $root = jQuery(scrollingEl);

    // If your header height changes on scroll, measure now
    const stickyH = (jQuery('.site-header,.sticky-header').filter(':visible').outerHeight()) || 0;

    // Cache the final top BEFORE animating
    const top = Math.max(0, $target.offset().top - scrollOffset - stickyH);

    // 1) Kill any ongoing/queued animations on the root
    $root.stop(true, true);

    // 2) Temporarily disable native CSS smooth scrolling to avoid double-smooth
    const prevScrollBehavior = scrollingEl.style.scrollBehavior;
    scrollingEl.style.scrollBehavior = 'auto';

    // 3) While animating, cancel if any competing user/JS scroll happens
    const cancelIfScroll = () => $root.stop(true, false);
    jQuery(window).on('wheel.pvscroll touchmove.pvscroll keydown.pvscroll', cancelIfScroll);

    // 4) Animate
    $root.animate({ scrollTop: top }, duration, 'swing', function () {
        // Restore
        jQuery(window).off('.pvscroll');
        scrollingEl.style.scrollBehavior = prevScrollBehavior || '';
    });
}

(function($){

	"use strict";

	/* Update popstate
	----*/

		const emit = () => document.dispatchEvent(new Event('urlchange'));
		const _pushState = history.pushState;
		const _replaceState = history.replaceState;

		history.pushState = function () {
		const ret = _pushState.apply(this, arguments);
		emit();
		return ret;
		};
		history.replaceState = function () {
		const ret = _replaceState.apply(this, arguments);
		emit();
		return ret;
		};
		window.addEventListener('popstate', emit);

		function debounce(fn, wait = 60) {
		  let t;
		  return function () {
		    clearTimeout(t);
		    t = setTimeout(() => fn.apply(this, arguments), wait);
		  };
		}

		const rerunReveal = debounce(() => revealWidgetsForCategory(), 50);
		rerunReveal();

		// When URL changes via SPA navigation
		document.addEventListener('urlchange', rerunReveal);

		// When browser back/forward triggers a render
		window.addEventListener('popstate', rerunReveal);

		// After any AJAX completes (good catch-all for WooCommerce/AJAX filters)
		$(document).ajaxComplete(rerunReveal);

	/* Helper functions
	----*/

		function getCategorySlugsFromURL(url = window.location.href, base = 'product-category') {
		  try {
		    const u = new URL(url, window.location.origin);
		    // Normalize multiple slashes and split
		    const parts = u.pathname.replace(/\/+/g, '/').split('/').filter(Boolean);
		    const i = parts.indexOf(base);
		    if (i === -1) return [];
		    return parts.slice(i + 1).filter(Boolean).map(decodeURIComponent);
		  } catch {
		    return [];
		  }
		}

		function revealWidgetsForCategory(opts = {}) {

			const settings = $.extend(
				{
					attr: 'data-category-limit',
					base: copt.productCatBase,
					includeAncestors: true
				},
				opts
			);

			const $widgets = $(`.widget[${settings.attr}]`);
			const slugs = getCategorySlugsFromURL(window.location.href, settings.base);

			// Always clear previous active classes first
			$widgets.removeClass('active');

			// If no slugs found, stop here
			if (!slugs.length) return [];

			const targets = settings.includeAncestors ? slugs : [slugs[slugs.length - 1]];
			const revealed = [];

			$.each(targets, function (_, slug) {
				$widgets
				.filter(`[${settings.attr}*="${slug}"]`)
				.addClass('active')
				.each(function () {
				    revealed.push(this);
				});
			});

			return revealed;
		}

		function updateNavigationVisibility(swiper, btn) {

			if (typeof(swiper) != "undefined") {

			    const totalSlides = swiper.slides.length;
			    const slidesPerView = swiper.params.slidesPerView;

			    if (totalSlides <= slidesPerView) {
			        btn.addClass('swiper-hidden');
			    } else {
			        btn.removeClass('swiper-hidden');
			    }

		    }
		}

		function buildSwiperCarousel($target,opt=false) {

		    const swiperID    = uniqueID();
		    let   breakpoints = {};

		    $target.find('.swiper').attr('id','swiper-'+swiperID);

		    const	swiperTarget   = '#'+$target.find('.swiper').attr('id'),
		    		colDesktop     = $target.attr('data-cl-d'),
		        	colTabletLands = $target.attr('data-cl-tbl'),
		        	colTablet      = $target.attr('data-cl-tb'),
		        	colMobile      = $target.attr('data-cl-mb'),
		        	colMobileS     = $target.attr('data-cl-mbs'),
		        	gapD           = opt && opt.hasOwnProperty('gap_d') ? opt.gap_d : 32,
		        	gapT           = opt && opt.hasOwnProperty('gap_tb') ? opt.gap_tb : 24,
		        	gapM           = opt && opt.hasOwnProperty('gap_mb') ? opt.gap_mb : 12,
		        	gapMS          = opt && opt.hasOwnProperty('gap_mbs') ? opt.gap_mbs : 8;

		    if (opt && opt.hasOwnProperty('breakpoints')){
		    	breakpoints = opt['breakpoints'];
		    } else {
		    	breakpoints = {
			        1: { slidesPerView: parseFloat(colMobileS), spaceBetween: gapMS },
			        360: { slidesPerView: parseFloat(colMobile), spaceBetween: gapM },
			        768: { slidesPerView: parseFloat(colTablet), spaceBetween: gapT },
			        1024: { slidesPerView: parseFloat(colTabletLands), spaceBetween: gapT },
			        1280: { slidesPerView: parseFloat(colDesktop), spaceBetween: gapD }
			    };
		    }

		    $target
		    .append('<div id="carousel-prev-'+swiperID+'" class="swiper-button swiper-button-prev swiper-hidden"></div><div id="carousel-next-'+swiperID+'" class="swiper-button swiper-button-next swiper-hidden"></div>');

		    // Define navigation buttons BEFORE initializing Swiper
		    const prevButton = $target.find('.swiper-button-prev');
		    const nextButton = $target.find('.swiper-button-next');

		    let previousIndex = 0; // track outside Swiper init

		    // Initialize Swiper
		    const swiper = new Swiper(swiperTarget, {
		        direction: 'horizontal',
		        loop: false,
		        spaceBetween: gapD,
		        slidesPerView: parseFloat(colDesktop),
		        grabCursor: true,
		        autoHeight: (opt && opt.hasOwnProperty('autoHeight')) ? opt['autoHeight'] : true,
		        breakpoints: breakpoints,
		        on: {
		            slideChange: function () { 

		            	if (swiper.activeIndex > previousIndex) {
						    // Swiped forward: mark previous slides as viewed
						    swiper.slides.forEach((slide, index) => {
						        if (index < swiper.activeIndex) {
						            slide.classList.add('viewed');
						        }
						    });
						} else if (swiper.activeIndex < previousIndex) {
						    // Swiped backward: unmark slides ahead of current
						    swiper.slides.forEach((slide, index) => {
						        if (index >= swiper.activeIndex) {
						            slide.classList.remove('viewed');
						        }
						    });
						}

			            previousIndex = swiper.activeIndex;

		            	if($target.find('.swiper-button')){
		            		updateNavigationVisibility(swiper, $target.find('.swiper-button'));
		            	}
		        	},
		            resize: function () { 
		            	if($target.find('.swiper-button')){
		            		updateNavigationVisibility(swiper, $target.find('.swiper-button'));
		            	}
		        	}
		        }
		    });

		    // Custom navigation controls
		    if (nextButton.length) {
			    jQuery('body').on('click', '#' + nextButton.attr('id'), function () {
			        swiper.slideNext();
			    });
			}

		    if (prevButton.length) {
			    jQuery('body').on('click', '#' + prevButton.attr('id'), function () {
			        swiper.slidePrev();
			    });
		    }

		    // Run visibility check initially
		    if ($target.find('.swiper-button').length) {
		    	updateNavigationVisibility(swiper, $target.find('.swiper-button'));
		    }
		}

		function getUrlParams(url=window.location.href) {

	        var url = decodeURIComponent(url);
	            url = url.split('?');

	        var query = url[1];
	        var params = new Object;

	        if (typeof(query) != 'undefined' && query != null) {
	            var vars = query.split('&');
	            for (var i = 0; i < vars.length; i++) {
	                var pair = vars[i].split('=');
	                params[pair[0]] = decodeURIComponent(pair[1]);
	            }
	            return (jQuery.isEmptyObject(params)) ? false : params;
	        }

	        return false;
	    }

		function createBaseUrl(baseURL){
			
			if (Object.keys(baseURL).length > 1) {

				let filterUrl   = ''; // First item is the base URL

				if ('base' in baseURL) {
					filterUrl = baseURL['base'];
				}

				Object.entries(baseURL).forEach(([key, value], index) => {
					if (key != "base") {
						filterUrl += (index == 1) ?  '?' : '&';
						filterUrl += key+'='+value;
					}
				});

				if (filterUrl.length) {
					baseURL = filterUrl;
				}

			} else if('base' in baseURL) {
				baseURL = baseURL['base'];
			}

			return baseURL;
		}

		function multipleChoosenLayeredNav(items){
			return items.map(function() {
			    return $(this).text();
			}).get().join(', ');
		}

		function layeredNavListScroll(list,removeSearchBar=true) {
		    // Calculate the height of only visible items
		    var visibleHeight = list.find('li:visible').outerHeight(true) * list.find('li:visible').length;

		    if (visibleHeight > 224) {
		        list.addClass('scroll');
		        if (list.prev('input').length === 0) {
		            $('<input type="search" placeholder="'+copt.strings.termSearchText+'" class="term-search" />').insertBefore(list);
		        }
		    } else {
		        list.removeClass('scroll');
		        if (removeSearchBar) {
		        	list.prev('.term-search').remove();
		        }
		    }
		}

		function listToSelect($list, level = 0) {
	        let options = '';
	        $list.children('li').each(function () {
	            const $item = $(this);
	            const current = $item.hasClass('current-cat') ? $item.find('a').attr('href') : false;
	            const text = '&nbsp;'.repeat(level * 4) + $item.find('a').attr('title');
	            const value = $item.find('a').attr('href');

	            options += '<option value="'+value+'"';

	            if (current) {
	            	options += ' selected="selected"';
	            }

	            options += '>'+text+'</option>';

	            // If there is a nested <ul>, process it recursively
	            const $nestedList = $item.children('ul');
	            if ($nestedList.length > 0) {
	                options += listToSelect($nestedList, level + 1);
	            }
	        });
	        return options;
	    }

	    function getItemCountBySlug(termInfo, slug) {
		    const term = termInfo.find(item => item.slug === slug);
		    return term ? term.count : 0;  // Return count if found, or null if not
		}

		function getCategoryBreadcrumbs(categories, slug) {
		    // Find the category that matches the given slug
		    let filteredCategory = categories.find(category => category.slug === slug);

		    // If the category is not found, return an empty array
		    if (!filteredCategory) {
		        return [];
		    }

		    // Initialize the breadcrumbs array
		    let breadcrumbs = [filteredCategory];

		    // Traverse the parent categories up the hierarchy
		    let parentId = filteredCategory.parent_id;
		    while (parentId !== 0) {
		        let parentCategory = categories.find(category => category.id === parentId);
		        if (parentCategory) {
		            breadcrumbs.push(parentCategory);
		            parentId = parentCategory.parent_id;
		        } else {
		            break; // Stop if no parent is found
		        }
		    }

		    // Reverse the breadcrumbs array to display from root to current category
		    return breadcrumbs.reverse();
		}

		function generatePagination(currentPage, totalPages, baseURL, midSize = 2, endSize = 1) {
		    if (totalPages <= 1) return ''; // No pagination needed if only one page

		    baseURL = baseURL.replace(/page\/\d+\/?/, '');

		    baseURL += 'page/';

		    let paginationHTML = '<ul class="page-numbers">';

		    // Previous button
		    if (currentPage > 1) {
		        paginationHTML += `<li><a class="prev page-numbers" href="${baseURL}${currentPage - 1}/">←</a></li>`;
		    }

		    let outputPages = new Set(); // To store visible pages

		    // Always show first N pages
		    for (let i = 1; i <= endSize; i++) outputPages.add(i);

		    // Always show last N pages
		    for (let i = totalPages - endSize + 1; i <= totalPages; i++) outputPages.add(i);

		    // Add middle range pages
		    for (let i = currentPage - midSize; i <= currentPage + midSize; i++) {
		        if (i > 0 && i <= totalPages) outputPages.add(i);
		    }

		    // Convert to sorted array
		    let pagesArray = Array.from(outputPages).sort((a, b) => a - b);

		    // Generate pagination with ellipses
		    let lastPage = 0;
		    pagesArray.forEach(page => {
		        if (lastPage && page !== lastPage + 1) {
		            paginationHTML += '<li><span class="page-numbers dots">…</span></li>';
		        }

		        if (page === currentPage) {
		            paginationHTML += `<li><span aria-label="Page ${page}" aria-current="page" class="page-numbers current">${page}</span></li>`;
		        } else {
		            paginationHTML += `<li><a aria-label="Page ${page}" class="page-numbers" href="${baseURL}${page}/">${page}</a></li>`;
		        }

		        lastPage = page;
		    });

		    // Next button
		    if (currentPage < totalPages) {
		        paginationHTML += `<li><a class="next page-numbers" href="${baseURL}${currentPage + 1}/">→</a></li>`;
		    }

		    paginationHTML += '</ul>';
		    return paginationHTML;
		}

		function updatepushState(baseURL,filterUrl){
			let newUrl = baseURL;

	        newUrl = newUrl.replace('/&', '/?');
	        newUrl = encodeURI(newUrl);

	        history.pushState({ url: newUrl }, "", newUrl);
		}

		function layeredNavDropdownEvents(trigger,activeFilters,baseURL){
	    	let activeTerm  = (trigger.is('select')) ? trigger.val() : trigger.parents('.widget').find('select').val(),
				attribute   = trigger.parents('.widget').attr('data-attribute'),
				queryType   = trigger.parents('.widget').attr('data-query-type');

			if (activeTerm.length) {

				if ('and' == queryType) {

					activeFilters['filter_'+attribute] = activeTerm;
					baseURL['filter_'+attribute]   = activeFilters['filter_'+attribute];

				} else {

					activeFilters['filter_'+attribute] = activeTerm.join(',');
					baseURL['query_type_'+attribute] = 'or';
					baseURL['filter_'+attribute] = activeFilters['filter_'+attribute];

				}


			} else {
				delete activeFilters['filter_'+attribute];
				delete baseURL['filter_'+attribute];
				delete activeFilters['query_type_'+attribute];
				delete baseURL['query_type_'+attribute];
			}

			ajaxProductFilter(activeFilters,baseURL);
	    }

	    function rebuildCategoriesCarousel(items){

	    	if (items.length) {

		    	let products = '';

		    	items.forEach(function(product){
		    		products += '<li class="swiper-hidden swiper-slide category '+product.slug+'">';
		    			products += '<a href="'+product.link+'" title="'+product.name+'">';
		    				products += '<div class="image-container">';
		    					products += '<img src="'+product.image+'" width="300" height="250" alt="'+product.name+'">';
		    					products += '<svg viewBox="0 0 300 300"><path d="M0,0H300V300H0V0Z"></path></svg>';
		    				products += '</div>';
		    				products += '<h3>'+product.name+'</h3>';
		    			products += '</a>';
		    		products += '</li>';
		    	});

		    	if (products.length) {
		    		return products;
		    	}

	    	}

	    }

	    async function getSpellingSuggestions(word) {
		    const url = `https://api.datamuse.com/sug?s=${encodeURIComponent(word)}`;
		    try {
		        const response = await fetch(url);
		        const data = await response.json();
		        return data.map(item => item.word); // Extract only suggested words
		    } catch (error) {
		        console.error("Error fetching suggestions:", error);
		        return [];
		    }
		}

		function offerKeywordSuggestions(suggestions) {
		    let suggestionCount = parseInt(localStorage.getItem(copt.shopName + '-suggestion-count')) || 0;

		    // If suggestions exist
		    if (suggestions.length > 0) {
		        // Increment suggestion count and store in localStorage
		        suggestionCount++;
		        localStorage.setItem(copt.shopName + '-suggestion-count', suggestionCount);

		        // If suggestion count exceeds 2, remove suggestions and exit
		        if (suggestionCount > 2) {
		            $('.et__product_ajax_search_keyword_suggestions').remove();
		            localStorage.removeItem(copt.shopName + '-suggestion-count');
		            return;
		        }

		        // Generate the suggestions HTML
		        let suggestionsHTML = '<div class="et__product_ajax_search_keyword_suggestions">';
		        suggestionsHTML += copt.strings.searchSuggestion + ': ';

		        // Add suggestions (up to 3)
		        suggestions.slice(0, 3).forEach(function(value) {
		            suggestionsHTML += '<span class="keyword-suggestion">' + value + '</span>';
		        });

		        suggestionsHTML += '</div>';

		        // Replace or insert suggestions HTML
		        if ($('.et__product_ajax_search_keyword_suggestions').length) {
		            $('.et__product_ajax_search_keyword_suggestions').replaceWith(suggestionsHTML);
		        } else {
		            $(suggestionsHTML).insertAfter($('.et__product_ajax_search.embed'));
		        }

		    } else {
		        console.log("No suggestions found.");
		    }
		}


	/* Swiper
	----*/

		if ($('.categories-carousel-container').length) {

			let opt = {
				'gap_d':16,
				'gap_tb':16,
				'gap_mb':8,
				'gap_mbs':8
			}

			buildSwiperCarousel($('.categories-carousel-container'),opt);
	    }

	/* Active clear functions
	----*/

		function clearActiveWidgetFromUrl(clearUrlData,clearLink){
			if (Object.keys(clearUrlData).length != 0) {

				clearLink += '?';

			    // Get all keys of the object
			    const keys = Object.keys(clearUrlData);

			    // Iterate through each key
			    keys.forEach(function(key, index) {
			        // Append key and value to clearLink
			        clearLink += key + '=' + clearUrlData[key];

			        // Check if it's not the last element
			        if (index < keys.length - 1) {
			            clearLink += '&'; // Add '&' between elements
			        }
			    });
		   	}

		   	return clearLink;
		}

		function isActiveWidgetInUrl(url,value){
			if(typeof(value) != "undefined"){
				return value.split(',').some(item => url.includes(item));
			}
			return false;
		}

		function appendActiveFilter(label,activeFilter,clearLink,$class=""){

			if ($('#loop-products').length) {

				if (!$('.active-filters').length) {
					$('<div class="active-filters original"></div>')
					.insertAfter($('.woocommerce-before-shop-loop'));

					$('.shop-widgets').prepend($('<div class="active-filters sidebar"></div>'));

				}

				if (!$('.clear-all-filters').length && $('.active-filters.original a').length == 1) {
					$('.active-filters')
					.prepend($('<a class="clear-all-filters" href="'+copt.shopLink+'" title="'+copt.strings.widgetClearAll+'"><span class="remove"></span>'+copt.strings.widgetClearAll+'</a>'))
				}

				let html = '<a class="'+$class+'" href="'+clearLink+'" title="'+copt.strings.widgetClear+'">';
						html += '<span class="remove"></span>';
						html += label+': '+activeFilter;
						html += '</a>';

				if ($('.active-filters').find('a.'+$class).length) {
					$('.active-filters').find('a.'+$class)
					.replaceWith(html);
				} else {
					$('.active-filters').append(html);
				}

				if ($class == "category" && $('.et__product_ajax_search').length) {

					html = '<a class="'+$class+'" href="'+clearLink+'" title="'+copt.strings.widgetClear+'">';
						html += '<span class="remove"></span>';
						html += activeFilter;
						html += '</a>';

					if ($('.et__product_ajax_search').children('a.category').length) {
						$('.et__product_ajax_search').children('a.category')
						.replaceWith(html);
					} else {
						$('.et__product_ajax_search').prepend(html);
					}
				}

			} else if ($class == "category" && $('.et__product_ajax_search').length) {

				let html = '<a class="'+$class+'" href="'+clearLink+'" title="'+copt.strings.widgetClear+'">';
					html += '<span class="remove"></span>';
					html += label+': '+activeFilter;
					html += '</a>';

				html = '<a class="'+$class+'" href="'+clearLink+'" title="'+copt.strings.widgetClear+'">';
					html += '<span class="remove"></span>';
					html += activeFilter;
					html += '</a>';

				if ($('.et__product_ajax_search').children('a.category').length) {
					$('.et__product_ajax_search').children('a.category')
					.replaceWith(html);
				} else {
					$('.et__product_ajax_search').prepend(html);
				}
			}
		}

		function activeWidgetClear(currentUrl){

			const currentUrlAttrs = currentUrl.split('?').pop();
			const currentUrlData  = {};

			currentUrlAttrs.split('&').forEach(function(value,index){
				let attribute = value.split('=');
				if (typeof(attribute[1]) != "undefined") {
					currentUrlData[attribute[0]] = attribute[1];
				}
			});

			$('.widget_product_categories').each(function(){
				
				let $this = $(this),
					title = $this.find('.widget_title');

				let active = false;
				let activeValue = false;

				if ($this.find('select').length && $this.find('select').val() != '') {
					active = $this.find('select option[value="'+$this.find('select').val()+'"]').text().trim();
					activeValue = $this.find('select').val();
				} else if($this.find('.current-cat').length) {
					active = $this.find('.current-cat > a').attr('title');
					activeValue = $this.find('.current-cat > a').attr('href');
				}

				if(title.find('.clear').length && (activeValue == false || isActiveWidgetInUrl(currentUrl,activeValue) == false)) {
					title.find('.clear').remove();
					$this.find('select').val('').trigger('change.select2');
					$this.find('.current-cat').removeClass('current-cat');
				} else if (active && active.length) {

					let clearUrlData = Object.assign({}, currentUrlData);

					if (Array.isArray(active)) {active = active.join(', ')}

					let clearLink = clearActiveWidgetFromUrl(clearUrlData,copt.shopLink);

					appendActiveFilter(copt.categoriesLabel,active,clearLink,'category');

					if (title.find('.clear').length === 0) {
						title.append('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
					} else {
						title.find('.clear').replaceWith('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
					}

				}

			});

			$('.woocommerce-widget-layered-nav').each(function(){
				
				let $this  = $(this);

				let title     = $this.find('.widget_title'),
					clearLink = '#';

				let active = false;
				let activeValue = false;

				if ($this.find('select').length) {
					active = $this.find('select').val();

					activeValue = active;

					if (Array.isArray(active)) {
						if (active.length == 0 || (active.length == 1 && active[0] == '')) {
							active = false;
							activeValue = false;
						} else {
							active = active.map(value => $this.find('select option[value="'+value+'"]').text()).join(', ');
							activeValue = activeValue.join(',');
						}
					} else if(activeValue.length) {
						active = $this.find('select option[value="'+$this.find('select').val()+'"]').text();
						activeValue = $this.find('select').val();
					}

				} else {

					if ($this.find('.chosen a').length > 1) {
						active = multipleChoosenLayeredNav($this.find('.chosen a'));
						activeValue = $this.find('.chosen a').map(function() {
						    return $(this).attr('data-term');
						}).get().join(',');
					} else {
						active = $this.find('.chosen a').text();
						activeValue = $this.find('.chosen a').attr('data-term');
					}
				}

				if(title.find('.clear').length && (activeValue == false || isActiveWidgetInUrl(currentUrl,activeValue) == false)) {
					title.find('.clear').remove();
					$this.find('select').val(false).trigger('change.select2');
					$this.find('.chosen').removeClass('chosen');
				} else if (active && active.length) {

					let activeAttribute = $this.attr('data-attribute');
					let clearUrlData    = Object.assign({}, currentUrlData);

					if('filter_' + activeAttribute in clearUrlData){delete clearUrlData['filter_' + activeAttribute];}
				    if('query_type_' + activeAttribute in clearUrlData){delete clearUrlData['query_type_' + activeAttribute];}

				    if (Array.isArray(active)) {active = active.join(', ')}

					let clearLink = clearActiveWidgetFromUrl(clearUrlData,window.location.origin + window.location.pathname);
					let label = $this.attr('data-attribute-label') ? $this.attr('data-attribute-label') : title.text();

					appendActiveFilter(label,active,clearLink,activeAttribute);

					if (title.find('.clear').length === 0) {
						title.append('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
					} else {
						title.find('.clear').replaceWith('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
					}
				}

			});

			if (
				currentUrl.indexOf('min_price') != -1 ||
				currentUrl.indexOf('max_price') != -1
			) {
				$('.widget_price_filter').each(function(){
					let $this = $(this),
						title = $this.find('.widget_title'),
						active = [];

					let min_price = currentUrlData['min_price'],
						max_price = currentUrlData['max_price'];

					switch(copt.currencyPosition){
						case 'left':
							min_price = copt.currencySymbol + min_price;
							max_price = copt.currencySymbol + max_price;
						break
						case 'left_space':
							min_price = copt.currencySymbol + ' ' + min_price;
							max_price = copt.currencySymbol + ' ' + max_price;
						break
						case 'right':
							min_price = min_price + copt.currencySymbol;
							max_price = max_price + copt.currencySymbol;
						break
						case 'right_space':
							min_price = min_price + ' ' + copt.currencySymbol;
							max_price = max_price + ' ' + copt.currencySymbol;
						break
					}

					active.push(min_price);
					active.push(max_price);

					if (active.length) {

						let clearLink = '#';
						let clearUrlData = Object.assign({}, currentUrlData);

						delete clearUrlData['min_price'];
					    delete clearUrlData['max_price'];

						clearLink = clearActiveWidgetFromUrl(
							clearUrlData,
							window.location.origin + window.location.pathname
						);

						appendActiveFilter(copt.strings.priceLabel,active.join(' - '),clearLink,'price');

						if (title.find('.clear').length === 0) {
							title.append('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
						} else {
							title.find('.clear').replaceWith('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
						}
					}

				});
			} else {
				$('.widget_price_filter').each(function(){
					$(this).find('.widget_title .clear').remove();
				});
			}

			if (currentUrl.indexOf('rating_filter') != -1) {
				$('.widget_rating_filter').each(function(){

					let $this     = $(this);

					let title     = $this.find('.widget_title'),
						clearLink = '#';

					let active = false;

					if (typeof(currentUrlData['rating_filter']) != "undefined" && currentUrlData['rating_filter'] != null) {
						active = currentUrlData['rating_filter'];
					}

					if(title.find('.clear').length && (active == false || isActiveWidgetInUrl(currentUrl,active) == false)) {

						title.find('.clear').remove();
						$this.find('.chosen').removeClass('chosen');
					} else if (active) {

						
						let clearLink = '#';
						let clearUrlData = Object.assign({}, currentUrlData);

						delete clearUrlData['rating_filter'];
						
						clearLink = clearActiveWidgetFromUrl(
							clearUrlData,
							window.location.origin + window.location.pathname
						);

						appendActiveFilter(
							title.contents()
						    .filter(function() {
						        return this.nodeType === Node.TEXT_NODE;
						    }).text(),
							active,
							clearLink,
							'rating'
						);

						if (title.find('.clear').length === 0) {
							title.append('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
						} else {
							title.find('.clear').replaceWith('<a href="'+clearLink+'" class="clear">'+copt.strings.widgetClear+'</a>');
						}
						
					}

				});
			} else {
				$('.widget_rating_filter').each(function(){
					$(this).find('.widget_title .clear').remove();
					$(this).find('.chosen').removeClass('chosen');
				});
			}


			if (currentUrl.indexOf('s=') != -1 && currentUrl.indexOf('s=1') === -1) {

				let clearLink = '#';
				let clearUrlData = Object.assign({}, currentUrlData);

				delete clearUrlData['s'];
				
				clearLink = clearActiveWidgetFromUrl(
					clearUrlData,
					window.location.origin + window.location.pathname
				);
				
				appendActiveFilter(
					copt.strings.searchLabel,
				    decodeURIComponent(currentUrlData['s'].replace('+', ' ')),
				    clearLink,
				    'search'
				);

			}

		}
	
	/* Widget display handle
	----*/

		function handleAttributeWidgetDisplay(atts){

			$('.shop-widgets .widget[data-attribute]').each(function(){
				$(this).addClass('hidden');
			});

			Object.entries(atts['attributeTerms']).forEach(([taxonomy, terms]) => {

        		let taxonomy_name = taxonomy.replace(/^pa_/, "");

        		let widget = $('.widget[data-attribute="'+taxonomy_name+'"]');

				if (widget.length) {

					let active = false;

					let displayType   = widget.attr('data-display-type'),
						queryType     = widget.attr('data-query-type'),
						dataCount     = widget.attr('data-count'),
						defaultSelect = displayType == 'dropdown' ? widget.find('option:first-child').prop('outerHTML') : false;

					if ('filter_'+taxonomy_name in atts['activeFilters']) {
						active = atts['activeFilters']['filter_'+taxonomy_name].split(',');
					}

					if (terms) {

						let html = '';

						terms.forEach(function(value){

							let termObj = value,
								slug    = termObj['slug'],
								label   = termObj['name'],
								count   = termObj['count'];

							if (displayType == 'dropdown') {

								let optAttr = [
									'value="'+slug+'"',
								];

								if (active && active.includes(slug)) {
									optAttr.push('selected');
									optAttr = optAttr.filter(item => item !== 'disabled');
								}

								html += '<option '+optAttr.join(' ')+'>'+label+'</option>';

							} else {

								let liClass = [
									'woocommerce-widget-layered-nav-list__item',
									'wc-layered-nav-term'
								]

								if (active && active.includes(slug)) {
									liClass.push('chosen');

									if ('or' == queryType) {
										count = getItemCountBySlug(terms, slug); 
									}

								}

								html += '<li class="'+liClass.join(' ')+'">';
									
									html += '<a rel="nofollow" href="'+atts['termLinkOrigin']+'filter_'+taxonomy_name+'='+slug;

									if ('or' == queryType) {html += 'query_type_'+taxonomy_name+'=or';}

									html += '"';

									html += 'data-term="'+slug+'" title="'+label+'">';

										if ('image_list' == displayType && 'img' in termObj) {
											html += '<span class="term-image-wrapper"><img src="'+termObj['img']+'" alt="'+label+'" title="'+label+'" class="term-image"></span>';
										} else if('color' == displayType){
											if ('color' in termObj) {
												html += '<span style="background-color:'+termObj['color']+'" class="term-color"></span>';
											} else if('color-light' in termObj){
												html += '<span style="background-color:'+termObj['color-light']+'" class="term-color light"></span>';
											} else {
												html += '<span class="term-color empty"></span>';
											}
										}

										html += label;
									html += '</a>';

									if(dataCount == 1){html += ' <span class="count">('+count+')</span>';}

								html += '</li>';

							}

						});

						if (html.length) {

						    let $html = $('<div>').html(html); // Wrap in a jQuery object for manipulation

						    if (displayType == 'dropdown') {
						        // Move selected options to the top
						        let $options = $html.find('option');
						        let $selected = $options.filter(':selected');
						        let $notSelected = $options.not(':selected');

						        widget.find('select')
						        .html(defaultSelect)
						        .append($selected)
						        .append($notSelected);

						        widget.find('select')
						        .trigger('change.select2');

						    } else {
						        // Move items with .chosen class to the top
						        let $items = $html.find('li');
						        let $chosen = $items.filter('.chosen');
						        let $notChosen = $items.not('.chosen');

						        widget.find('.woocommerce-widget-layered-nav-list')
						        .html('')
						        .prepend($notChosen)
						        .prepend($chosen)
						        .scrollTop(0);


						    }
						}

						widget.removeClass('hidden');

					} else {
						widget.addClass('hidden');
					}

				}

			});

        	$('.woocommerce-widget-layered-nav-list').each(function(){
				layeredNavListScroll($(this));
			});

		}

		function handlePriceWidgetDisplay(priceFilter,activeFilters){

			let priceSlider = $(".price_slider");

	        if (priceSlider.length) {

	        	if (priceSlider.hasClass("ui-slider")) {
	            	priceSlider.slider( "destroy" );
	            }

	            let min_price = parseFloat(priceFilter['min_price']),
					max_price = parseFloat(priceFilter['max_price']),
					step      = parseFloat($( '.price_slider_amount' ).attr( 'data-step' )) || 1;

				['min_price', 'max_price'].forEach(key => {
				    if (key in activeFilters) {
				        const value = parseFloat(activeFilters[key]);
				        if (key === 'min_price' && value < min_price) {
				            min_price = value;
				        } else if (key === 'max_price' && value > max_price) {
				            max_price = value;
				        }
				    }
				});

				$( '.price_slider' ).slider({
					range: true,
					animate: true,
					min: min_price,
					max: max_price,
					step: step,
					values: [ min_price, max_price ],
					create: function() {
						$( '.price_slider_amount #min_price' ).val( min_price );
						$( '.price_slider_amount #max_price' ).val( max_price );

						$( '.price_label .from' ).text( min_price );
						$( '.price_label .to' ).text( max_price );

						$( document.body ).trigger( 'price_slider_create', [ min_price, max_price ] );
					},
					slide: function( event, ui ) {
						$( '.price_slider_amount #min_price' ).val( ui.values[0] );
						$( '.price_slider_amount #max_price' ).val( ui.values[1] );

						$( document.body ).trigger( 'price_slider_slide', [ ui.values[0], ui.values[1] ] );
					},
					change: function( event, ui ) {

						$( document.body ).trigger( 'price_slider_change', [ ui.values[0], ui.values[1] ] );
					}
				});

	        }

		}

		function handleRatingWidgetDisplay(ratingFilter){

			let widget = $(".shop-widgets .widget_rating_filter");

	        if (widget.length) {
	        	widget.find('ul').html(ratingFilter);
	        	widget.removeClass('hidden');
	        } else {
	        	widget.addClass('hidden');
	        }

		}

		function handleTitleSection(params){

			if (params['response']['current_results_title']) {
				$('.title-section-title h1').text(params['response']['current_results_title']);
			}

			if (params['response']['breadcrumbs']) {
				$('.et-breadcrumbs').html(params['response']['breadcrumbs'])
			}

		}

	/* Product filter
	----*/

		function ajaxProductFilter(activeFilters,filterUrl,updateHistory = true){

			$('#wrap').addClass('loading');

			let activeParams = getUrlParams();
			let baseURL = createBaseUrl(filterUrl);
			let paged = 1;

			let filterKeywords = copt.ajaxFilterKeywrods;

			if (filterKeywords.length) {

				filterKeywords.split(',').map(item => item.trim()).forEach((value) => {
					if (activeFilters.hasOwnProperty(value)) {
						delete activeFilters[value];
					}
				});

			}

			if ('paged' in activeFilters) {
				paged = activeFilters['paged'];
				delete activeFilters['paged'];
			} else {
				baseURL = baseURL.replace(/page\/\d+\/?/, '');
			}

			$.ajax({
	            url: copt.ajaxUrl, // Built-in WordPress AJAX URL for the admin area
	            type: 'POST',
	            data: {
	            	'action':'et__product_filter',
	            	'active_filters':JSON.stringify(activeFilters),
	            	'base_url':baseURL.split('?').shift(),
	            	'url_params':(baseURL.includes('?') ? baseURL.split('?').pop() : ''),
	            	'lang':$('html').attr('lang').split('-').shift(),
					'currency':copt.activeCurrency,
	            	'paged':paged
	            },
	            success: function(response) {

	            	response = JSON.parse(response);

	            	console.log(response);

	                preAfterAjaxProductFilter(response,activeFilters,baseURL);
					if (updateHistory) {
			    		updatepushState(baseURL,filterUrl);
			    	}
					activeWidgetClear(baseURL);

	            },
	            error: function(xhr, status, error) {
	                console.log(error);
	            }
	        });


		}

		function preAfterAjaxProductFilter(response,activeFilters,baseURL){

			let params = {
            	'response':response,
            	'activeFilters':activeFilters,
            	'baseURL':baseURL
            };

			afterProductFilter(params);
		}

		function afterProductFilter(params){

            let termLinkOrigin = params['baseURL']+(params['baseURL'].includes('?') ? '&' : '?');

			if (params['response']['products']) {

            	$('.product-content > .woocommerce-notices-wrapper').show();

            	$('.woocommerce-no-products-found').remove();
            	$('.no-results-form').remove();
				$('.woocommerce-info').remove();
				$('.return-to-shop').remove();

            	var $container = $('.product-content');
				if (!$container.length) return;

				// Create or reuse the list
				var $ul = $('#loop-products');
				if (!$ul.length) {
					$ul = $('<ul id="loop-products" class="loop-posts loop-products products nav-pagination"></ul>');
				}

				// If .no-vehicles-form exists inside .product-content, place before it; else append.
				var $anchor = $container.find('.no-vehicles-form').first();
				if ($anchor.length) {
					$ul.insertBefore($anchor);   // moves if it already exists elsewhere
				} else if($container.find('.woocommerce-pagination').length) {
					$ul.insertBefore($container.find('.woocommerce-pagination'));
				} else {
					$container.append($ul);
				}

            	$('#loop-products').html(params['response']['products']);

                if (params['response']['pagination']) {

					if  ($('.woocommerce-pagination').length) {
                		$('.woocommerce-pagination').html(params['response']['pagination']);
					} else {
						$('.product-content').append('<nav class="woocommerce-pagination">'+params['response']['pagination']+'</nav>');
					}

                } else {
                	$('.woocommerce-pagination').remove();
                }

                if (params['response']['found_results']) {
                	if ($('.woocommerce-result-count').length) {
                		$('.woocommerce-result-count').html(params['response']['found_results']);
                	} else {
                		$('<p class="woocommerce-result-count">'+params['response']['found_results']+'</p>')
                		.insertBefore($('.woocommerce-ordering'));
                	}
                }


                if (params['response']['price_filter']) {
			        handlePriceWidgetDisplay(params['response']['price_filter'],params['activeFilters']);
	            } else if(params['wcPriceFilter']) {
			        handlePriceWidgetDisplay(params['wcPriceFilter'],params['activeFilters']);
	            }

	            if (params['response']['rating_filter']) {
			        handleRatingWidgetDisplay(params['response']['rating_filter']);
	            } else if(params['wcPriceFilter']) {
			        handleRatingWidgetDisplay(params['wcRatingFilter']);
	            } else {
					$(".shop-widgets .widget_rating_filter").addClass('hidden');
	            }

	            if (params['response']['product_terms']) {

	            	let atts = {
	            		'attributeTerms':params['response']['product_terms'],
	            		'activeFilters':params['activeFilters'],
	            		'termLinkOrigin':termLinkOrigin
	            	};

	            	handleAttributeWidgetDisplay(atts);
	            } else if(params['wcAttributesTerms']) {

	            	let atts = {
	            		'attributeTerms':params['wcAttributesTerms'],
	            		'activeFilters':params['activeFilters'],
	            		'termLinkOrigin':termLinkOrigin
	            	};

	            	handleAttributeWidgetDisplay(atts);
	            } else {
	            	$('.shop-widgets .widget[data-attribute]').each(function(){
						$(this).addClass('hidden');
					});
	            }

	            if (params['response']['product_categories']) {

	            	let atts = {
	            		'categoryTerms':params['response']['product_categories'],
	            		'activeFilters':params['activeFilters'],
	            	};

	            	handleCategoryWidgetDisplay(atts);
	            }

            } else if(params['response']['not_found']) {
            	
            	$('.product-content > .woocommerce-before-shop-loop ').hide();
            	$('.product-content > .woocommerce-notices-wrapper').hide();
            	$('#loop-products').replaceWith(params['response']['not_found']);

            	if (!$('.return-to-shop').length) {
            		$('<a href="'+copt.shopLink+'" class="et-button button return-to-shop medium">'+copt.strings.clearSelection+'</a>')
            		.insertAfter('.woocommerce-no-products-found');
            	}

            	$('.woocommerce-pagination').remove();

            	$('.widget[data-query-type="or"] .chosen .count').text('(0)');

			    if (params['activeFilters'].hasOwnProperty('s')) {
					getSpellingSuggestions(String(params['activeFilters']['s'].trim())).then(offerKeywordSuggestions);
				}
            	
            }

            let foundResultsText;
            let foundTotalClass = 'positive';

			if ('found_total' in params['response']) {
			    if (params['response']['found_total'] == 1) {
			        foundResultsText = copt.strings.foundResult.replace('##', 1);
			    } else if(params['response']['found_total'] == 0) {
			        foundTotalClass = 'negative';
			    	foundResultsText = copt.strings.foundResults.replace('##', 0);
			    } else {
			        foundResultsText = copt.strings.foundResults.replace('##', params['response']['found_total']);
			    }
			} else {
				foundTotalClass = 'negative';
			    foundResultsText = copt.strings.foundResults.replace('##', 0);
			}

        	if ($('.found-total').length) {
        		$('.found-total')
        		.removeClass('active')
        		.removeClass('first')
        		.removeClass('negative')
        		.removeClass('positive')
        		.html(foundResultsText);

        		setTimeout(function(){
        			$('.found-total')
        			.addClass('active')
        			.addClass(foundTotalClass);
        		},1);

        	} else {
        		$('<div class="found-total active first '+foundTotalClass+'">'+foundResultsText+'</div>')
        		.insertAfter($('.shop-widgets.sidebar-widget-area + .widget-area-shadow'));

        		$('.shop-widgets.sidebar-widget-area').addClass('has-found-total');
        	}

            if (!params['activeFilters'].hasOwnProperty('s') && $('.woocommerce-ordering select[name="orderby"]').val() == 'relevance') {
            	if ($('.woocommerce-ordering select[name="orderby"] option[value="'+copt.defaultSort+'"]').length) {
            		$('.woocommerce-ordering select[name="orderby"]').val(copt.defaultSort)
            	} else {
            		$('.woocommerce-ordering select[name="orderby"] option[value="relevance"]').attr('value','menu_order').text(copt.strings.defaultSortLabel)
            		$('.woocommerce-ordering select[name="orderby"]').val('menu_order');
            	}
            }

            if (params['response']['categories_carousel']) {

	            if (!$('.categories-carousel-container').length) {
	            	$('.product-categories-carousel-container')
					.html(params['response']['categories_carousel']);
	            } else {
	            	$('.categories-carousel-container').replaceWith(params['response']['categories_carousel']);
	            }

				let opt = {
					'gap_d':16,
					'gap_tb':16,
					'gap_mb':8,
					'gap_mbs':8
				}

				buildSwiperCarousel($('.categories-carousel-container'),opt);
				lazyLoad(document.querySelector('.categories-carousel-container'));

				setTimeout(function(){
	    			$('.categories-carousel').find('.swiper-slide').removeClass('swiper-hidden');
	    		},50);

            } else {
            	$('.categories-carousel-container').remove();
            }

            handleTitleSection(params);
			
			$('#wrap').removeClass('loading');

			lazyLoad(document.querySelector('#loop-products'));

			if ($('.active-filters').length) {
				$('.active-filters').html('');
			}

			$('.et__product_ajax_search.embed').removeClass('loading');
			$('.et__product_ajax_search_keyword_suggestions').remove();

			const scrollOffset = $('.header.sticky-true').length ? $('.header.sticky-true:visible').outerHeight() : 0;
			etSmoothScrollTo($('.woocommerce-before-shop-loop'), scrollOffset, 500);

			revealWidgetsForCategory({ includeAncestors: false });
		}

	$(document).on('click', 'a[href*="make="]:not([href*="page"])', function () {
	  // Remove the 'vehicle' cookie for the root path
	  $.removeCookie('vehicle', { path: '/' });
	});


	if (typeof(copt.productAjaxFilter) != "undefined" && copt.productAjaxFilter == 1) {
		$(document).on('click', 'a[href*="?ca="][href*="ajax=true"]', function(e) {
		  e.preventDefault(); // stop the default navigation

		  // Extract the value after ?ca=
		  const href = $(this).attr('href');
		  const urlParams = new URLSearchParams(href.split('?')[1]);
		  const category = urlParams.get('ca');

		  if (category) {
		    // Build the new URL and navigate
		    const newUrl = `${copt.siteUrl}/${copt.productCatBase}/${category}/`;
		    window.location.href = newUrl;
		  }
		});
		$(document).on('click', 'a[href*="?brand="][href*="ajax=true"]', function(e) {
		  e.preventDefault(); // stop the default navigation

		  // Extract the value after ?ca=
		  const href = $(this).attr('href');
		  const urlParams = new URLSearchParams(href.split('?')[1]);
		  const brand = urlParams.get('brand');

		  if (brand) {
		    // Build the new URL and navigate
		    const newUrl = `${copt.shopLink}/?filter_brand=${brand}/`;
		    window.location.href = newUrl;
		  }
		});
	}

	if (
		$('body').hasClass('woocommerce-js') && 
		$('body').hasClass('woocommerce-page') &&
		$('body').hasClass('archive')
	) {

		var currentUrl = window.location.href;

		if (!history.state) {
	        history.replaceState({ url: window.location.href }, "", window.location.href);
	    }

		activeWidgetClear(currentUrl);

		$('.woocommerce-widget-layered-nav-list').each(function(){
			layeredNavListScroll($(this));
		});

		$('.woocommerce-pagination a').each(function(){
    		let $this = $(this),
    			link  = $this.attr('href').endsWith("/") ? $this.attr('href').slice(0, -1).split('/') : $this.attr('href').split('/');

    		if (link.pop() == 1) {
    			link.splice(-1);
    			$this.attr('href',link.join('/')+'/');
    		}
    	});

		$('body').on('click','.widget .cat-parent > .cat-toggle',function(){

			let parent = $(this).parent();

			parent.toggleClass('current-cat-parent');

			if (parent.hasClass('current-cat-parent')) {
				parent.children('ul').css('height', '0');
				gsap.to(parent.children('ul'), {
			        duration: 0.3,
			        height: 'auto',
			        ease: 'power3.out'
			    });
			} else {
				parent.children('ul').css('height', 'auto');
				gsap.to(parent.children('ul'), {
			        duration: 0.3,
			        height: '0',
			        ease: 'power3.out',
			    });
			}

		});

		$("body").on('keyup input', 'input.term-search', function (e) {

		    let filter = $(this).val();
		    let $list = $(this).next('ul');

		    // Filter the list items based on the search input
		    $list.find('li').each(function () {
		        if ($(this).find('a').attr('title').search(new RegExp(filter, "i")) < 0) {
		            $(this).hide(0);
		        } else {
		            $(this).show();
		        }
		    });

		    // Remove existing 'no-terms-found' message before adding a new one
		    $(this).nextAll('.no-terms-found').remove();

		    if ($list.find('li:visible').length === 0 && filter !== '') {
		        // Show 'no-terms-found' message only if there are no visible items and input is not empty
		        $('<p class="no-terms-found">' + copt.strings.noTermsFound + '</p>').insertAfter($list);
		    }

		    // Handle clear event: If the input is empty, reset the list and remove the 'no-terms-found' message
		    if (filter === '') {
		        $list.find('li').show();  // Show all items
		        $(this).nextAll('.no-terms-found').remove(); // Remove 'no-terms-found' message if input is cleared
		    }

		    // Recalculate scroll behavior
		    layeredNavListScroll($list,false);
		});

		if (typeof(copt.productAjaxFilter) != "undefined" && copt.productAjaxFilter == 1) {

			var loggedIn           = ($('body').hasClass('logged-in')) ? true : false;
			var wcAttributesTerms  =  false;
			var wcPriceFilter      =  false;
			var wcRatingFilter     =  false;
			var productIndex       = false;
			var categoryIndex      = false;
			var templateStructure  = '';
			var categoriesCarousel = '';
			var noProductsFound    = '';
			var banners            = '';

			let activeFilters      = {};
			let baseURL            = {'base' : copt.shopLink};
			let activeParams       = getUrlParams();

			/* Fetch products data
			------------*/

				$.ajax({

					url: copt.ajaxUrl,
		            type: 'POST',
		            data: {
		            	'action':'et__fetch_products_data',
		            	'lang':$('html').attr('lang').split('-').shift()
		            },
		            success: function(response) {

		            	if (response) {

		            		response = JSON.parse(response)

		            		if (response['attributes_terms']) {
		            			wcAttributesTerms = response['attributes_terms'];
		            		}
		            		if (response['price_filter']) {
		            			wcPriceFilter = response['price_filter'];
		            		}
		            		if (response['rating_filter']) {
		            			wcRatingFilter = response['rating_filter'];
		            		}

		            	}

		            },
		            error: function(xhr, status, error) {
		                console.log(error);
		            }

			    });

			/* Static
			------------*/

				revealWidgetsForCategory({ includeAncestors: false });

				$('.woocommerce-widget-layered-nav').each(function(){
				
					let $this  = $(this);

					if ($this.attr('data-display-type') != "dropdown" && $this.find('span.count')) {
						$this.attr('data-count',1);
					}

				});

				$('.widget_product_categories').each(function(){
				
					let $this  = $(this);

					if ($this.attr('data-display-type') == "dropdown") {

						const options = listToSelect($this.find('ul.product-categories'));

						if (options != '') {
							$('<select id="'+$this.attr('id')+'" class="'+$this.attr('class')+'"><option value="">'+copt.strings.any+' '+$this.attr('data-title')+'</option>'+options+'</select>')
							.insertAfter($this.find('ul.product-categories'));
    						$this.find('ul.product-categories').remove();
    						$('select#'+$this.attr('id')).select2();
						}

					}

					let active = false;

					if ($this.find('select').length) {
						active = $this.find('select').val();

					} else if($this.find('.current-cat').length) {
						active = $this.find('.current-cat > a').attr('href');
					}

					if (active && active.length) {
						baseURL['base'] = active;

						let catUrl = active.endsWith("/") ? active.slice(0, -1) : active;
						activeFilters['category'] = catUrl.split('/').pop();

					}

				});

				if (activeParams) {
					Object.entries(activeParams).forEach(([key, value]) => {
						if (key != 'category') {
							baseURL[key] = value;
							activeFilters[key] = value;
						}
					});

					// if ('s' in activeParams && activeParams['s'] != '') {
					// 	activeFilters['s'] = activeParams['s'];
					// 	baseURL['s'] = activeFilters['s'];

					// 	ajaxProductFilter(activeFilters,baseURL);
					// }

				}

			/* Events
			------------*/

				$('body').on('click','.woocommerce-before-shop-loop .sale-products', function(e){
					e.preventDefault();

					$(this).toggleClass('chosen');

					if ($(this).hasClass('chosen')) {
						activeFilters['sale'] = 1;
					} else {
						delete activeFilters['sale'];
					}

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('body').on('click','.widget .cat-item > a', function(e){
					e.preventDefault();

					let activeCategory = $(this);

					if (activeCategory.attr('href') != '#') {

						activeCategory
						.parents('.widget')
						.find('.current-cat')
						.removeClass('current-cat');

						activeCategory
						.parent()
						.addClass('current-cat');

						baseURL['base'] = activeCategory.attr('href');

						let catUrl = activeCategory.attr('href').endsWith("/") ? activeCategory.attr('href').slice(0, -1) : activeCategory.attr('href');
						activeFilters['category'] = catUrl.split('/').pop();
					}

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('body').on('click','.categories-carousel .category a', function(e){
					e.preventDefault();

					let activeCategory = $(this);

					if (activeCategory.attr('href') != '#') {

						activeCategory
						.parents('.categories-carousel')
						.find('.current-cat')
						.removeClass('current-cat');

						activeCategory
						.parent()
						.addClass('current-cat');

						if ($('.widget .cat-item > a[href="'+activeCategory.attr('href')+'"]').length) {
							$('.widget .cat-item > a[href="'+activeCategory.attr('href')+'"]').trigger('click');
						} else if($('select.widget_product_categories').length){

							$('select.widget_product_categories')
						    .val(activeCategory.attr('href'))
						    .trigger('change')
						    .trigger({
						        type: 'select2:select',
						        params: { data: { id: activeCategory.attr('href') } }
						    });

						}
					}


				});

				$('body').on('select2:select','select.widget_product_categories',function(){

					let activeCategory = $(this).val();

					if (activeCategory != '') {

					    baseURL['base'] = $(this).val();

					    let catUrl = activeCategory.endsWith("/") ? activeCategory.slice(0, -1) : activeCategory;
						activeFilters['category'] = catUrl.split('/').pop();

					}

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('body').on('click','.wc-layered-nav-term  > a', function(e){
					e.preventDefault();

					let activeTerm  = $(this),
						attribute   = activeTerm.parents('.widget').attr('data-attribute'),
						queryType   = activeTerm.parents('.widget').attr('data-query-type');

					activeTerm
					.parent()
					.toggleClass('chosen');

					if ('and' == queryType) {


						activeTerm
						.parent()
						.siblings()
						.removeClass('chosen');

						if (activeTerm.parent().hasClass('chosen')) {
							activeFilters['filter_'+attribute] = activeTerm.attr('data-term');
							baseURL['filter_'+attribute]   = activeFilters['filter_'+attribute];
						} else {
							delete activeFilters['filter_'+attribute];
							delete baseURL['filter_'+attribute];
						}

					} else {

						let chosenTerms = [];

						activeTerm.parents('.widget').find('.chosen').each(function(){
							chosenTerms.push($(this).find('a').attr('data-term'));
						});

						if (chosenTerms.length) {
							activeFilters['filter_'+attribute] = chosenTerms.join(',');
							baseURL['query_type_'+attribute] = 'or';
							baseURL['filter_'+attribute] = activeFilters['filter_'+attribute];
						} else {
							delete activeFilters['filter_'+attribute];
							delete baseURL['filter_'+attribute];
							delete activeFilters['query_type_'+attribute];
							delete baseURL['query_type_'+attribute];
						}

					}

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('.woocommerce-widget-layered-nav-dropdown').on('submit',function(e){
					
					e.preventDefault();
					let trigger = $(this);
					layeredNavDropdownEvents(trigger,activeFilters,baseURL);

				});

				$('body').on('select2:unselect','select.woocommerce-widget-layered-nav-dropdown',function(){
					let trigger = $(this);
					layeredNavDropdownEvents(trigger,activeFilters,baseURL);
				});

				$('body').on('click','.widget_price_filter button', function(e){
					
					e.preventDefault();

					activeFilters['min_price'] = $(this).parents('.widget_price_filter').find('input[name="min_price"]').val();
					activeFilters['max_price'] = $(this).parents('.widget_price_filter').find('input[name="max_price"]').val();

					baseURL['min_price'] = activeFilters['min_price'];
					baseURL['max_price'] = activeFilters['max_price'];

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('body').on('click','.wc-layered-nav-rating > a', function(e){
					e.preventDefault();

					let activeTerm = $(this),
						attribute  = 'rating_filter';

					activeTerm
					.parent()
					.toggleClass('chosen');

					let termUrl = activeTerm.attr('href');

					if (activeTerm.parent().hasClass('chosen')) {
						activeFilters['rating_filter'] = termUrl.split('=').pop();
						baseURL['rating_filter'] = activeFilters['rating_filter'];
					} else {
						delete activeFilters['rating_filter'];
						delete baseURL['rating_filter'];
					}

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('.woocommerce-ordering').on('submit',function(e){
					e.preventDefault();

					if ($(this).find('select').val()) {
						
						activeFilters['orderby'] = $(this).find('select').val();
						baseURL['orderby'] = activeFilters['orderby'];

						ajaxProductFilter(activeFilters,baseURL);

					}

				});

				$('body').on('click','.woocommerce-pagination a', function(e){
					e.preventDefault();

					let $this = $(this),
						link  = $this.attr('href').split('?').shift(),
						paged = link.endsWith("/") ? link.slice(0, -1).split('/').pop() : link.split('/').pop();

					activeFilters['paged'] = paged;
					baseURL['base'] = $this.attr('href').split('?').shift();

					ajaxProductFilter(activeFilters,baseURL);

				});

				$('body').on('click','.widget_title .clear, .active-filters a, .return-to-shop, .et__product_ajax_search.embed > a', function(e){
					e.preventDefault();

					let $this = $(this);

					activeFilters = getUrlParams($this.attr('href'));

					if (false == activeFilters) {
						activeFilters = {};
					}

					baseURL = {'base' : $this.attr('href').split('?').shift()};

					if ($this.attr('href').includes(copt.categoryBase)) {
						let catUrl = $this.attr('href').split('?').shift();

						if (catUrl.endsWith("/")) {catUrl = catUrl.slice(0, -1)}
						activeFilters['category'] = catUrl.split('/').pop();
					}

					if (Object.keys(activeFilters).length) {
						Object.entries(activeFilters).forEach(([key, value]) => {
							if (key != 'category') {
								baseURL[key] = value;
							}
						});
					}

					if (
						$this.hasClass('clear-all-filters') || 
						$this.hasClass('search') || 
						$this.hasClass('return-to-shop')
					) {
						$('.et__product_ajax_search.embed > .query').val('');
						$('.et__product_ajax_search.embed > a').remove();
					}

					if (
						$this.parent().hasClass('et__product_ajax_search') ||
						$this.hasClass('category') ||
						$this.parents('.widget_product_categories').length
					) {
						$('.et__product_ajax_search.embed > a').remove();
					}

					if ($this.hasClass('clear')) {
						$this.parent().parent().toggleClass('close');
					} else if(!$this.hasClass('return-to-shop')) {
						$this.remove();
					}

					if (
						$this.hasClass('clear-all-filters') ||  
						$this.hasClass('return-to-shop')
					) {
						if ($('.woocommerce-ordering select[name="orderby"] option[value="'+copt.defaultSort+'"]').length) {
		            		$('.woocommerce-ordering select[name="orderby"]').val(copt.defaultSort)
		            	} else {
		            		$('.woocommerce-ordering select[name="orderby"] option[value="relevance"]').attr('value','menu_order').text(copt.strings.defaultSortLabel)
		            		$('.woocommerce-ordering select[name="orderby"]').val('menu_order');
		            	}
					}

					if ($this.hasClass('return-to-shop')) {
                		$.removeCookie('vehicle', { path: '/' });
                		window.location.href = copt.shopLink;

                		return;
					}

					ajaxProductFilter(activeFilters,baseURL);

				});

				if($('.shop-widgets .widget[data-display-type]').length){
					$('form[name="product-vehicle-filter"] input[type="submit"]').on('click',function(e){
						
						e.preventDefault();

						if(!$(this).parents('.shop-top-widgets').length){
							return;
						}

						let vin = $(this).parents('form').find('input.vin');

						if (vin.length && vin.val()) {

							activeFilters['vin'] = vin.val();
							baseURL['vin'] = activeFilters['vin'];

						} else {

							$(this).parents('form').find('select').each(function(){
								if ($(this).val()) {
									let name = $(this).attr('name');

									if (name == "year") {name = "yr"}

									activeFilters[name] = $(this).val();
									baseURL[name] = activeFilters[name];

								}
							});

							delete activeFilters['vin'];
							delete baseURL['vin'];

						}

						if (Object.keys(activeFilters).length) {
							ajaxProductFilter(activeFilters,baseURL);
						}

					});
				}

				window.addEventListener('popstate', function(event) {
				    if (!event.state) return; // Ensure state exists

				    const url = event.state.url;

				    activeFilters = getUrlParams(url) || {};

				    baseURL = { 'base': url.split('?').shift() };

				    if (url.includes(copt.categoryBase)) {
				        let catUrl = url.split('?').shift();
				        if (catUrl.endsWith("/")) { catUrl = catUrl.slice(0, -1); }
				        activeFilters['category'] = catUrl.split('/').pop();
				    }

				    // ! important Also pass paged here //

				    Object.entries(activeFilters).forEach(([key, value]) => {
				        if (key !== 'category') {
				            baseURL[key] = value;
				        }
				    });

				    // DO NOT pushState inside popstate
				    ajaxProductFilter(activeFilters, baseURL,false);
				});

		} else {

			$('body').on('click','.widget_title .clear', function(e){
				e.preventDefault();

				if ($(this).attr('href') != '#') {
					window.location.assign($(this).attr('href'));
				}

				$(this).parent().parent().toggleClass('close');

			});
		}

	}

	$('.et__product_ajax_search > .query').on('focus',function(){
		$(this).parent().addClass('active');
	});

	$('.et__product_ajax_search > .query').on('focusout',function(){
		$(this).parent().removeClass('active');
	});

})(jQuery);

(function($){

	"use strict";

	var productDefaultPrice = $('.summary-details > .price').length ? $('.summary-details > .price').html() : 0;
	var restoreDefaultPrice = false;
	var variableProductData = {};

	/* ---------------------------
	 *  WOO VARIATION EVENT HOOKS
	 * ---------------------------
	 * We let Woo fire its official events and update our UI there.
	 * No manual timeouts, no DOM polling.
	 */
	// if ($('.variations_form').length) {
		// Clear previous handlers if any (safety)
		$(document)
			.off('found_variation.et hide_variation.et reset_data.et', 'form.variations_form');

		// When a valid variation is found, Woo passes fresh price_html
		$(document).on('found_variation.et', 'form.variations_form', function (event, variation) {
			const $form = $(this);

			// Update price in your custom spot
			if (variation && variation.price_html) {
				$('.summary-details p.price').html(variation.price_html);
			}

			// Build variableProductData (as in your original)
			const formData   = $form.serializeArray();
			const attributes = {};
			formData.forEach(field => {
				if (field.name && field.name.startsWith('attribute_')) {
					attributes[field.name] = field.value;
				}
			});

			const product_id   = parseInt($form.find('input[name="product_id"]').val());
			const variation_id = parseInt(variation.variation_id);
			const quantity     = parseInt($form.find('input.qty').val()) || 1;

			variableProductData['variable_product_id'] = product_id;
			variableProductData['variation_id']        = variation_id;
			variableProductData['quantity']            = quantity;
			variableProductData['variations']          = attributes;

			// Once a variation is found, we are not restoring default anymore
			restoreDefaultPrice = false;
		});

		// No valid variation selected / cleared → restore default price
		$(document).on('hide_variation.et reset_data.et', 'form.variations_form', function () {
			if (typeof productDefaultPrice !== 'undefined' && productDefaultPrice) {
				$('.summary-details p.price').html(productDefaultPrice);
			}
			restoreDefaultPrice = false;
		});
	// }

	/* ---------------------------------
	 *  (Optional) Legacy helper kept,
	 *  but not used for DOM polling.
	 * --------------------------------- */
	function updateCurrentPrice(restoreDefault = false) {
		const setPriceHtml = (selector, priceHtml) => { $(selector).html(priceHtml); };
		const updateAllPriceDisplays = (priceHtml) => {
			setPriceHtml('.summary-details p.price', priceHtml);
		};

		// Only used when explicitly asked to restore default (we prefer event-driven)
		if (restoreDefault && typeof productDefaultPrice !== 'undefined' && productDefaultPrice) {
			updateAllPriceDisplays(productDefaultPrice);
			restoreDefaultPrice = false;
			return;
		}
	}

	/* ---------------------------------
	 *  UI sync: select ↔ swatches
	 * --------------------------------- */
	function updateCustomVariationAttr(attr, etAttr) {
		if (attr.val()) {
			etAttr.find('select').val(attr.val());
			etAttr
				.find('a[data-value="' + attr.val() + '"]')
				.addClass('chosen')
				.siblings()
				.removeClass('chosen');
			etAttr.parent().find('.clear').addClass('active');
		} else {
			etAttr.find('select').val('');
			etAttr.find('a[data-value="' + attr.val() + '"]').removeClass('chosen');
			if (
				!etAttr.parent().find('.chosen').length ||
				!etAttr.parent().find('select').val()
			) {
				etAttr.parent().find('.clear').removeClass('active');
			}
		}
	}

	/* ---------------------------------
	 *  Apply a swatch value to the real select
	 *  and let Woo evaluate immediately.
	 * --------------------------------- */
	function updateDefaultVariationAttr(attr, val) {
		const $summary = attr.parents('.summary-details');
		const selectId = attr.parent().attr('id').replace(/^attr-/, '');
		const $form    = $summary.find('form.variations_form');

		// Update the real select & trigger Woo change
		$summary.find('select#' + selectId)
			.val(val)
			.trigger('change');

		// Force Woo to evaluate with current attrs now
		$form.trigger('check_variations');

		// DO NOT call updateCurrentPrice here — Woo events above will handle the price
	}

	/* ---------------------------------
	 *  Clear link state toggler
	 * --------------------------------- */
	function toggleVariationClear(attr) {
		if (attr.is('select') && attr.val()) {
			attr.parents('.et__variation-swatches').find('.et__clear_variation-swatches a').addClass('active');
		} else if (attr.is('a') && attr.hasClass('chosen')) {
			attr.parents('.et__variation-swatches').find('.et__clear_variation-swatches a').addClass('active');
		} else {
			attr.parents('.et__variation-swatches').find('.et__clear_variation-swatches a').removeClass('active');
		}
	}

	/* ---------------------------------
	 *  Gallery thumbs active state
	 * --------------------------------- */
	$('body').on('click', '.single-product-main .flex-control-thumbs li', function () {
		$(this).addClass('active').siblings().removeClass('active');
	});

	/* ---------------------------------
	 *  SKU copy to clipboard
	 * --------------------------------- */
	$('.sku_wrapper').on('click', function () {
		let skuText = $(this).find('.sku').text().trim();
		if (skuText) {
			let tempInput = $('<input>');
			$('body').append(tempInput);
			tempInput.val(skuText).select();
			document.execCommand('copy');

			alert(copt.strings.skuCopy.replace('##', skuText));
			tempInput.remove();
		}
	});

	/* ---------------------------------
	 *  Clear all chosen variation swatches
	 * --------------------------------- */
	$('body').on('click', '.et__clear_variation-swatches a', function (e) {
		e.preventDefault();

		let $this = $(this);
		const $summary = $this.parents('.summary');

		$this.parents('.et__variation-swatches').find('.chosen').removeClass('chosen');
		$this.parents('.et__variation-swatches').find('select').val('');
		$this.removeClass('active');

		// Tell subsequent logic to show default price once reset completes
		restoreDefaultPrice = true;

		// Trigger Woo's native reset (will emit reset_data → our handler restores price)
		$summary.find('.reset_variations').trigger('click');
	});

	/* ---------------------------------
	 *  Reset button: rely on Woo event
	 *  (no manual price mutation here)
	 * --------------------------------- */
	$('body').on('click', '.summary .reset_variations', function () {
		// We purposefully do not call updateCurrentPrice here.
		// Woo will trigger 'reset_data', and our handler will restore default price.
		restoreDefaultPrice = true;
	});

	/* ---------------------------------
	 *  Swatch click → update select and check
	 * --------------------------------- */
	$('body').on('click', '.et__variation-swatches a.variation-item-opt', function (e) {
		e.preventDefault();

		let $this = $(this);

		$this.toggleClass('chosen').siblings().removeClass('chosen');
		toggleVariationClear($this);
		updateDefaultVariationAttr($this, $this.attr('data-value'));
	});

	/* ---------------------------------
	 *  Select change → mirror into swatches
	 * --------------------------------- */
	$('.variation-item-opt.select').on('change', function () {
		let $this = $(this);
		toggleVariationClear($this);
		updateDefaultVariationAttr($this, $this.val());
	});

	/* ---------------------------------
	 *  On load: keep swatches in sync with selects
	 * --------------------------------- */
	if ($('.variations_form.cart').length) {
		let variationForm = $('.variations_form.cart'),
			variationAtts = variationForm.find('select');

		variationAtts.each(function () {
			let $this  = $(this),
				etThis = $this.parents('.summary').find('.et__variation-swatches #attr-' + $this.attr('id'));

			if (typeof etThis !== "undefined" && etThis.length) {
				updateCustomVariationAttr($this, etThis);
				$this.on('change', function () {
					updateCustomVariationAttr($this, etThis);
				});
			}
		});
	}

	/* ---------------------------------
	 *  Quantity click zones +/- 1
	 * --------------------------------- */
	$('body').on('click', '.summary-details .quantity', function (e) {
		let $this      = $(this);
		let offset     = $this.offset();
		let relativeX  = e.pageX - offset.left; // click X inside .quantity
		let width      = $this.width();
		let $qtyInput  = $this.children('.qty');
		let currentVal = parseInt($qtyInput.val()) || 1;

		if (relativeX <= 48) {
			if (currentVal > 1) {
				$qtyInput.val(currentVal - 1);
			}
		} else if (relativeX >= width - 48) {
			$qtyInput.val(currentVal + 1);
		}
	});


})(jQuery);