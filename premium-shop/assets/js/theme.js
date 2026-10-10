/**
 * Premium Shop — global front-end script (vanilla JS, no dependency).
 *
 * Drawers & modal (focus trap, Esc, scroll lock), sticky header, announcement
 * bar, navigation toggles, live search, product rails, scroll reveal,
 * wishlist, quick view, side cart, quantity buttons, newsletter, toasts.
 */
(function () {
	'use strict';

	var S = window.premiumShop || {};
	var I18N = S.i18n || {};
	var doc = document;
	var root = doc.documentElement;
	var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
	function debounce(fn, wait) {
		var t;
		return function () {
			var args = arguments, self = this;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(self, args); }, wait);
		};
	}
	function escapeHtml(str) {
		return String(str).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}
	function store(key, value) {
		try {
			if (value === undefined) { return window.localStorage.getItem(key); }
			window.localStorage.setItem(key, value);
		} catch (e) { return null; }
		return null;
	}
	function wcAjaxUrl(endpoint, params) {
		if (!S.wcAjax) { return ''; }
		var url = S.wcAjax.replace('%%endpoint%%', endpoint).replace('%25%25endpoint%25%25', endpoint);
		Object.keys(params || {}).forEach(function (k) {
			url += (url.indexOf('?') > -1 ? '&' : '?') + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
		});
		return url;
	}

	/* ------------------------------------------------------------------
	 * Toast
	 * ------------------------------------------------------------------ */
	var toastTimer;
	function toast(message, node) {
		var el = $('[data-ps-toast]');
		if (!el) { return; }
		el.classList.toggle('ps-toast--rich', !!node);
		el.textContent = node ? '' : message;
		if (node) { el.appendChild(node); }
		el.hidden = false;
		requestAnimationFrame(function () { el.classList.add('is-visible'); });
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			el.classList.remove('is-visible');
			setTimeout(function () { el.hidden = true; }, 400);
		}, node ? 4200 : 2600);
	}

	/* ------------------------------------------------------------------
	 * Drawers & modal
	 * ------------------------------------------------------------------ */
	var activeDrawer = null;
	var lastFocus = null;
	var FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';

	function focusables(el) {
		return $$(FOCUSABLE, el).filter(function (n) {
			return n.offsetWidth > 0 || n.offsetHeight > 0 || n === doc.activeElement;
		});
	}

	function setExpanded(id, state) {
		$$('[aria-controls="' + id + '"]').forEach(function (btn) {
			btn.setAttribute('aria-expanded', state ? 'true' : 'false');
		});
	}

	function openDrawer(el, trigger) {
		if (!el) { return false; }
		if (activeDrawer && activeDrawer !== el) { closeDrawer(activeDrawer, true); }

		lastFocus = trigger || doc.activeElement;
		el.hidden = false;
		el.classList.add('is-active');
		if (!el.getAttribute('role')) {
			el.setAttribute('role', 'dialog');
			el.setAttribute('data-ps-role-added', '1');
		}
		el.setAttribute('aria-modal', 'true');
		void el.offsetWidth; // Reflow so the transition runs.
		el.classList.add('is-open');
		doc.body.classList.add('ps-lock');
		setExpanded(el.id, true);
		activeDrawer = el;

		setTimeout(function () {
			var target = $('[data-autofocus]', el) || $('input[type="search"]', el) || focusables(el)[0];
			if (target) { target.focus({ preventScroll: true }); }
		}, 80);

		doc.dispatchEvent(new CustomEvent('ps:drawer-open', { detail: { el: el } }));
		return true;
	}

	function closeDrawer(el, immediate) {
		if (!el || !el.classList.contains('is-open')) { return; }
		el.classList.remove('is-open');
		setExpanded(el.id, false);
		doc.body.classList.remove('ps-lock');
		if (activeDrawer === el) { activeDrawer = null; }

		var done = function () {
			if (el.classList.contains('is-open')) { return; }
			el.classList.remove('is-active');
			if (!el.classList.contains('ps-drawer--desktop-static')) { el.hidden = true; }
			if (el.getAttribute('data-ps-role-added')) {
				el.removeAttribute('role');
				el.removeAttribute('data-ps-role-added');
			}
			el.removeAttribute('aria-modal');
			doc.dispatchEvent(new CustomEvent('ps:drawer-close', { detail: { el: el } }));
		};

		if (immediate || reduceMotion) { done(); } else { setTimeout(done, 450); }

		if (!immediate && lastFocus && doc.contains(lastFocus)) {
			lastFocus.focus({ preventScroll: true });
		}
	}

	doc.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-ps-open]');
		if (opener) {
			var target = doc.getElementById(opener.getAttribute('data-ps-open'));
			if (target && openDrawer(target, opener)) { e.preventDefault(); }
			return;
		}
		var closer = e.target.closest('[data-ps-close]');
		if (closer) {
			e.preventDefault();
			closeDrawer(closer.closest('[data-ps-drawer]'));
		}
	});

	doc.addEventListener('keydown', function (e) {
		if (!activeDrawer) { return; }
		if (e.key === 'Escape') {
			closeDrawer(activeDrawer);
			return;
		}
		if (e.key === 'Tab') {
			var items = focusables(activeDrawer);
			if (!items.length) { return; }
			var first = items[0], last = items[items.length - 1];
			if (e.shiftKey && doc.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});

	// A static desktop sidebar must not stay "open" after resizing.
	window.addEventListener('resize', debounce(function () {
		if (activeDrawer && activeDrawer.classList.contains('ps-drawer--desktop-static') && window.innerWidth >= 1024) {
			closeDrawer(activeDrawer, true);
		}
	}, 150));

	window.PremiumShop = { openDrawer: openDrawer, closeDrawer: closeDrawer, toast: toast, wcAjaxUrl: wcAjaxUrl };

	/* ------------------------------------------------------------------
	 * Sticky header & back to top
	 * ------------------------------------------------------------------ */
	var header = $('[data-ps-header]');
	var backTop = $('[data-ps-back-top]');
	var ticking = false;

	function onScroll() {
		var y = window.scrollY || window.pageYOffset;
		if (header) { header.classList.toggle('is-scrolled', y > 8); }
		if (backTop) { backTop.classList.toggle('is-visible', y > 700); }
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { requestAnimationFrame(onScroll); ticking = true; }
	}, { passive: true });
	onScroll();

	if (backTop) {
		backTop.addEventListener('click', function (e) {
			e.preventDefault();
			window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
			var main = doc.getElementById('ps-main');
			if (main) { main.focus({ preventScroll: true }); }
		});
	}

	/* ------------------------------------------------------------------
	 * Announcement bar
	 * ------------------------------------------------------------------ */
	var promo = $('[data-ps-promo]');
	if (promo) {
		var promoKey = 'ps_promo_closed';
		if (store(promoKey) === promo.getAttribute('data-ps-promo')) {
			promo.classList.add('is-hidden');
		}
		var msgs = $$('.ps-promo__msg', promo);
		var index = 0;
		var paused = false;
		if (msgs.length > 1 && !reduceMotion) {
			promo.addEventListener('mouseenter', function () { paused = true; });
			promo.addEventListener('mouseleave', function () { paused = false; });
			promo.addEventListener('focusin', function () { paused = true; });
			promo.addEventListener('focusout', function () { paused = false; });
			setInterval(function () {
				if (paused || doc.hidden) { return; }
				msgs[index].classList.remove('is-active');
				msgs[index].setAttribute('aria-hidden', 'true');
				index = (index + 1) % msgs.length;
				msgs[index].classList.add('is-active');
				msgs[index].removeAttribute('aria-hidden');
			}, 4500);
		}
		var closeBtn = $('[data-ps-promo-close]', promo);
		if (closeBtn) {
			closeBtn.addEventListener('click', function () {
				promo.classList.add('is-hidden');
				store(promoKey, promo.getAttribute('data-ps-promo'));
			});
		}
	}

	/* ------------------------------------------------------------------
	 * Navigation (dropdowns, mega menu, mobile accordions)
	 * ------------------------------------------------------------------ */
	doc.addEventListener('click', function (e) {
		var toggle = e.target.closest('.ps-nav__toggle');
		if (toggle) {
			var li = toggle.parentElement;
			var open = !li.classList.contains('is-open');
			// Close siblings.
			$$(':scope > li.is-open', li.parentElement).forEach(function (sib) {
				if (sib !== li) {
					sib.classList.remove('is-open');
					var t = $(':scope > .ps-nav__toggle', sib);
					if (t) { t.setAttribute('aria-expanded', 'false'); }
				}
			});
			li.classList.toggle('is-open', open);
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			return;
		}
		// Click outside closes desktop dropdowns.
		if (!e.target.closest('.ps-nav')) {
			$$('.ps-nav li.is-open').forEach(function (li) {
				li.classList.remove('is-open');
				var t = $(':scope > .ps-nav__toggle', li);
				if (t) { t.setAttribute('aria-expanded', 'false'); }
			});
		}
	});

	doc.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape' || activeDrawer) { return; }
		var open = $('.ps-nav li.is-open');
		if (open) {
			open.classList.remove('is-open');
			var t = $(':scope > .ps-nav__toggle', open);
			if (t) { t.setAttribute('aria-expanded', 'false'); t.focus(); }
		}
	});

	/* ------------------------------------------------------------------
	 * Live search
	 * ------------------------------------------------------------------ */
	function initLiveSearch(form) {
		var input = $('input[type="search"]', form);
		var box = $('.ps-live-results', form);
		var status = $('[data-ps-live-status]', form);
		if (!input || !box || !S.wcAjax) { return; }

		var controller = null;
		var activeIndex = -1;
		var cache = {};

		function items() { return $$('a', box); }

		function setActive(i) {
			var list = items();
			list.forEach(function (a) { a.classList.remove('is-active'); a.removeAttribute('aria-selected'); });
			activeIndex = i;
			if (i >= 0 && list[i]) {
				list[i].classList.add('is-active');
				list[i].setAttribute('aria-selected', 'true');
				input.setAttribute('aria-activedescendant', list[i].id);
				list[i].scrollIntoView({ block: 'nearest' });
			} else {
				input.removeAttribute('aria-activedescendant');
			}
		}

		function hide() {
			box.hidden = true;
			input.setAttribute('aria-expanded', 'false');
			setActive(-1);
		}

		function highlight(text, term) {
			var safe = escapeHtml(text);
			var t = term.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
			if (!t) { return safe; }
			return safe.replace(new RegExp('(' + escapeHtml(t) + ')', 'ig'), '<mark>$1</mark>');
		}

		function render(data, term) {
			var html = '';
			var n = 0;
			var uid = input.id;
			if (data.products && data.products.length) {
				html += '<div class="ps-live-results__group"><p class="ps-live-results__label">' + escapeHtml(I18N.products || 'Products') + '</p>';
				data.products.forEach(function (p) {
					html += '<a class="ps-live-results__item" role="option" id="' + uid + '-opt-' + (n++) + '" href="' + escapeHtml(p.url) + '">' +
						'<img src="' + escapeHtml(p.image) + '" alt="" width="52" height="64" loading="lazy">' +
						'<span class="ps-live-results__meta"><span class="ps-live-results__title">' + highlight(p.title, term) + '</span>' +
						(p.category ? '<span class="ps-live-results__cat">' + escapeHtml(p.category) + '</span>' : '') + '</span>' +
						'<span class="ps-live-results__price">' + (p.price || '') + '</span></a>';
				});
				html += '</div>';
			}
			if (data.categories && data.categories.length) {
				html += '<div class="ps-live-results__group"><p class="ps-live-results__label">' + escapeHtml(I18N.categories || 'Categories') + '</p>';
				data.categories.forEach(function (c) {
					html += '<a class="ps-live-results__item" role="option" id="' + uid + '-opt-' + (n++) + '" href="' + escapeHtml(c.url) + '">' +
						'<span class="ps-live-results__meta"><span class="ps-live-results__title">' + highlight(c.name, term) + '</span>' +
						'<span class="ps-live-results__cat">' + c.count + '</span></span></a>';
				});
				html += '</div>';
			}
			if (!n) {
				html = '<p class="ps-live-results__empty">' + escapeHtml(I18N.noResults || 'No products found.') + '</p>';
			} else if (data.allUrl) {
				html += '<a class="ps-live-results__all" role="option" id="' + uid + '-opt-' + (n++) + '" href="' + escapeHtml(data.allUrl) + '">' +
					escapeHtml(I18N.viewAll || 'View all results') + (data.total ? ' (' + data.total + ')' : '') + ' →</a>';
			}
			box.innerHTML = html;
			box.hidden = false;
			input.setAttribute('aria-expanded', 'true');
			activeIndex = -1;
			if (status) { status.textContent = n ? (data.total || n) + ' ' + (I18N.products || '') : (I18N.noResults || ''); }
		}

		var search = debounce(function () {
			var term = input.value.trim();
			if (term.length < 2) { hide(); return; }
			if (cache[term]) { render(cache[term], term); return; }
			if (controller) { controller.abort(); }
			controller = window.AbortController ? new AbortController() : null;

			box.innerHTML = '<p class="ps-live-results__loading">' + escapeHtml(I18N.searching || '…') + '</p>';
			box.hidden = false;

			fetch(wcAjaxUrl('ps_search', { term: term, lang: S.lang || '' }), {
				credentials: 'same-origin',
				signal: controller ? controller.signal : undefined
			})
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || !res.success) { throw new Error('search'); }
					cache[term] = res.data;
					if (input.value.trim() === term) { render(res.data, term); }
				})
				.catch(function (err) {
					if (err && err.name === 'AbortError') { return; }
					hide();
				});
		}, 220);

		input.addEventListener('input', search);
		input.addEventListener('focus', function () { if (input.value.trim().length >= 2 && box.innerHTML) { box.hidden = false; input.setAttribute('aria-expanded', 'true'); } });
		input.addEventListener('keydown', function (e) {
			var list = items();
			if (box.hidden || !list.length) { return; }
			if (e.key === 'ArrowDown') { e.preventDefault(); setActive((activeIndex + 1) % list.length); }
			else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(activeIndex <= 0 ? list.length - 1 : activeIndex - 1); }
			else if (e.key === 'Enter' && activeIndex >= 0) { e.preventDefault(); window.location.href = list[activeIndex].href; }
			else if (e.key === 'Escape') { e.stopPropagation(); hide(); }
		});
		doc.addEventListener('click', function (e) { if (!form.contains(e.target)) { hide(); } });
	}
	$$('[data-ps-live-search]').forEach(initLiveSearch);

	/* ------------------------------------------------------------------
	 * Product rails
	 * ------------------------------------------------------------------ */
	$$('[data-ps-rail]').forEach(function (rail) {
		var track = $('ul', rail);
		var section = rail.closest('.ps-rail-section');
		if (!track || !section) { return; }
		var prev = $('[data-ps-rail-prev]', section);
		var next = $('[data-ps-rail-next]', section);

		function update() {
			var max = track.scrollWidth - track.clientWidth - 2;
			var x = Math.abs(track.scrollLeft);
			if (prev) { prev.disabled = x <= 2; }
			if (next) { next.disabled = x >= max; }
			var nav = section.querySelector('.ps-rail-nav');
			if (nav) { nav.style.visibility = max <= 0 ? 'hidden' : ''; }
		}
		function go(dir) {
			track.scrollBy({ left: dir * track.clientWidth * 0.85, behavior: reduceMotion ? 'auto' : 'smooth' });
		}
		if (prev) { prev.addEventListener('click', function () { go(root.dir === 'rtl' ? 1 : -1); }); }
		if (next) { next.addEventListener('click', function () { go(root.dir === 'rtl' ? -1 : 1); }); }
		track.addEventListener('scroll', debounce(update, 60), { passive: true });
		window.addEventListener('resize', debounce(update, 150));
		update();
	});

	/* ------------------------------------------------------------------
	 * Scroll reveal
	 * ------------------------------------------------------------------ */
	var revealObserver = null;
	if (!reduceMotion && !S.reducedMotion && doc.body.classList.contains('ps-animations') && 'IntersectionObserver' in window) {
		root.classList.add('ps-animations-ready');
		revealObserver = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting) {
					entry.target.classList.add('is-revealed');
					revealObserver.unobserve(entry.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
	}
	function reveal(ctx) {
		$$('[data-reveal]:not(.is-revealed)', ctx).forEach(function (el) {
			if (revealObserver) { revealObserver.observe(el); } else { el.classList.add('is-revealed'); }
		});
	}
	reveal();

	/* ------------------------------------------------------------------
	 * Quantity buttons
	 * ------------------------------------------------------------------ */
	function enhanceQuantities(ctx) {
		$$('.quantity', ctx).forEach(function (wrap) {
			var input = $('input.qty', wrap);
			if (!input || input.type === 'hidden' || wrap.getAttribute('data-ps-qty')) { return; }
			wrap.setAttribute('data-ps-qty', '1');

			function make(dir, label) {
				var b = doc.createElement('button');
				b.type = 'button';
				b.className = 'ps-qty-btn';
				b.setAttribute('aria-label', label);
				b.innerHTML = dir < 0
					? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg>'
					: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>';
				b.addEventListener('click', function () {
					var step = parseFloat(input.step) || 1;
					var min = input.min !== '' ? parseFloat(input.min) : 0;
					var max = input.max !== '' ? parseFloat(input.max) : Infinity;
					var val = parseFloat(input.value) || 0;
					var nextVal = Math.min(max, Math.max(min, val + dir * step));
					if (nextVal !== val) {
						input.value = nextVal;
						input.dispatchEvent(new Event('input', { bubbles: true }));
						input.dispatchEvent(new Event('change', { bubbles: true }));
					}
				});
				return b;
			}
			wrap.insertBefore(make(-1, '−'), input);
			wrap.appendChild(make(1, '+'));
		});
	}
	enhanceQuantities();

	/* ------------------------------------------------------------------
	 * Cart fragments, side cart & AJAX add to cart
	 * ------------------------------------------------------------------ */
	function applyFragments(fragments) {
		if (!fragments) { return; }
		Object.keys(fragments).forEach(function (selector) {
			$$(selector).forEach(function (el) {
				var tmp = doc.createElement('div');
				tmp.innerHTML = fragments[selector];
				if (tmp.firstElementChild) { el.replaceWith(tmp.firstElementChild); }
			});
		});
		bumpCount();
	}

	function bumpCount() {
		$$('.ps-cart-count--header').forEach(function (el) {
			el.classList.remove('is-bump');
			void el.offsetWidth;
			el.classList.add('is-bump');
		});
	}

	/* ------------------------------------------------------------------
	 * Add to cart feedback: the visitor stays on the page. The product
	 * image flies to the cart icon, the button confirms, a toast offers
	 * the cart link.
	 * ------------------------------------------------------------------ */
	function productImageFor(button) {
		if (!button) { return null; }
		var box = button.closest('.ps-card, li.product, .product, .ps-qv, [data-ps-sticky-cart]');
		var img = box ? $('img', box) : null;
		if ((!img || !img.offsetWidth) && button.closest('form.cart')) {
			img = $('.woocommerce-product-gallery__image img, .woocommerce-product-gallery img');
		}
		return img && img.offsetWidth ? img : null;
	}

	function cartTarget() {
		return $$('.ps-header__cart, [data-ps-cart-target]').filter(function (el) {
			var r = el.getBoundingClientRect();
			return r.width > 0 && r.height > 0;
		})[0] || null;
	}

	function flyToCart(img) {
		var target = cartTarget();
		if (!img || !target || reduceMotion || S.reducedMotion || !img.animate) {
			if (target) { shake(target); }
			return;
		}
		var from = img.getBoundingClientRect();
		var to = target.getBoundingClientRect();
		var size = Math.min(from.width, from.height, 220);
		var clone = doc.createElement('img');
		clone.src = img.currentSrc || img.src;
		clone.alt = '';
		clone.className = 'ps-fly';
		clone.style.width = clone.style.height = size + 'px';
		clone.style.left = (from.left + (from.width - size) / 2) + 'px';
		clone.style.top = (from.top + (from.height - size) / 2) + 'px';
		doc.body.appendChild(clone);
		var dx = to.left + to.width / 2 - (from.left + from.width / 2);
		var dy = to.top + to.height / 2 - (from.top + from.height / 2);
		var lift = Math.min(160, Math.abs(dy) / 2 + 60);
		var anim = clone.animate([
			{ transform: 'translate(0,0) scale(1) rotate(0deg)', opacity: 1, borderRadius: '16px' },
			{ transform: 'translate(' + dx * 0.45 + 'px,' + (dy * 0.45 - lift) + 'px) scale(.55) rotate(-8deg)', opacity: 0.95, borderRadius: '30%', offset: 0.5 },
			{ transform: 'translate(' + dx + 'px,' + dy + 'px) scale(.08) rotate(10deg)', opacity: 0.4, borderRadius: '50%' }
		], { duration: 850, easing: 'cubic-bezier(.5,-0.1,.35,1)' });
		anim.onfinish = function () {
			clone.remove();
			shake(target);
			bumpCount();
		};
	}

	function shake(el) {
		el.classList.remove('is-receiving');
		void el.offsetWidth;
		el.classList.add('is-receiving');
		setTimeout(function () { el.classList.remove('is-receiving'); }, 900);
	}

	function markAdded(button) {
		if (!button) { return; }
		button.classList.add('ps-is-added');
		var label = button.hasAttribute('data-ps-label') ? button.getAttribute('data-ps-label') : button.innerHTML;
		if (I18N.added && button.tagName !== 'INPUT') {
			button.setAttribute('data-ps-label', label);
			button.innerHTML = '<span class="ps-added-check" aria-hidden="true"></span>' + escapeHtml(I18N.added);
		}
		clearTimeout(button._psAdded);
		button._psAdded = setTimeout(function () {
			button.classList.remove('ps-is-added');
			if (button.hasAttribute('data-ps-label')) {
				button.innerHTML = button.getAttribute('data-ps-label');
				button.removeAttribute('data-ps-label');
			}
		}, 2200);
	}

	function addedToast(button, img) {
		var box = doc.createElement('div');
		box.className = 'ps-toast__body';
		var name = '';
		var holder = button ? button.closest('.ps-card, li.product, .product, .ps-qv') : null;
		var title = holder ? $('.ps-card__title, .woocommerce-loop-product__title, .product_title, h2, h3', holder) : null;
		if (!title && button && button.closest('form.cart')) { title = $('.product_title'); }
		if (title) { name = title.textContent.replace(/\s+/g, ' ').trim(); }
		box.innerHTML = (img ? '<img class="ps-toast__img" src="' + escapeHtml(img.currentSrc || img.src) + '" alt="">' : '<span class="ps-toast__check" aria-hidden="true"></span>') +
			'<span class="ps-toast__text"><strong>' + escapeHtml(I18N.addedCart || '✓') + '</strong>' + (name ? '<span>' + escapeHtml(name) + '</span>' : '') + '</span>' +
			(S.cartUrl ? '<a class="ps-toast__link" href="' + escapeHtml(S.cartUrl) + '">' + escapeHtml(I18N.viewCart || '') + '</a>' : '');
		toast('', box);
	}

	function openCartDrawer() {
		var drawer = doc.getElementById('ps-cart-drawer');
		if (drawer && S.cartDrawer) {
			openDrawer(drawer, $('.ps-header__cart'));
			return true;
		}
		return false;
	}

	if (window.jQuery) {
		window.jQuery(doc.body).on('added_to_cart', function (event, fragments, hash, $button) {
			var button = $button && $button[0] ? $button[0] : null;
			var image = productImageFor(button);
			bumpCount();
			if (activeDrawer && activeDrawer.id === 'ps-quick-view') { closeDrawer(activeDrawer, true); }
			markAdded(button);
			if (openCartDrawer()) { return; }
			flyToCart(image);
			addedToast(button, image);
		});
		window.jQuery(doc.body).on('updated_wc_div updated_cart_totals wc_fragments_refreshed', function () {
			enhanceQuantities();
		});
	}

	/*
	 * Block cart & checkout change the cart through the Store API, which does not
	 * refresh WooCommerce fragments: watch the block cart store and refresh the
	 * header count, side cart and free-shipping bar when quantities, items or
	 * discounts change.
	 */
	function watchBlockCart() {
		if (!doc.querySelector('.wp-block-woocommerce-cart, .wp-block-woocommerce-checkout') || !S.wcAjax) { return; }
		var data = window.wp && window.wp.data;
		var store;
		try { store = data && data.select('wc/store/cart'); } catch (e) { store = null; }
		if (!store || !store.getCartTotals || !store.getCartData) { return; }
		var last = null;
		var refresh = debounce(function () {
			fetch(wcAjaxUrl('get_refreshed_fragments'), { method: 'POST', credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) { applyFragments(res && res.fragments); })
				.catch(function () {});
		}, 400);
		data.subscribe(function () {
			var s = data.select('wc/store/cart');
			var totals = s.getCartTotals();
			var cart = s.getCartData();
			if (!totals || !cart) { return; }
			var key = [cart.itemsCount, totals.total_items, totals.total_discount].join('|');
			if (last === null) { last = key; return; }
			if (key !== last) { last = key; refresh(); }
		});
	}
	window.addEventListener('load', watchBlockCart);

	/* Contact form: send without reloading (falls back to a normal POST). */
	$$('[data-ps-contact-form]').forEach(function (form) {
		var status = form.parentNode.querySelector('[data-ps-contact-status]');
		form.addEventListener('submit', function (e) {
			if (!window.fetch || !window.FormData) { return; }
			e.preventDefault();
			if (form.reportValidity && !form.reportValidity()) { return; }
			var btn = $('button[type="submit"]', form);
			if (btn) { btn.classList.add('is-loading'); btn.disabled = true; }
			var show = function (ok, text) {
				if (!status) { return; }
				status.innerHTML = '<p class="ps-notice ps-notice--' + (ok ? 'success' : 'error') + '">' + escapeHtml(text) + '</p>';
				status.scrollIntoView({ block: 'nearest', behavior: S.reducedMotion ? 'auto' : 'smooth' });
			};
			fetch(form.getAttribute('action'), { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' }, body: new FormData(form) })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					var text = res && res.data && res.data.message ? res.data.message : (I18N.error || 'Error');
					show(!!(res && res.success), text);
					if (res && res.success) {
						$$('textarea, input[name="ps_phone"], input[name="ps_order"]', form).forEach(function (f) { f.value = ''; });
						var c = $('input[name="ps_consent"]', form);
						if (c) { c.checked = false; }
					}
				})
				.catch(function () { show(false, I18N.error || 'Error'); })
				.then(function () { if (btn) { btn.classList.remove('is-loading'); btn.disabled = false; } });
		});
	});

	/* Order received: a short burst of confetti. */
	(function () {
		var canvas = $('[data-ps-confetti]');
		if (!canvas || reduceMotion || S.reducedMotion || !canvas.getContext) { return; }
		var ctx = canvas.getContext('2d');
		var styles = window.getComputedStyle(root);
		var colors = ['--ps-accent', '--ps-success', '--ps-primary'].map(function (v) { return styles.getPropertyValue(v).trim(); }).filter(Boolean).concat(['#ffc857', '#ff8a5b', '#f4d35e']);
		var dpr = Math.min(window.devicePixelRatio || 1, 2);
		var w, h, pieces = [], start = performance.now();
		function size() {
			w = canvas.offsetWidth; h = canvas.offsetHeight;
			canvas.width = w * dpr; canvas.height = h * dpr;
			ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
		}
		size();
		window.addEventListener('resize', size);
		for (var i = 0; i < 160; i++) {
			pieces.push({
				x: w / 2 + (Math.random() - 0.5) * w * 0.3,
				y: h * 0.35,
				vx: (Math.random() - 0.5) * 14,
				vy: -Math.random() * 13 - 4,
				r: Math.random() * Math.PI,
				vr: (Math.random() - 0.5) * 0.3,
				s: 6 + Math.random() * 7,
				c: colors[i % colors.length]
			});
		}
		function frame(now) {
			var t = now - start;
			ctx.clearRect(0, 0, w, h);
			pieces.forEach(function (p) {
				p.vy += 0.32; p.vx *= 0.99; p.x += p.vx; p.y += p.vy; p.r += p.vr;
				ctx.save();
				ctx.globalAlpha = Math.max(0, 1 - t / 4200);
				ctx.translate(p.x, p.y); ctx.rotate(p.r);
				ctx.fillStyle = p.c;
				ctx.fillRect(-p.s / 2, -p.s / 4, p.s, p.s / 2);
				ctx.restore();
			});
			if (t < 4200) { requestAnimationFrame(frame); } else { canvas.remove(); }
		}
		setTimeout(function () { requestAnimationFrame(frame); }, 450);
	}());

	/* Compact language menu: close when tapping outside or pressing Escape. */
	doc.addEventListener('click', function (e) {
		$$('[data-ps-lang-mini][open]').forEach(function (d) {
			if (!d.contains(e.target)) { d.removeAttribute('open'); }
		});
	});
	doc.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') { return; }
		$$('[data-ps-lang-mini][open]').forEach(function (d) {
			d.removeAttribute('open');
			var s = $('summary', d);
			if (s) { s.focus(); }
		});
	});

	/**
	 * Add to cart through WooCommerce's own AJAX endpoint.
	 *
	 * @param {number} productId Product ID.
	 * @param {number} qty Quantity.
	 * @param {HTMLElement} button Triggering button.
	 */
	function ajaxAddToCart(productId, qty, button) {
		var body = new URLSearchParams();
		body.append('product_id', productId);
		body.append('quantity', qty || 1);
		if (button) { button.classList.add('loading'); button.disabled = true; }

		return fetch(wcAjaxUrl('add_to_cart'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || res.error) {
					if (res && res.product_url) { window.location.href = res.product_url; }
					throw new Error('add_to_cart');
				}
				applyFragments(res.fragments);
				if (window.jQuery) {
					window.jQuery(doc.body).trigger('added_to_cart', [res.fragments, res.cart_hash, button ? window.jQuery(button) : null]);
				} else if (!openCartDrawer()) {
					toast('✓');
				}
			})
			.catch(function () { toast(I18N.error || 'Error'); })
			.then(function () {
				if (button) { button.classList.remove('loading'); button.disabled = false; }
			});
	}

	/* ------------------------------------------------------------------
	 * Wishlist (stored in the browser, no account needed)
	 * ------------------------------------------------------------------ */
	var WL_KEY = 'ps_wishlist';
	function wlGet() {
		try {
			var v = JSON.parse(store(WL_KEY) || '[]');
			return Array.isArray(v) ? v.map(Number).filter(Boolean) : [];
		} catch (e) { return []; }
	}
	function wlSet(list) { store(WL_KEY, JSON.stringify(list.slice(0, 60))); }
	function wlSync() {
		var list = wlGet();
		$$('[data-ps-wishlist]').forEach(function (btn) {
			var on = list.indexOf(Number(btn.getAttribute('data-ps-wishlist'))) > -1;
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		$$('[data-ps-wishlist-count]').forEach(function (el) {
			el.textContent = list.length;
			el.hidden = !list.length;
		});
	}

	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-ps-wishlist]');
		if (!btn) { return; }
		e.preventDefault();
		var id = Number(btn.getAttribute('data-ps-wishlist'));
		var list = wlGet();
		var pos = list.indexOf(id);
		if (pos > -1) {
			list.splice(pos, 1);
			toast(I18N.removedWishlist || '♡');
			var page = btn.closest('[data-ps-wishlist-page]');
			if (page) {
				var card = btn.closest('li');
				if (card) { card.remove(); }
				if (!list.length) { renderWishlistEmpty(page); }
			}
		} else {
			list.unshift(id);
			toast(I18N.addedWishlist || '♥');
		}
		wlSet(list);
		wlSync();
	});
	window.addEventListener('storage', function (e) { if (e.key === WL_KEY) { wlSync(); } });
	wlSync();

	function renderWishlistEmpty(page) {
		var tpl = $('template[data-ps-wishlist-empty]');
		page.innerHTML = tpl ? tpl.innerHTML : '<p>' + escapeHtml(I18N.wishlistEmpty || '') + '</p>';
	}

	var wishlistPage = $('[data-ps-wishlist-page]');
	if (wishlistPage) {
		var ids = wlGet();
		if (!ids.length || !S.wcAjax) {
			renderWishlistEmpty(wishlistPage);
		} else {
			fetch(wcAjaxUrl('ps_wishlist', { ids: ids.join(','), lang: S.lang || '' }), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || !res.success || !res.data.count) { renderWishlistEmpty(wishlistPage); return; }
					wishlistPage.innerHTML = res.data.html;
					// Forget products that no longer exist.
					wlSet(ids.filter(function (id) { return res.data.ids.indexOf(id) > -1; }));
					wlSync();
					enhanceQuantities(wishlistPage);
				})
				.catch(function () { renderWishlistEmpty(wishlistPage); });
		}
	}

	/* ------------------------------------------------------------------
	 * Quick view
	 * ------------------------------------------------------------------ */
	var modal = doc.getElementById('ps-quick-view');
	var modalContent = modal ? $('[data-ps-modal-content]', modal) : null;

	function initQuickViewContent(ctx) {
		enhanceQuantities(ctx);
		wlSync();
		var slides = $('[data-ps-qv-slides]', ctx);
		var dots = $$('.ps-qv__dot', ctx);
		if (slides && dots.length) {
			slides.addEventListener('scroll', debounce(function () {
				var i = Math.round(slides.scrollLeft / slides.clientWidth);
				dots.forEach(function (d, n) { d.classList.toggle('is-active', n === i); });
			}, 50), { passive: true });
		}
		var form = $('.ps-qv__form', ctx);
		if (form) {
			form.addEventListener('submit', function (e) {
				e.preventDefault();
				var btn = $('[data-ps-qv-add]', form);
				var qty = $('input.qty', form);
				ajaxAddToCart(btn.getAttribute('data-ps-qv-add'), qty ? qty.value : 1, btn);
			});
		}
		initQuickViewVariations(ctx);
	}

	/**
	 * Add a product form to the cart in the background: the form is posted to
	 * the product page (same server-side handling as a normal submit, so
	 * variations, grouped products and add-on plugins keep working), then the
	 * cart fragments are refreshed. The visitor stays on the page.
	 *
	 * @param {HTMLFormElement} form Product form.
	 * @param {HTMLElement} btn Add to cart button.
	 */
	function submitInBackground(form, btn) {
		if (btn.classList.contains('loading')) { return; }
		btn.classList.add('loading');
		btn.disabled = true;
		var data = new FormData(form);
		if (!data.has('add-to-cart')) { data.append('add-to-cart', btn.value || form.getAttribute('data-product_id') || ''); }
		fetch(form.getAttribute('action') || window.location.href, { method: 'POST', credentials: 'same-origin', body: data })
			.then(function (r) { return r.text(); })
			.then(function (html) {
				var page = new DOMParser().parseFromString(html, 'text/html');
				var err = page.querySelector('.woocommerce-error li, .woocommerce-error, .wc-block-components-notice-banner.is-error');
				if (err) { throw new Error(err.textContent.replace(/\s+/g, ' ').trim()); }
				return fetch(wcAjaxUrl('get_refreshed_fragments'), { method: 'POST', credentials: 'same-origin' });
			})
			.then(function (r) { return r.json(); })
			.then(function (res) {
				applyFragments(res && res.fragments);
				if (window.jQuery) {
					window.jQuery(doc.body).trigger('added_to_cart', [res.fragments, res.cart_hash, window.jQuery(btn)]);
				} else {
					markAdded(btn);
					if (!openCartDrawer()) { flyToCart(productImageFor(btn)); addedToast(btn, productImageFor(btn)); }
				}
			})
			.catch(function (error) { toast((error && error.message && error.message !== 'Failed to fetch') ? error.message : (I18N.error || 'Error')); })
			.then(function () {
				btn.classList.remove('loading');
				btn.disabled = false;
			});
	}

	/**
	 * Product page and quick view forms: stay on the page when adding.
	 * "Buy now" keeps its normal behaviour (straight to the checkout).
	 */
	doc.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.matches || !form.matches('form.cart') || !S.wcAjax || !window.fetch || !window.FormData || S.ajaxCart === false) { return; }
		if (e.submitter && e.submitter.name === 'ps_buy_now') { return; }
		if (form.closest('.ps-no-ajax') || form.hasAttribute('data-ps-no-ajax')) { return; }
		var btn = e.submitter && e.submitter.classList.contains('single_add_to_cart_button') ? e.submitter : $('.single_add_to_cart_button', form);
		if (!btn) { return; }
		e.preventDefault();
		if (btn.classList.contains('disabled')) { return; }
		if (form.classList.contains('variations_form')) {
			var vid = $('input[name="variation_id"]', form);
			if (!vid || !parseInt(vid.value, 10)) { return; }
		}
		submitInBackground(form, btn);
	});

	function initQuickViewVariations(ctx) {
		var vform = $('[data-ps-qv-variations] form.variations_form', ctx);
		if (vform && window.jQuery && window.jQuery.fn.wc_variation_form) {
			window.jQuery(vform).wc_variation_form();
		}
	}

	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-ps-quick-view]');
		if (!btn || !modal || !modalContent) { return; }
		e.preventDefault();
		modal.classList.remove('ps-modal--video');
		modalContent.classList.add('is-loading');
		modalContent.innerHTML = '<span class="ps-spinner" role="status" aria-label="' + escapeHtml(I18N.loading || '') + '"></span>';
		openDrawer(modal, btn);

		fetch(wcAjaxUrl('ps_quick_view', { product_id: btn.getAttribute('data-ps-quick-view'), lang: S.lang || '' }), { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) { throw new Error('qv'); }
				modalContent.classList.remove('is-loading');
				modalContent.innerHTML = res.data.html;
				modal.setAttribute('aria-labelledby', 'ps-qv-title');
				initQuickViewContent(modalContent);
				var title = $('#ps-qv-title', modalContent);
				if (title) { title.setAttribute('tabindex', '-1'); title.focus({ preventScroll: true }); }
			})
			.catch(function () {
				// Never a dead end: open the product page instead.
				var card = btn.closest('.ps-card, li.product, .product');
				var link = card ? $('a[href]:not([href="#"]):not([data-ps-quick-view])', card) : null;
				if (link) { window.location.href = link.href; return; }
				modalContent.classList.remove('is-loading');
				modalContent.innerHTML = '<p class="ps-live-results__empty">' + escapeHtml(I18N.error || '') + '</p>';
			});
	});

	doc.addEventListener('ps:drawer-close', function (e) {
		if (e.detail.el === modal && modalContent) {
			modalContent.innerHTML = '';
			modal.removeAttribute('aria-labelledby');
		}
	});

	/* ------------------------------------------------------------------
	 * Newsletter (AJAX, progressive enhancement)
	 * ------------------------------------------------------------------ */
	$$('[data-ps-newsletter]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var msg = $('[data-ps-newsletter-msg]', form);
			var email = $('input[type="email"]', form);
			var consent = $('input[name="ps_consent"]', form);
			var btn = $('button[type="submit"]', form);

			if (email && !email.checkValidity()) { email.reportValidity(); return; }
			if (consent && !consent.checked) { consent.reportValidity(); return; }

			var data = new FormData(form);
			data.set('action', 'premium_shop_newsletter');
			if (btn) { btn.disabled = true; }

			fetch(S.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					var ok = res && res.success;
					msg.textContent = res && res.data && res.data.message ? res.data.message : (I18N.error || '');
					msg.classList.toggle('is-success', !!ok);
					msg.classList.toggle('is-error', !ok);
					if (ok) { form.classList.add('is-done'); }
				})
				.catch(function () {
					msg.textContent = I18N.error || '';
					msg.classList.add('is-error');
				})
				.then(function () { if (btn) { btn.disabled = false; } });
		});
	});

	// Expose helpers for shop.js / product.js.
	window.PremiumShop.enhanceQuantities = enhanceQuantities;
	window.PremiumShop.reveal = reveal;
	window.PremiumShop.wishlistSync = wlSync;
	window.PremiumShop.ajaxAddToCart = ajaxAddToCart;
	window.PremiumShop.modal = modal;
	window.PremiumShop.modalContent = modalContent;
}());
