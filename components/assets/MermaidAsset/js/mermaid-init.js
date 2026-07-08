/*
 * Инициализация mermaid для встроенной документации (plans/help-content-review.md).
 * kartik-markdown выдаёт для ```mermaid ``` блок <pre><code class="mermaid">…</code></pre>;
 * приводим его к <div class="mermaid"> (textContent уже деэкранирован),
 * рендерим лениво через IntersectionObserver - чтобы диаграммы в свёрнутых
 * инфоблоках (DocsPanelWidget, display:none) рисовались при раскрытии,
 * а не пытались считать размеры в скрытом состоянии.
 */
(function () {
	if (typeof mermaid === 'undefined') return;

	//pre>code.mermaid -> div.mermaid
	document.querySelectorAll('pre > code.mermaid').forEach(function (code) {
		var div = document.createElement('div');
		div.className = 'mermaid';
		div.textContent = code.textContent;
		code.parentNode.replaceWith(div);
	});

	var nodes = document.querySelectorAll('div.mermaid');
	if (!nodes.length) return;

	mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'strict' });

	function render(el) {
		if (el.getAttribute('data-processed')) return;
		try {
			mermaid.run({ nodes: [el] });
		} catch (e) {
			console.error('mermaid render failed', e);
		}
	}

	//рендерим когда элемент реально виден (получил размеры) - работает и для
	//диаграмм внутри свёрнутых панелей, раскрываемых позже
	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					render(en.target);
					io.unobserve(en.target);
				}
			});
		});
		nodes.forEach(function (n) { io.observe(n); });
	} else {
		nodes.forEach(render);
	}
})();
