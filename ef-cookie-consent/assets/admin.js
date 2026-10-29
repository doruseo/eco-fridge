/* EF Cookie Consent — admin: colour pickers, service rows, cookie scanner. */
(function ($) {
	'use strict';
	var cfg = window.efccAdmin || {};

	$(function () {
		if ($.fn.wpColorPicker) { $('.efcc-color').wpColorPicker(); }
	});

	/* —— services table —— */
	function addServiceRow(id, data) {
		var tpl = document.getElementById('efcc-service-template');
		var body = document.getElementById('efcc-services-body');
		if (!tpl || !body) { return; }
		var html = tpl.innerHTML.split('__ID__').join(id);
		var tmp = document.createElement('tbody');
		tmp.innerHTML = html;
		var row = tmp.firstElementChild;
		if (data) {
			['name', 'provider', 'privacy', 'purpose', 'patterns', 'cookies'].forEach(function (k) {
				var el = row.querySelector('[name$="[' + k + ']"]');
				if (el && data[k] !== undefined) { el.value = data[k]; }
			});
			var cat = row.querySelector('[name$="[category]"]');
			if (cat && data.category) { cat.value = data.category; }
		}
		body.appendChild(row);
		var first = row.querySelector('input[type="text"]');
		if (first) { first.focus(); }
	}

	document.addEventListener('click', function (e) {
		if (e.target.id === 'efcc-add-preset') {
			var sel = document.getElementById('efcc-preset');
			var opt = sel && sel.options[sel.selectedIndex];
			if (!opt || !opt.value) { return; }
			addServiceRow(opt.value, JSON.parse(opt.getAttribute('data-preset')));
			opt.remove();
			sel.value = '';
		}
		if (e.target.id === 'efcc-add-custom') {
			var name = window.prompt('Service ID (lowercase, e.g. my-chat-widget):');
			if (!name) { return; }
			name = name.toLowerCase().replace(/[^a-z0-9_-]/g, '-');
			addServiceRow(name, null);
		}
		if (e.target.classList.contains('efcc-remove')) {
			var tr = e.target.closest('tr');
			if (tr && window.confirm('Remove this service?')) { tr.remove(); }
		}
	});

	/* —— cookie scanner —— */
	var scanBtn = document.getElementById('efcc-scan');
	if (scanBtn) {
		scanBtn.addEventListener('click', function () {
			var urls = JSON.parse(scanBtn.getAttribute('data-urls') || '[]');
			var box = document.getElementById('efcc-scan-frames');
			var status = document.getElementById('efcc-scan-status');
			var i = 0, found = 0, timer = null, frame = null;
			scanBtn.disabled = true;

			function send(names, url, done) {
				var body = new URLSearchParams({ action: 'efcc_scan_save', nonce: cfg.nonce, url: url, done: done ? '1' : '' });
				names.forEach(function (n) { body.append('names[]', n); });
				return fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body }).then(function (r) { return r.json(); });
			}

			function next() {
				if (frame) { frame.remove(); frame = null; }
				if (i >= urls.length) {
					send([], '', true).then(function () {
						status.textContent = 'Done: ' + urls.length + ' pages scanned, ' + found + ' new cookies. Reloading…';
						setTimeout(function () { location.reload(); }, 1500);
					});
					return;
				}
				var url = urls[i++];
				status.textContent = 'Scanning ' + i + ' / ' + urls.length + ': ' + url.replace(location.origin, '');
				frame = document.createElement('iframe');
				frame.src = url + (url.indexOf('?') < 0 ? '?' : '&') + 'efcc_scan=1';
				frame.setAttribute('title', 'scan');
				box.appendChild(frame);
				timer = setTimeout(next, 25000); // A page that never reports.
			}

			window.addEventListener('message', function (e) {
				if (e.origin !== location.origin || !e.data || !e.data.efccScan) { return; }
				clearTimeout(timer);
				send((e.data.cookies || []).concat(e.data.storage || []), e.data.url, false).then(function (r) {
					if (r && r.success) { found += r.data.new; }
					next();
				});
			});
			next();
		});
	}
})(jQuery);
