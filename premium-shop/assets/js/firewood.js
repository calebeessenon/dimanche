/**
 * Premium Shop — firewood tools: needs calculator, unit converter,
 * postcode delivery check.
 */
(function () {
	'use strict';

	var cfg = window.premiumShopFirewood || {};
	var I18N = cfg.i18n || {};
	var PS = window.PremiumShop || {};
	var doc = document;

	function $(sel, ctx) { return (ctx || doc).querySelector(sel); }
	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
	function num(el) { return parseFloat(String(el.value).replace(',', '.')) || 0; }
	function fmt(n, d) {
		var s = n.toFixed(d === undefined ? 1 : d);
		return cfg.decimal === ',' ? s.replace('.', ',') : s;
	}
	function escapeHtml(str) {
		return String(str).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	/* 1 solid m³ (FM) ≈ 1.4 stacked m³ (RM) ≈ 2 loose m³ (SRM). */
	var PER_FM = { fm: 1, rm: 1.4, srm: 2 };
	var EFFICIENCY = 0.78;

	/* ------------------------------------------------------------------
	 * Calculator & converter
	 * ------------------------------------------------------------------ */
	$$('[data-ps-calc]').forEach(function (calc) {
		var species = $('[data-ps-calc-species]', calc);
		var kw = $('[data-ps-calc-kw]', calc);
		var hours = $('[data-ps-calc-hours]', calc);
		var days = $('[data-ps-calc-days]', calc);
		var outRm = $('[data-ps-calc-rm]', calc);
		var outEq = $('[data-ps-calc-eq]', calc);

		function needs() {
			var energy = num(kw) * num(hours) * num(days) / EFFICIENCY; // kWh of wood energy
			var perRm = num(species) || 1850;
			var rm = energy / perRm;
			outRm.textContent = rm > 0 ? fmt(rm, 1) : '—';
			outEq.textContent = rm > 0 ? (I18N.equivalent || '%1$s / %2$s').replace('%1$s', fmt(rm / PER_FM.rm * PER_FM.srm, 1)).replace('%2$s', fmt(rm / PER_FM.rm, 1)) : '';
		}
		[species, kw, hours, days].forEach(function (el) { el.addEventListener('input', needs); el.addEventListener('change', needs); });
		needs();

		var value = $('[data-ps-conv-value]', calc);
		var unit = $('[data-ps-conv-unit]', calc);
		function convert() {
			var fm = num(value) / PER_FM[unit.value];
			['rm', 'srm', 'fm'].forEach(function (u) {
				var el = $('[data-ps-conv-' + u + ']', calc);
				if (el) { el.textContent = fmt(fm * PER_FM[u], 2); }
			});
		}
		if (value && unit) {
			value.addEventListener('input', convert);
			unit.addEventListener('change', convert);
			convert();
		}
	});

	/* ------------------------------------------------------------------
	 * Postcode delivery check
	 * ------------------------------------------------------------------ */
	$$('[data-ps-delivery-form]').forEach(function (form) {
		var result = $('[data-ps-delivery-result]', form);
		var input = $('input[name="postcode"]', form);

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var postcode = input.value.trim();
			if (postcode.length < 3) {
				result.className = 'ps-delivery__result is-error';
				result.textContent = I18N.invalid || '';
				input.focus();
				return;
			}
			var country = form.elements.country ? form.elements.country.value : '';
			result.className = 'ps-delivery__result is-loading';
			result.textContent = I18N.checking || '…';

			fetch(PS.wcAjaxUrl('ps_delivery_check', { postcode: postcode, country: country, lang: (window.premiumShop || {}).lang || '' }), { credentials: 'same-origin' })
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res || !res.success) {
						result.className = 'ps-delivery__result is-error';
						result.textContent = (res && res.data && res.data.message) || I18N.invalid || '';
						return;
					}
					var d = res.data;
					var html = '<p class="ps-delivery__msg">' + escapeHtml(d.message) + '</p>';
					if (d.methods && d.methods.length) {
						html += '<ul class="ps-delivery__methods">';
						d.methods.forEach(function (m) {
							html += '<li><span>' + escapeHtml(m.title) + '</span>' + (m.cost ? '<strong>' + escapeHtml(m.cost) + '</strong>' : '') + '</li>';
						});
						html += '</ul>';
					}
					if (d.time) { html += '<p class="ps-delivery__time">' + escapeHtml(d.time) + '</p>'; }
					result.className = 'ps-delivery__result ' + (d.ok ? 'is-ok' : 'is-no');
					result.innerHTML = html;
					// Keep every check on the page in sync.
					$$('[data-ps-delivery-form] input[name="postcode"]').forEach(function (other) { other.value = postcode; });
					if (window.jQuery && d.ok) { window.jQuery(doc.body).trigger('wc_update_cart'); }
				})
				.catch(function () {
					result.className = 'ps-delivery__result is-error';
					result.textContent = ((window.premiumShop || {}).i18n || {}).error || '';
				});
		});
	});
}());
