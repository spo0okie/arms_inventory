<?php

/* @var $this yii\web\View */
/* @var $title string заголовок страницы (первый H1 файла) */
/* @var $html string отрендеренный HTML страницы */

$this->title = $title;
$this->params['breadcrumbs'][] = ['label' => 'Документация', 'url' => ['index']];
$this->params['breadcrumbs'][] = $title;

?>
<div class="docs-page">
	<?= $html ?>
</div>
