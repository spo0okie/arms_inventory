<?php

/* @var $this yii\web\View */

use app\components\SearchFieldWidget;

$this->title = 'Инвентаризация';
?>
<div class="site-index row col align-self-center">
	<?= $this->render('_about') ?>

	<div class="search">
		Поиск по базе данных:<br /><br />
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
			<?= SearchFieldWidget::widget(['model'=>'Contracts','field'=>'fullname','label'=>'Документы:\&gt; ']) ?>
		</p>
	</div>
	
</div>
