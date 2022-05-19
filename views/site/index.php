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
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Comps','field'=>'name','label'=>'Компьютеры:\&gt; ']) ?>
		</p>
		
		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Users','field'=>'Ename','label'=>'Пользователи:\&gt; ']) ?>
		</p>

		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Services','field'=>'name','label'=>'Сервисы:\&gt; ']) ?>
		</p>

		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'NetIps','field'=>'text_addr','label'=>'IP адреса:\&gt; ']) ?>
		</p>
	</div>
	
</div>
