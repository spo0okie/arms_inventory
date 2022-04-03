<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'Инвентаризация';
?>
<div class="site-index row col align-self-center">
	<h1>⬢ инвентаризация</h1>
	<p class="lead">База данных софта, железа и лицензий</p>
	<ul>
		<li><a href="https://github.com/spo0okie/arms_inventory">github repository</a> </li>
		
		<li><a href="https://github.com/spo0okie/arms_inventory/commits/master">dev history</a> </li>

		<li><a href="https://github.com/spo0okie/arms_inventory/issues">issues list</a> </li>
	</ul>


	<div class="search">
		Starting MS-DOS...<br />
		Поиск:<br />
		<p>
			<label class="search-label" for="comps" onclick="document.location.replace('<?= \yii\helpers\Url::to(['/comps/index']) ?>');">Компьютеры:\&gt; </label>
			<span class="search-group">
				<input id="comps-search" type="text" class="search-input" onkeypress="onkeypress = function(e){
					if (!e) e = window.event;
					let keyCode = e.code || e.key;
					if (keyCode == 'Enter'){
						document.location.replace('<?= \yii\helpers\Url::to(['/comps/index']) ?>?CompsSearch[name]=' + $(this).val());
						return false;
					}
				}">
			</span>
		</p>
		
		<p>
			<label class="search-label" for="comps" onclick="document.location.replace('<?= \yii\helpers\Url::to(['/users/index']) ?>');">Пользователи:\&gt; </label>
			<span class="search-group">
				<input id="users-search" type="text" class="search-input" onkeypress="onkeypress = function(e){
					if (!e) e = window.event;
					let keyCode = e.code || e.key;
					if (keyCode == 'Enter'){
						document.location.replace('<?= \yii\helpers\Url::to(['/users/index']) ?>?UsersSearch[Ename]=' + $(this).val());
						return false;
					}
				}">
			</span>

		</p>
	</div>
	
</div>
