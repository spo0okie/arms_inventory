<?php
/* Инициализация темы оформления (plans/themes.md).
 *
 * Рендерится ПЕРВЫМ в <head> обоих полных layout'ов (main.php, embed.php) —
 * атрибут data-bs-theme должен встать на <html> ДО загрузки CSS и первой
 * отрисовки, иначе страница мигнёт светлой темой (FOUC).
 *
 * Тема выбирается только на клиенте: серверный HTML одинаков для всех
 * пользователей — это требование кэшируемости (см. принцип «кэш требует
 * одинаковости HTML»). Выбор хранится в localStorage.armsTheme:
 *   'light' | 'dark' — явный выбор;
 *   'auto' — по системной prefers-color-scheme, со слежением за её сменой;
 *   ничего не выбрано — ВРЕМЕННО 'light' (пока тёмная тема на обкатке,
 *   plans/themes.md; после обкатки дефолт вернуть в 'auto').
 *
 * Здесь же — делегированный обработчик переключателя (пункты меню с
 * data-set-theme, см. menu.php) и подсветка активного пункта. Отдельный
 * vanilla-<script> по тем же причинам, что и тогглер справки в main.php:
 * независимое исполнение, делегирование на document без DOM-ready.
 *
 * Новая тема = новый набор переменных [data-bs-theme="имя"] в themes.css
 * + пункт меню; этот скрипт менять не требуется. */
?>
<script>
(function(){
	var KEY='armsTheme',
		mq=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)');
	function mode(){
		try{
			var m=localStorage.getItem(KEY);
			if(m) return m;
		}catch(_){}
		return 'light'; //временный дефолт на обкатку тёмной (см. шапку файла)
	}
	function apply(){
		var m=mode(),
			t=(m==='auto')?((mq&&mq.matches)?'dark':'light'):m;
		document.documentElement.setAttribute('data-bs-theme',t);
	}
	//подсветить активный пункт переключателя (галочки рисует CSS по .active)
	function mark(){
		var m=mode(),
			items=document.querySelectorAll('a[data-set-theme]');
		for(var i=0;i<items.length;i++)
			items[i].classList.toggle('active',items[i].getAttribute('data-set-theme')===m);
	}
	apply();
	if(mq&&mq.addEventListener) mq.addEventListener('change',apply);
	document.addEventListener('click',function(e){
		var a=e.target.closest&&e.target.closest('a[data-set-theme]');
		if(!a) return;
		e.preventDefault();
		try{localStorage.setItem(KEY,a.getAttribute('data-set-theme'));}catch(_){}
		apply();
		mark();
	});
	document.addEventListener('DOMContentLoaded',mark);
})();
</script>
