/*
 * EF Cookie Consent — banner, preferences, Consent Mode updates, unblocking,
 * revocation, consent log and the admin cookie scanner frame.
 * Settings come from window.efccConfig (printed in <head>); the stored choice
 * was already read there into window.efccStored.
 */
(function () {
	'use strict';
	var c = window.efccConfig;
	if (!c) { return; }
	var d = document;
	var banner = d.getElementById('efcc-banner');
	var modal = d.getElementById('efcc-modal');
	var floatBtn = d.getElementById('efcc-float');
	var state = window.efccStored || null;
	var lastFocus = null;
	var optional = c.categories.filter(function (k) { return k !== 'necessary'; });

	window.dataLayer = window.dataLayer || [];
	function gtag() { window.dataLayer.push(arguments); }

	/* —— state —— */

	function uuid() {
		if (window.crypto && crypto.randomUUID) { return crypto.randomUUID(); }
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (ch) {
			var r = Math.random() * 16 | 0;
			return (ch === 'x' ? r : (r & 3 | 8)).toString(16);
		});
	}

	function serviceCategory(id) { return (c.services[id] || {}).category || 'marketing'; }

	function granted(cat) {
		if (cat === 'necessary') { return true; }
		return !!(state && state.cats && state.cats[cat]);
	}

	// A service runs when its category is allowed and it wasn't switched off on its own.
	function serviceAllowed(id, cat) {
		cat = cat || serviceCategory(id);
		if (cat === 'necessary') { return true; }
		if (!granted(cat)) { return false; }
		return !(state && state.svc && state.svc[id] === 0);
	}

	function consentModeUpdate() {
		var u = {};
		Object.keys(c.gcmMap).forEach(function (type) {
			var cat = c.gcmMap[type];
			var ok = granted(cat);
			// Switching off Google Analytics / Google Ads on its own also denies its signals.
			if (ok && type === 'analytics_storage' && c.services['google-analytics'] && !serviceAllowed('google-analytics')) { ok = false; }
			if (ok && type.indexOf('ad_') === 0 && c.services['google-ads'] && !serviceAllowed('google-ads')) { ok = false; }
			u[type] = ok ? 'granted' : 'denied';
		});
		return u;
	}

	function readCookieNames() {
		return d.cookie.split(';').map(function (p) { return p.split('=')[0].trim(); }).filter(Boolean);
	}

	function wildcard(pattern, name) {
		var re = new RegExp('^' + pattern.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$');
		return re.test(name);
	}

	function deleteCookie(name) {
		var host = location.hostname;
		var parts = host.split('.');
		var domains = ['', host, '.' + host];
		if (parts.length > 2) { domains.push('.' + parts.slice(-3).join('.'), '.' + parts.slice(-2).join('.')); }
		domains.forEach(function (dom) {
			d.cookie = name + '=; Max-Age=0; path=/' + (dom ? '; domain=' + dom : '');
		});
	}

	/* —— applying a choice —— */

	function loadGtm() {
		if (!c.gtm || window.efccGtmLoaded) { return; }
		window.efccGtmLoaded = true;
		window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
		var s = d.createElement('script');
		s.async = true;
		s.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(c.gtm);
		d.head.appendChild(s);
	}

	function unblock() {
		d.querySelectorAll('script[type="text/plain"][data-efcc-category]').forEach(function (old) {
			if (!serviceAllowed(old.getAttribute('data-efcc-service'), old.getAttribute('data-efcc-category'))) { return; }
			var s = d.createElement('script');
			Array.prototype.forEach.call(old.attributes, function (a) {
				if (a.name === 'type' || a.name === 'data-efcc-type') { return; }
				s.setAttribute(a.name, a.value);
			});
			var t = old.getAttribute('data-efcc-type');
			if (t && t !== 'text/javascript') { s.type = t; }
			if (!old.src) { s.text = old.text; }
			old.parentNode.replaceChild(s, old);
		});
		d.querySelectorAll('iframe[data-efcc-src]').forEach(function (f) {
			if (!serviceAllowed(f.getAttribute('data-efcc-service'), f.getAttribute('data-efcc-category'))) { return; }
			var ph = f.previousElementSibling;
			if (ph && ph.classList.contains('efcc-placeholder')) { ph.remove(); }
			f.src = f.getAttribute('data-efcc-src');
			f.removeAttribute('data-efcc-src');
			f.hidden = false;
		});
	}

	function apply(prev) {
		if (c.gcm) { gtag('consent', 'update', consentModeUpdate()); }
		var allowed = Object.keys(c.services).filter(function (id) { return serviceAllowed(id); });
		var flags = { method: state.method, services: allowed };
		optional.forEach(function (k) { flags[k] = granted(k); });
		window.dataLayer.push({ event: 'efcc_consent_update', efcc_consent: flags });
		optional.forEach(function (k) {
			if (granted(k)) { window.dataLayer.push({ event: 'efcc_' + k + '_granted' }); }
		});
		if (c.gtmMode === 'basic' && (granted('statistics') || granted('marketing'))) { loadGtm(); }
		if (c.wooAttr && window.wc_order_attribution && window.wc_order_attribution.setOrderTracking) {
			window.wc_order_attribution.setOrderTracking(serviceAllowed('woocommerce-attribution', 'statistics'));
		}
		unblock();
		d.dispatchEvent(new CustomEvent('efcc:consent', { detail: state }));

		// Withdrawn consent: remove the cookies of what is no longer allowed.
		var revoked = false;
		if (prev) {
			Object.keys(c.services).forEach(function (id) {
				var was = prev.cats && prev.cats[serviceCategory(id)] && !(prev.svc && prev.svc[id] === 0);
				if (was && !serviceAllowed(id)) {
					revoked = true;
					(c.services[id].cookies || []).forEach(function (pattern) {
						readCookieNames().forEach(function (n) { if (wildcard(pattern, n)) { deleteCookie(n); } });
					});
				}
			});
		}
		return revoked;
	}

	function log() {
		if (!c.log || c.scan) { return; }
		var body = JSON.stringify({ id: state.id, method: state.method, cats: state.cats, svc: state.svc, v: c.version, url: location.href.split('#')[0] });
		try {
			if (navigator.sendBeacon) {
				navigator.sendBeacon(c.logUrl, new Blob([body], { type: 'application/json' }));
			} else {
				fetch(c.logUrl, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true });
			}
		} catch (e) { /* the choice still applies */ }
	}

	function save(method, cats, svc) {
		var prev = state;
		state = { v: c.version, id: (prev && prev.id) || uuid(), ts: Date.now(), method: method, cats: cats, svc: svc || {} };
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		d.cookie = c.cookie + '=' + encodeURIComponent(JSON.stringify(state)) + '; Max-Age=' + (c.days * 86400) + '; path=/; SameSite=Lax' + secure;
		var revoked = apply(prev);
		log();
		hideBanner();
		closeModal();
		showFloat();
		if (revoked && c.reload) { setTimeout(function () { location.reload(); }, 300); }
	}

	function allCats(v) {
		var o = {};
		optional.forEach(function (k) { o[k] = v ? 1 : 0; });
		return o;
	}

	function allServices(cats) {
		var o = {};
		Object.keys(c.services).forEach(function (id) {
			var cat = serviceCategory(id);
			if (cat !== 'necessary') { o[id] = cats[cat] ? 1 : 0; }
		});
		return o;
	}

	function acceptAll() { var cats = allCats(1); save('accept', cats, allServices(cats)); }
	function rejectAll() { var cats = allCats(0); save('reject', cats, allServices(cats)); }

	function saveFromModal() {
		var cats = allCats(0);
		var svc = {};
		modal.querySelectorAll('[data-efcc-cat]').forEach(function (i) { cats[i.getAttribute('data-efcc-cat')] = i.checked ? 1 : 0; });
		modal.querySelectorAll('[data-efcc-svc]').forEach(function (i) { svc[i.getAttribute('data-efcc-svc')] = i.checked ? 1 : 0; });
		save('custom', cats, svc);
	}

	/* —— UI —— */

	function showBanner() { if (banner) { banner.hidden = false; d.documentElement.classList.add('efcc-banner-open'); } if (floatBtn) { floatBtn.hidden = true; } }
	function hideBanner() { if (banner) { banner.hidden = true; d.documentElement.classList.remove('efcc-banner-open'); } }
	function showFloat() { if (floatBtn) { floatBtn.hidden = false; } }

	function syncModal() {
		modal.querySelectorAll('[data-efcc-cat]').forEach(function (i) { i.checked = granted(i.getAttribute('data-efcc-cat')); });
		modal.querySelectorAll('[data-efcc-svc]').forEach(function (i) { i.checked = serviceAllowed(i.getAttribute('data-efcc-svc'), i.getAttribute('data-efcc-svc-cat')); });
		var idBox = modal.querySelector('.efcc-consent-id');
		if (idBox) {
			idBox.hidden = !state;
			if (state) { idBox.querySelector('[data-efcc-id]').textContent = state.id; }
		}
	}

	function focusables() {
		return Array.prototype.filter.call(modal.querySelectorAll('button, [href], input, summary, [tabindex]:not([tabindex="-1"])'), function (el) {
			return !el.disabled && el.offsetParent !== null;
		});
	}

	function openModal() {
		if (!modal) { return; }
		lastFocus = d.activeElement;
		syncModal();
		modal.hidden = false;
		d.documentElement.classList.add('efcc-modal-open');
		modal.querySelector('.efcc-modal__dialog').focus();
	}

	function closeModal() {
		if (!modal || modal.hidden) { return; }
		modal.hidden = true;
		d.documentElement.classList.remove('efcc-modal-open');
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	d.addEventListener('click', function (e) {
		var t = e.target.closest('[data-efcc], .efcc-open, a[href="#efcc-settings"], [data-efcc-allow], .efcc-cat__toggle');
		if (!t) { return; }
		if (t.classList.contains('efcc-cat__toggle')) {
			var open = t.getAttribute('aria-expanded') !== 'true';
			t.setAttribute('aria-expanded', open ? 'true' : 'false');
			d.getElementById(t.getAttribute('aria-controls')).hidden = !open;
			return;
		}
		if (t.hasAttribute('data-efcc-allow')) {
			// "Allow and show" on a placeholder: allow that category (and service) only.
			var cats = state ? Object.assign({}, state.cats) : allCats(0);
			var svc = state ? Object.assign({}, state.svc) : allServices(cats);
			cats[t.getAttribute('data-efcc-allow')] = 1;
			svc[t.getAttribute('data-efcc-allow-svc')] = 1;
			save('custom', cats, svc);
			return;
		}
		if (t.matches('.efcc-open, a[href="#efcc-settings"]')) { e.preventDefault(); openModal(); return; }
		switch (t.getAttribute('data-efcc')) {
			case 'accept': acceptAll(); break;
			case 'reject': rejectAll(); break;
			case 'manage': openModal(); break;
			case 'save': saveFromModal(); break;
			case 'close': closeModal(); break;
		}
	});

	// A category switch sets its services; a service switch updates its category.
	d.addEventListener('change', function (e) {
		var i = e.target;
		if (!modal || !modal.contains(i)) { return; }
		if (i.hasAttribute('data-efcc-cat')) {
			modal.querySelectorAll('[data-efcc-svc-cat="' + i.getAttribute('data-efcc-cat') + '"]').forEach(function (s) { s.checked = i.checked; });
		} else if (i.hasAttribute('data-efcc-svc')) {
			var cat = i.getAttribute('data-efcc-svc-cat');
			var any = Array.prototype.some.call(modal.querySelectorAll('[data-efcc-svc-cat="' + cat + '"]'), function (s) { return s.checked; });
			var box = modal.querySelector('[data-efcc-cat="' + cat + '"]');
			if (box) { box.checked = any; }
		}
	});

	d.addEventListener('keydown', function (e) {
		if (!modal || modal.hidden) { return; }
		if (e.key === 'Escape') { closeModal(); return; }
		if (e.key !== 'Tab') { return; }
		var f = focusables();
		if (!f.length) { return; }
		var first = f[0], last = f[f.length - 1];
		if (e.shiftKey && d.activeElement === first) { e.preventDefault(); last.focus(); }
		else if (!e.shiftKey && d.activeElement === last) { e.preventDefault(); first.focus(); }
	});

	/* —— start —— */

	if (c.scan) {
		// Admin scanner frame: everything allowed (set in <head>), nothing saved;
		// report what the page stored after it had time to load its tags.
		state = { v: c.version, id: 'scan', method: 'accept', cats: allCats(1), svc: {} };
		apply(null);
		setTimeout(function () {
			var keys = function (st) { try { return Object.keys(st); } catch (e) { return []; } };
			window.parent.postMessage({ efccScan: true, url: location.href, cookies: readCookieNames(), storage: keys(localStorage).concat(keys(sessionStorage)) }, location.origin);
		}, 7000);
		return;
	}

	if (state) {
		apply(null);
		showFloat();
	} else if (c.gpc && navigator.globalPrivacyControl) {
		// Global Privacy Control: the browser already says "do not sell / share".
		var none = allCats(0);
		save('gpc', none, allServices(none));
	} else if (!c.hide) {
		showBanner();
	} else {
		showFloat();
	}
	if (c.preview) { showBanner(); }
})();
