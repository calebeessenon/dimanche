/**
 * Premium Shop — shop listings: instant AJAX filtering, price range,
 * sorting, pagination, grid/list view.
 *
 * Progressive enhancement: without JavaScript the filter form submits
 * normally and every filter keeps working.
 */
(function () {
	'use strict';

	var cfg = window.premiumShopShop || {};
	var PS = window.PremiumShop || {};
	var I18N = (window.premiumShop && window.premiumShop.i18n) || {};
	var doc = document;

	function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
	function debounce(fn, wait) {
		var t;
		return function () {
			var args = arguments;
			clearTimeout(t);
			t = setTimeout(function () { fn.apply(null, args); }, wait);
		};
	}

	var results = $('[data-ps-region="results"]');
	if (!results) { return; }

	/* Live region for screen readers. */
	var live = doc.createElement('p');
	live.className = 'screen-reader-text';
	live.setAttribute('aria-live', 'polite');
	doc.body.appendChild(live);
	results.removeAttribute('aria-live');

	/* ------------------------------------------------------------------
	 * Grid / list view
	 * ------------------------------------------------------------------ */
	function currentView() {
		var saved = null;
		try { saved = window.localStorage.getItem('ps_view'); } catch (e) {}
		return saved || cfg.defaultView || 'grid';
	}

	function applyView(view) {
		$$('ul.products', results).forEach(function (ul) { ul.classList.toggle('is-list', view === 'list'); });
		$$('[data-ps-view]').forEach(function (btn) {
			btn.setAttribute('aria-pressed', btn.getAttribute('data-ps-view') === view ? 'true' : 'false');
		});
	}

	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-ps-view]');
		if (!btn) { return; }
		var view = btn.getAttribute('data-ps-view');
		try { window.localStorage.setItem('ps_view', view); } catch (err) {}
		applyView(view);
	});

	/* ------------------------------------------------------------------
	 * Price range sliders
	 * ------------------------------------------------------------------ */
	function initRange(form) {
		var range = $('[data-ps-range]', form);
		if (!range) { return; }

		var min = parseFloat(range.getAttribute('data-min'));
		var max = parseFloat(range.getAttribute('data-max'));
		var from = $('[data-ps-range-from]', range);
		var to = $('[data-ps-range-to]', range);
		var minInput = $('[data-ps-range-min]', form);
		var maxInput = $('[data-ps-range-max]', form);

		function paint() {
			var a = parseFloat(from.value), b = parseFloat(to.value);
			range.style.setProperty('--ps-from', ((a - min) / (max - min) * 100) + '%');
			range.style.setProperty('--ps-to', ((b - min) / (max - min) * 100) + '%');
		}

		function fromSliders(changed) {
			var a = parseFloat(from.value), b = parseFloat(to.value);
			if (a > b) {
				if (changed === from) { from.value = b; a = b; } else { to.value = a; b = a; }
			}
			minInput.value = a > min ? Math.round(a) : '';
			maxInput.value = b < max ? Math.round(b) : '';
			paint();
		}

		function fromInputs() {
			var a = minInput.value === '' ? min : Math.max(min, Math.min(max, parseFloat(minInput.value)));
			var b = maxInput.value === '' ? max : Math.max(min, Math.min(max, parseFloat(maxInput.value)));
			from.value = Math.min(a, b);
			to.value = Math.max(a, b);
			paint();
		}

		from.addEventListener('input', function () { fromSliders(from); });
		to.addEventListener('input', function () { fromSliders(to); });
		from.addEventListener('change', function () { minInput.dispatchEvent(new Event('change', { bubbles: true })); });
		to.addEventListener('change', function () { maxInput.dispatchEvent(new Event('change', { bubbles: true })); });
		minInput.addEventListener('input', fromInputs);
		maxInput.addEventListener('input', fromInputs);
		paint();
	}

	/* ------------------------------------------------------------------
	 * Filter form → clean URL
	 * ------------------------------------------------------------------ */
	function formToUrl(form) {
		var params = {};
		var order = [];
		new FormData(form).forEach(function (value, name) {
			value = String(value).trim();
			if (value === '') { return; }
			var key = name.replace(/\[\]$/, '');
			if (!params[key]) { params[key] = []; order.push(key); }
			params[key].push(value);
		});

		// "post_type" is only needed together with a search term.
		if (!params.s) { delete params.post_type; }

		var url = new URL(form.getAttribute('action'), window.location.origin);
		order.forEach(function (key) {
			if (params[key]) { url.searchParams.set(key, params[key].join(',')); }
		});
		return url.toString();
	}

	/* ------------------------------------------------------------------
	 * AJAX navigation
	 * ------------------------------------------------------------------ */
	var controller = null;

	function swapRegions(html) {
		var parsed = new DOMParser().parseFromString(html, 'text/html');
		['results', 'filters'].forEach(function (name) {
			var current = $('[data-ps-region="' + name + '"]');
			var incoming = $('[data-ps-region="' + name + '"]', parsed);
			if (current && incoming) { current.innerHTML = incoming.innerHTML; }
		});
		var title = $('title', parsed);
		if (title) { doc.title = title.textContent; }
		var heading = $('.ps-shop-header', parsed);
		var currentHeading = $('.ps-shop-header');
		if (heading && currentHeading) { currentHeading.innerHTML = heading.innerHTML; }
	}

	function navigate(url, push) {
		if (!cfg.ajax || !window.fetch || !window.DOMParser) {
			window.location.href = url;
			return;
		}

		if (controller) { controller.abort(); }
		controller = window.AbortController ? new AbortController() : null;
		results.setAttribute('aria-busy', 'true');

		fetch(url, {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
			signal: controller ? controller.signal : undefined
		})
			.then(function (r) {
				if (!r.ok) { throw new Error(String(r.status)); }
				return r.text();
			})
			.then(function (html) {
				swapRegions(html);
				if (push !== false) { window.history.pushState({ psShop: true }, '', url); }
				init();
				results.setAttribute('aria-busy', 'false');
				live.textContent = I18N.resultsUpdated || '';

				var top = results.getBoundingClientRect().top + window.scrollY - 100;
				if (window.scrollY > top) {
					window.scrollTo({ top: top, behavior: 'smooth' });
				}
				if (window.jQuery) { window.jQuery(doc.body).trigger('ps_shop_updated'); }
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') { return; }
				window.location.href = url;
			});
	}

	window.addEventListener('popstate', function () {
		navigate(window.location.href, false);
	});

	/* Filter links (categories, chips, reset) & pagination. */
	doc.addEventListener('click', function (e) {
		var link = e.target.closest('[data-ps-filter-link], .woocommerce-pagination a');
		if (!link || !cfg.ajax || e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0) { return; }
		e.preventDefault();
		navigate(link.href);
		var drawer = link.closest('[data-ps-drawer].is-open');
		if (drawer && link.classList.contains('ps-filter__cat') && PS.closeDrawer) { PS.closeDrawer(drawer); }
	});

	/* WooCommerce sorting select: intercept before WooCommerce submits the form. */
	doc.addEventListener('change', function (e) {
		var select = e.target.closest('.woocommerce-ordering select');
		if (!select || !cfg.ajax) { return; }
		e.stopImmediatePropagation();
		e.stopPropagation();
		var form = select.form;
		var url = new URL(window.location.href);
		new FormData(form).forEach(function (value, name) {
			if (name === 'paged') { return; }
			url.searchParams.set(name, value);
		});
		url.pathname = url.pathname.replace(/\/page\/\d+\/?$/, '/');
		navigate(url.toString());
	}, true);

	function bindForm(form) {
		if (!form || form.getAttribute('data-ps-bound')) { return; }
		form.setAttribute('data-ps-bound', '1');
		form.setAttribute('data-ajax', cfg.ajax ? '1' : '0');
		initRange(form);

		var update = debounce(function () {
			var url = formToUrl(form);
			if (cfg.ajax) { navigate(url); } else { window.location.href = url; }
		}, 380);

		form.addEventListener('change', function (e) {
			if (e.target.matches('[data-ps-range-from], [data-ps-range-to]')) { return; }
			if (e.target.matches('input[type="search"]')) { return; }
			update();
		});

		var search = $('[data-ps-filter-search]', form);
		if (search) {
			search.addEventListener('input', debounce(function () {
				if (search.value.trim().length === 0 || search.value.trim().length >= 2) { update(); }
			}, 500));
		}

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var url = formToUrl(form);
			if (cfg.ajax) { navigate(url); } else { window.location.href = url; }
			var drawer = form.closest('[data-ps-drawer].is-open');
			if (drawer && PS.closeDrawer) { PS.closeDrawer(drawer); }
		});
	}

	function init() {
		bindForm($('[data-ps-filters]'));
		applyView(currentView());
		if (PS.enhanceQuantities) { PS.enhanceQuantities(results); }
		if (PS.wishlistSync) { PS.wishlistSync(); }
		if (PS.reveal) { PS.reveal(results); }
	}

	init();
}());
