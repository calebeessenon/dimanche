/**
 * Premium Shop — "Gaming & tech" style: motion layer.
 * Particles, typing, counters, 3D tilt, parallax stage, cursor glow,
 * scroll progress, magnetic buttons and a deal countdown.
 * Everything is skipped when the visitor prefers reduced motion.
 */
(function () {
	'use strict';

	var doc = document;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
	var L10N = window.PremiumShopGaming || { endsIn: 'Ends in', units: ['days', 'hrs', 'min', 'sec'] };

	function $$(sel, ctx) { return Array.prototype.slice.call((ctx || doc).querySelectorAll(sel)); }
	function onVisible(el, cb, opts) {
		if (!('IntersectionObserver' in window)) { cb(true); return; }
		new IntersectionObserver(function (entries) {
			entries.forEach(function (e) { cb(e.isIntersecting, e); });
		}, opts || { threshold: 0.15 }).observe(el);
	}

	/* -------------------------------------------------------------
	 * Hero particles: drifting points linked by lines, reacting to the mouse.
	 * ----------------------------------------------------------- */
	$$('[data-ps-particles]').forEach(function (canvas) {
		if (reduce || !canvas.getContext) { return; }
		var ctx = canvas.getContext('2d');
		var dpr = Math.min(window.devicePixelRatio || 1, 2);
		var points = [];
		var mouse = { x: -9999, y: -9999 };
		var running = false;
		var w = 0;
		var h = 0;
		var colors = ['124,92,255', '0,212,255', '255,79,216'];

		function size() {
			var r = canvas.getBoundingClientRect();
			w = r.width; h = r.height;
			canvas.width = w * dpr; canvas.height = h * dpr;
			ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
			var count = Math.round(Math.min(90, (w * h) / 16000));
			points = [];
			for (var i = 0; i < count; i++) {
				points.push({
					x: Math.random() * w, y: Math.random() * h,
					vx: (Math.random() - 0.5) * 0.35, vy: (Math.random() - 0.5) * 0.35,
					r: Math.random() * 1.8 + 0.6, c: colors[i % colors.length]
				});
			}
		}
		function frame() {
			if (!running) { return; }
			ctx.clearRect(0, 0, w, h);
			for (var i = 0; i < points.length; i++) {
				var p = points[i];
				p.x += p.vx; p.y += p.vy;
				if (p.x < 0 || p.x > w) { p.vx *= -1; }
				if (p.y < 0 || p.y > h) { p.vy *= -1; }
				var dx = p.x - mouse.x;
				var dy = p.y - mouse.y;
				var dist = Math.sqrt(dx * dx + dy * dy);
				if (dist < 120) { p.x += dx / dist * 1.2; p.y += dy / dist * 1.2; }
				ctx.beginPath();
				ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
				ctx.fillStyle = 'rgba(' + p.c + ',.9)';
				ctx.fill();
				for (var j = i + 1; j < points.length; j++) {
					var q = points[j];
					var d = Math.hypot(p.x - q.x, p.y - q.y);
					if (d < 120) {
						ctx.strokeStyle = 'rgba(' + p.c + ',' + (0.16 * (1 - d / 120)).toFixed(3) + ')';
						ctx.lineWidth = 1;
						ctx.beginPath(); ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y); ctx.stroke();
					}
				}
			}
			requestAnimationFrame(frame);
		}
		size();
		window.addEventListener('resize', function () { size(); });
		canvas.parentNode.addEventListener('pointermove', function (e) {
			var r = canvas.getBoundingClientRect();
			mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top;
		});
		canvas.parentNode.addEventListener('pointerleave', function () { mouse.x = mouse.y = -9999; });
		onVisible(canvas, function (vis) {
			if (vis && !running && !doc.hidden) { running = true; requestAnimationFrame(frame); }
			if (!vis) { running = false; }
		}, { threshold: 0 });
		doc.addEventListener('visibilitychange', function () {
			if (doc.hidden) { running = false; } else if (!running) { running = true; requestAnimationFrame(frame); }
		});
	});

	/* -------------------------------------------------------------
	 * Typing: rotate platform names.
	 * ----------------------------------------------------------- */
	$$('[data-ps-typed]').forEach(function (el) {
		var words;
		try { words = JSON.parse(el.getAttribute('data-ps-typed')); } catch (e) { return; }
		if (!words || words.length < 2 || reduce) { return; }
		var wi = 0;
		var ci = words[0].length;
		var deleting = true;
		function tick() {
			var word = words[wi];
			if (deleting) {
				ci--;
				el.textContent = word.slice(0, ci);
				if (ci <= 0) { deleting = false; wi = (wi + 1) % words.length; }
				setTimeout(tick, 45);
			} else {
				word = words[wi];
				ci++;
				el.textContent = word.slice(0, ci);
				if (ci >= word.length) { deleting = true; setTimeout(tick, 1900); return; }
				setTimeout(tick, 85);
			}
		}
		setTimeout(tick, 2200);
	});

	/* -------------------------------------------------------------
	 * Counters.
	 * ----------------------------------------------------------- */
	$$('[data-ps-count]').forEach(function (el) {
		var target = parseInt(el.getAttribute('data-ps-count'), 10) || 0;
		if (reduce) { return; }
		el.textContent = '0';
		var done = false;
		onVisible(el, function (vis) {
			if (!vis || done) { return; }
			done = true;
			var start = performance.now();
			var dur = 1400;
			(function step(now) {
				var t = Math.min(1, (now - start) / dur);
				var eased = 1 - Math.pow(1 - t, 3);
				el.textContent = String(Math.round(target * eased));
				if (t < 1) { requestAnimationFrame(step); }
			})(start);
		});
	});

	/* -------------------------------------------------------------
	 * Hero stage: parallax layers following the pointer.
	 * ----------------------------------------------------------- */
	$$('[data-ps-stage]').forEach(function (stage) {
		if (reduce || !fine) { return; }
		var hero = stage.closest('[data-ps-ghero]') || stage;
		var items = $$('[data-depth]', stage);
		var raf = 0;
		hero.addEventListener('pointermove', function (e) {
			var r = hero.getBoundingClientRect();
			var x = (e.clientX - r.left) / r.width - 0.5;
			var y = (e.clientY - r.top) / r.height - 0.5;
			cancelAnimationFrame(raf);
			raf = requestAnimationFrame(function () {
				stage.style.transform = 'rotateY(' + (x * 10) + 'deg) rotateX(' + (-y * 8) + 'deg)';
				items.forEach(function (it) {
					var d = parseFloat(it.getAttribute('data-depth')) || 1;
					it.style.transform = 'translate3d(' + (x * 40 * d) + 'px,' + (y * 30 * d) + 'px,' + (d * 40) + 'px)';
				});
			});
		});
		hero.addEventListener('pointerleave', function () {
			stage.style.transform = '';
			items.forEach(function (it) { it.style.transform = ''; });
		});
	});

	/* -------------------------------------------------------------
	 * 3D tilt on product cards and category tiles.
	 * ----------------------------------------------------------- */
	if (!reduce && fine) {
		doc.addEventListener('pointermove', function (e) {
			var card = e.target.closest && e.target.closest('.ps-preset-gaming .ps-card, .ps-preset-gaming .ps-cat-tile');
			if (!card) { return; }
			var r = card.getBoundingClientRect();
			var x = (e.clientX - r.left) / r.width - 0.5;
			var y = (e.clientY - r.top) / r.height - 0.5;
			card.style.transform = 'perspective(800px) rotateY(' + (x * 8) + 'deg) rotateX(' + (-y * 8) + 'deg) translateY(-4px)';
			card.style.setProperty('--mx', (x + 0.5) * 100 + '%');
			card.style.setProperty('--my', (y + 0.5) * 100 + '%');
		}, { passive: true });
		doc.addEventListener('pointerout', function (e) {
			var card = e.target.closest && e.target.closest('.ps-card, .ps-cat-tile');
			if (card && !card.contains(e.relatedTarget)) { card.style.transform = ''; }
		}, { passive: true });
	}

	/* -------------------------------------------------------------
	 * Magnetic buttons.
	 * ----------------------------------------------------------- */
	if (!reduce && fine) {
		$$('[data-ps-magnetic]').forEach(function (btn) {
			btn.addEventListener('pointermove', function (e) {
				var r = btn.getBoundingClientRect();
				var x = e.clientX - r.left - r.width / 2;
				var y = e.clientY - r.top - r.height / 2;
				btn.style.transform = 'translate(' + (x * 0.18) + 'px,' + (y * 0.25) + 'px)';
			});
			btn.addEventListener('pointerleave', function () { btn.style.transform = ''; });
		});
	}

	/* -------------------------------------------------------------
	 * Scroll progress bar and cursor glow.
	 * ----------------------------------------------------------- */
	var bar = doc.createElement('div');
	bar.className = 'g-progress';
	bar.setAttribute('aria-hidden', 'true');
	doc.body.appendChild(bar);
	var ticking = false;
	function progress() {
		var max = doc.documentElement.scrollHeight - window.innerHeight;
		bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, window.scrollY / max) : 0) + ')';
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { ticking = true; requestAnimationFrame(progress); }
	}, { passive: true });
	progress();

	if (!reduce && fine) {
		var glow = doc.createElement('div');
		glow.className = 'g-cursor';
		glow.setAttribute('aria-hidden', 'true');
		doc.body.appendChild(glow);
		var gx = 0;
		var gy = 0;
		var tx = 0;
		var ty = 0;
		var moving = false;
		doc.addEventListener('pointermove', function (e) {
			tx = e.clientX; ty = e.clientY;
			glow.classList.add('is-on');
			if (!moving) { moving = true; requestAnimationFrame(follow); }
		}, { passive: true });
		doc.addEventListener('pointerleave', function () { glow.classList.remove('is-on'); });
		function follow() {
			gx += (tx - gx) * 0.14; gy += (ty - gy) * 0.14;
			glow.style.transform = 'translate3d(' + gx + 'px,' + gy + 'px,0)';
			if (Math.abs(tx - gx) > 0.5 || Math.abs(ty - gy) > 0.5) { requestAnimationFrame(follow); } else { moving = false; }
		}
	}

	/* -------------------------------------------------------------
	 * Deal countdown in the campaign banner (until Sunday midnight).
	 * ----------------------------------------------------------- */
	$$('.ps-preset-gaming .ps-campaign__content').forEach(function (box) {
		var anchor = box.querySelector('.ps-btn, a');
		var wrap = doc.createElement('div');
		wrap.className = 'g-countdown';
		wrap.setAttribute('role', 'timer');
		var html = '<span class="g-countdown__label">' + L10N.endsIn + '</span>';
		for (var i = 0; i < 4; i++) {
			html += '<span class="g-countdown__cell"><b>00</b><small>' + L10N.units[i] + '</small></span>';
		}
		wrap.innerHTML = html;
		if (anchor) { anchor.parentNode.insertBefore(wrap, anchor); } else { box.appendChild(wrap); }
		var cells = $$('.g-countdown__cell', wrap);
		function end() {
			var d = new Date();
			var e = new Date(d.getFullYear(), d.getMonth(), d.getDate() + ((7 - d.getDay()) % 7), 23, 59, 59);
			return e;
		}
		var target = end();
		var last = [];
		function update() {
			var s = Math.max(0, Math.floor((target - new Date()) / 1000));
			if (s === 0) { target = new Date(Date.now() + 7 * 864e5); }
			var parts = [Math.floor(s / 86400), Math.floor(s % 86400 / 3600), Math.floor(s % 3600 / 60), s % 60];
			parts.forEach(function (v, n) {
				var txt = (v < 10 ? '0' : '') + v;
				if (last[n] !== txt) {
					var b = cells[n].firstChild;
					b.textContent = txt;
					if (!reduce && last[n] !== undefined) {
						cells[n].classList.remove('is-tick');
						void cells[n].offsetWidth;
						cells[n].classList.add('is-tick');
					}
					last[n] = txt;
				}
			});
		}
		update();
		setInterval(update, 1000);
	});
})();
