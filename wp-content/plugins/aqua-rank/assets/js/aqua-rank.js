/* Aqua Rank front-end (no dependencies). */
(function () {
	'use strict';

	var yen = function (n) {
		return Math.round(n).toLocaleString('ja-JP') + '円';
	};
	var esc = function (s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	};

	/* 比較表：絞り込み + 並べ替え */
	document.querySelectorAll('[data-compare]').forEach(function (wrap) {
		var tbody = wrap.querySelector('tbody');
		wrap.querySelectorAll('[data-filter]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var f = btn.getAttribute('data-filter');
				wrap.querySelectorAll('[data-filter]').forEach(function (b) {
					b.classList.toggle('is-active', b === btn);
					b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
				});
				tbody.querySelectorAll('tr').forEach(function (tr) {
					var terms = (tr.getAttribute('data-terms') || '').split(' ');
					tr.hidden = !!f && terms.indexOf(f) === -1;
				});
			});
		});
		wrap.querySelectorAll('th[data-sort]').forEach(function (th) {
			th.setAttribute('tabindex', '0');
			var sort = function () {
				var idx = Array.prototype.indexOf.call(th.parentNode.children, th);
				var asc = th.getAttribute('aria-sort') !== 'ascending';
				// 総合評価は高い順が自然なので初回は降順
				if (!th.hasAttribute('aria-sort') && idx === 1) { asc = false; }
				wrap.querySelectorAll('th[data-sort]').forEach(function (o) { o.removeAttribute('aria-sort'); });
				th.setAttribute('aria-sort', asc ? 'ascending' : 'descending');
				var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
				rows.sort(function (a, b) {
					var va = parseFloat(a.children[idx].getAttribute('data-value'));
					var vb = parseFloat(b.children[idx].getAttribute('data-value'));
					if (isNaN(va)) { va = asc ? Infinity : -Infinity; }
					if (isNaN(vb)) { vb = asc ? Infinity : -Infinity; }
					return asc ? va - vb : vb - va;
				});
				rows.forEach(function (r) { tbody.appendChild(r); });
			};
			th.addEventListener('click', sort);
			th.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); sort(); } });
		});
	});

	/* タイプ診断 */
	document.querySelectorAll('[data-diagnosis]').forEach(function (box) {
		var data = [];
		try { data = JSON.parse(box.querySelector('[data-diagnosis-data]').textContent); } catch (e) {}
		var out = box.querySelector('.ar-diagnosis__result');
		box.querySelector('[data-diagnosis-run]').addEventListener('click', function () {
			var v = function (n) { var el = box.querySelector('input[name="' + n + '"]:checked'); return el ? el.value : ''; };
			var q1 = v('dq1'), q2 = v('dq2'), q3 = v('dq3');
			if (!q1 || !q2 || !q3) {
				out.hidden = false;
				out.innerHTML = '<p>3つの質問すべてに回答してください。</p>';
				return;
			}
			var josui = (q1 === 'josui') + (q2 === 'josui');
			var type = josui >= 1 && q2 === 'josui' ? 'josui' : (josui === 2 ? 'josui' : 'tennen');
			var typeLabel = type === 'josui' ? '浄水型（水道水補充）' : '宅配水（天然水・RO水）';
			var placeLabel = q3 === 'takujo' ? '卓上型' : '床置き型';
			var match = function (s, strict) {
				var t = s.type.split(' '), p = s.placement.split(' ');
				var typeOk = type === 'josui' ? t.indexOf('josui') > -1 || t.indexOf('chokketsu') > -1 : t.indexOf('tennen') > -1 || t.indexOf('ro') > -1;
				return typeOk && (!strict || p.indexOf(q3) > -1);
			};
			var list = data.filter(function (s) { return match(s, true); });
			if (list.length < 1) { list = data.filter(function (s) { return match(s, false); }); }
			list = list.slice(0, 3);
			var html = '<p>あなたにおすすめは <strong>' + typeLabel + '</strong> × <strong>' + placeLabel + '</strong> のウォーターサーバーです。</p>';
			if (list.length) {
				html += '<ol>' + list.map(function (s) {
					return '<li><a href="' + esc(s.url) + '"><strong>' + esc(s.title) + '</strong></a>（月額目安 ' + esc(s.cost) + (s.score ? '・評価 ' + esc(s.score) : '') + '）' +
						(s.cta ? '<br><a class="ar-btn ar-btn--cta ar-btn--sm" href="' + esc(s.cta) + '" target="_blank" rel="sponsored noopener">公式サイトを見る</a>' : '') + '</li>';
				}).join('') + '</ol>';
			}
			out.innerHTML = html;
			out.hidden = false;
			out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
		});
	});

	/* 年間コストシミュレーター */
	document.querySelectorAll('[data-costsim]').forEach(function (box) {
		var calc = function () {
			var g = function (k) { return parseFloat(box.querySelector('[data-k="' + k + '"]').value) || 0; };
			var litersMonth = g('liters') * 30;
			var cost = {
				pet: litersMonth / 2 * g('pet'),
				bottle: litersMonth / 12 * g('bottle') + g('rental') + g('elec'),
				josui: g('josui') + g('elec') + litersMonth * 0.2
			};
			var best = Object.keys(cost).reduce(function (a, b) { return cost[a] <= cost[b] ? a : b; });
			Object.keys(cost).forEach(function (k) {
				var row = box.querySelector('[data-row="' + k + '"]');
				row.children[1].textContent = yen(cost[k]);
				row.children[2].textContent = yen(cost[k] * 12);
				row.classList.toggle('is-best', k === best);
			});
		};
		box.addEventListener('input', calc);
		calc();
	});

	/* スマホ固定CTA：少しスクロールしたら表示 */
	var sticky = document.querySelector('[data-sticky-cta]');
	if (sticky) {
		var onScroll = function () { sticky.hidden = window.scrollY < 600; };
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}
})();
