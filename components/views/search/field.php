<?php

/* @var $this yii\web\View */
/* @var $model string */
/* @var $field string */
/* @var $label string */

use yii\helpers\Url;
use yii\helpers\Inflector;

$modelId=Inflector::camel2id($model);

?>
<label class="search-label" for="<?= $modelId ?>-search" onclick="document.location.replace('<?= Url::to(['/'.$modelId.'/index']) ?>');"><?= $label ?></label>
<span class="search-group">
	<input id="<?= $modelId ?>-search" type="text" class="search-input" onkeypress="onkeypress = function(e){
		if (!e) e = window.event;
		let keyCode = e.code || e.key;
		if (keyCode == 'Enter' || keyCode == 'NumpadEnter'){
			document.location.assign('<?= Url::to(['/'.$modelId.'/index']) ?>?<?= $model ?>Search[<?= $field ?>]=' + $(this).val());
			return false;
		} //else console.log(keyCode);
	}">
</span>
