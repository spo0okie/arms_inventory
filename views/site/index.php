<?php

/* @var $this yii\web\View */

use app\components\SearchFieldWidget;

$this->title = 'Инвентаризация';
?>
<div class="site-index row col align-self-center">
	<?= $this->render('_about') ?>

	<div class="search">
		Поиск по базе данных: <?= \yii\helpers\Html::tag('span','<i class="far fa-question-circle"></i>',array_merge(
			['class'=>'attr-hint-icon','qtip_pin'=>'1'],
			\app\helpers\FieldsHelper::toolTipOptions('Поиск по базе данных',
				'Поиск объектов по имени: оборудование — по инвентарному номеру, '
				.'компьютеры и сервисы — по названию, пользователи — по ФИО, '
				.'документы — по названию, IP адреса — по адресу.<br>'
				.'Нажмите Enter — откроется список с этим фильтром.')
		)) ?><br /><br />
		<p>
			<?= SearchFieldWidget::widget(['model'=>'Techs','field'=>'num','label'=>'Оборудование:\&gt; ']) ?>
		</p>
		
		<p>
			<?= SearchFieldWidget::widget(['model'=>'Comps','field'=>'name','label'=>'Компьютеры:\&gt; ']) ?>
		</p>
		
		<p>
			<?= SearchFieldWidget::widget(['model'=>'Users','field'=>'shortName','label'=>'Пользователи:\&gt; ']) ?>
		</p>

		<p>
			<?= SearchFieldWidget::widget(['model'=>'Services','field'=>'name','label'=>'Сервисы:\&gt; ']) ?>
		</p>

		<p>
			<?= SearchFieldWidget::widget(['model'=>'NetIps','field'=>'text_addr','label'=>'IP адреса:\&gt; ']) ?>
		</p>

		<p>
			<?= SearchFieldWidget::widget(['model'=>'Contracts','field'=>'name','label'=>'Документы:\&gt; ']) ?>
		</p>
	</div>
	
</div>
