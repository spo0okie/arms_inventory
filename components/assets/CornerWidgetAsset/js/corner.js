/* Угловой блок CornerWidget: по умолчанию оверлей в правом верхнем углу родителя;
 * если оверлей накрывает контент соседей по родителю - переходит в собственную
 * строку (класс own-row). Работает со всеми .page-corner-widget на странице. */
(function(){
	var gap=8; //минимальный зазор между блоком и контентом, при котором считаем что коллизии нет

	function intersects(a,b){
		return a.left<b.right+gap && a.right+gap>b.left && a.top<b.bottom+gap && a.bottom+gap>b.top;
	}

	//пересекает ли прямоугольник br реальное содержимое родителя (строки текста,
	//иконки, картинки), а не блочные боксы, которые всегда растянуты на всю ширину;
	//собственное содержимое блока не считается
	function collides(block,br){
		var parent=block.parentElement;
		var walker=document.createTreeWalker(parent,NodeFilter.SHOW_TEXT);
		var node,range=document.createRange();
		while ((node=walker.nextNode())) {
			if (block.contains(node) || !node.nodeValue.trim()) continue;
			range.selectNodeContents(node);
			var rects=range.getClientRects();
			for (var i=0;i<rects.length;i++)
				if (rects[i].width && rects[i].height && intersects(br,rects[i])) return true;
		}
		var leafs=parent.querySelectorAll('i,svg,img,input,button');
		for (var j=0;j<leafs.length;j++) {
			if (block.contains(leafs[j])) continue;
			var r=leafs[j].getBoundingClientRect();
			if (r.width && r.height && intersects(br,r)) return true;
		}
		return false;
	}

	function initCorner(block){
		var parent=block.parentElement;
		if (!parent) return;

		//оверлей позиционируется относительно родителя - вьюхе незачем об этом знать
		if (getComputedStyle(parent).position==='static') parent.classList.add('position-relative');

		var scheduled=false;
		function update(){
			scheduled=false;
			block.classList.remove('own-row');	//меряем в режиме оверлея
			if (collides(block,block.getBoundingClientRect())) block.classList.add('own-row');
		}
		function schedule(){
			if (scheduled) return;
			scheduled=true;
			requestAnimationFrame(update);
		}

		window.addEventListener('resize',schedule);
		window.addEventListener('load',schedule);
		if (document.fonts && document.fonts.ready) document.fonts.ready.then(schedule);
		//контент меняется без перезагрузки (тогглер архивных, разворачиваемые карточки)
		if (window.ResizeObserver) new ResizeObserver(schedule).observe(parent);
		update();
	}

	document.querySelectorAll('.page-corner-widget').forEach(initCorner);
})();
