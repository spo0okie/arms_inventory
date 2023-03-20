<?php

/* @var $this yii\web\View */

use yii\helpers\Html;

$this->title = 'Инвентаризация';
?>
<div class="site-index row col align-self-center">
	<?= $this->render('_about') ?>

	<div class="search">
		Starting MS-DOS...<br />
		Поиск:<br />
		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Comps','field'=>'name','label'=>'Компьютеры:\&gt; ']) ?>
		</p>
		
		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Users','field'=>'shortName','label'=>'Пользователи:\&gt; ']) ?>
		</p>

		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Services','field'=>'name','label'=>'Сервисы:\&gt; ']) ?>
		</p>

		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'NetIps','field'=>'text_addr','label'=>'IP адреса:\&gt; ']) ?>
		</p>

		<p>
			<?= \app\components\SearchFieldWidget::widget(['model'=>'Contracts','field'=>'fullname','label'=>'Документы:\&gt; ']) ?>
		</p>
	</div>
	
</div>
