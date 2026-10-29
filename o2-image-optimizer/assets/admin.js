/* O2 Image Optimizer — dashboard controls, status polling, media-list action. */
(function () {
	'use strict';
	var cfg = window.o2ioAdmin;
	if (!cfg) { return; }

	function post(data) {
		var body = new URLSearchParams(data);
		body.append('nonce', cfg.nonce);
		return fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); });
	}

	function render(d) {
		if (!d || !d.success) { return; }
		d = d.data;
		var bar = document.getElementById('o2io-bar');
		if (bar) { bar.style.width = d.pct + '%'; }
		var txt = document.getElementById('o2io-queue-text');
		if (txt) { txt.textContent = d.text; }
		var stats = document.querySelectorAll('#o2io-stats .o2io-stat span');
		if (stats.length === 4) {
			stats[0].textContent = d.stats.images.toLocaleString();
			stats[1].textContent = d.stats.done.toLocaleString();
			stats[2].textContent = d.stats.pending.toLocaleString();
			stats[3].textContent = (d.stats.partial + d.stats.error).toLocaleString();
		}
		var log = document.getElementById('o2io-log');
		if (log) {
			log.innerHTML = '';
			d.log.forEach(function (l) {
				var li = document.createElement('li');
				var t = document.createElement('time');
				t.textContent = l[0];
				li.appendChild(t);
				li.appendChild(document.createTextNode(' ' + l[1]));
				log.appendChild(li);
			});
		}
		setStatus(d.queue.status, d.pct);
	}

	/* —— "running" state: big panel, tab title, warning before leaving —— */
	var title = document.title;
	var status = '';
	function setStatus(s, pct) {
		var box = document.getElementById('o2io-bulk');
		if (box) { box.dataset.status = s; }
		var p = document.getElementById('o2io-running-pct');
		if (p) { p.textContent = pct + '%'; }
		document.title = s === 'running' ? '⏳ ' + pct + '% – optimising images… ' + title : title;
		var was = status;
		status = s;
		if (s === 'running' && was !== 'running') { drive(); }
	}
	window.addEventListener('beforeunload', function (e) {
		if (status !== 'running') { return; }
		e.preventDefault();
		e.returnValue = 'The image optimisation is still running. Keep this window open until it finishes.';
		return e.returnValue;
	});

	/* While running, this page asks the server for the next batch one after
	   another (each request waits for the previous one), with the configured
	   pause between batches. WP-Cron remains the fallback when it is closed. */
	var driving = false;
	function drive() {
		if (driving) { return; }
		driving = true;
		(function loop() {
			if (status !== 'running') { driving = false; return; }
			post({ action: 'o2io_tick' }).then(render, function () {}).finally(function () {
				if (status !== 'running') { driving = false; return; }
				setTimeout(loop, Math.max(2, cfg.pause || 0) * 1000);
			});
		})();
	}

	// Dashboard buttons.
	document.addEventListener('click', function (e) {
		var b = e.target.closest('[data-o2io]');
		if (!b) { return; }
		if (b.dataset.confirm && !window.confirm(b.dataset.confirm)) { return; }
		b.disabled = true;
		post({ action: 'o2io_control', do: b.dataset.o2io }).then(render).finally(function () { b.disabled = false; });
	});

	// Poll while the dashboard is open (the batches themselves run in drive()).
	var bulk = document.getElementById('o2io-bulk');
	if (bulk) {
		setStatus(bulk.dataset.status, parseInt(document.getElementById('o2io-running-pct').textContent, 10) || 0);
		setInterval(function () {
			if (status !== 'running') { post({ action: 'o2io_status' }).then(render); }
		}, 6000);
	}

	// Media list: convert one image.
	document.addEventListener('click', function (e) {
		var a = e.target.closest('.o2io-one');
		if (!a) { return; }
		e.preventDefault();
		var id = a.dataset.id;
		var cell = document.querySelector('.o2io-cell[data-id="' + id + '"]');
		a.textContent = 'Converting…';
		post({ action: 'o2io_one', id: id }).then(function (d) {
			a.textContent = 'Convert AVIF/WebP';
			if (cell && d && d.success) { cell.innerHTML = d.data.html; }
		});
	});
})();
