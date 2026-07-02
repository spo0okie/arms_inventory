<?php
//для рендера hostname в ОС и оборудовании

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
/** @var yii\web\View $this */
/** @var app\models\Comps $model */
/** @var boolean $static_view */
/** @var string $hostname */

if (!isset($hostname)) $hostname=$model->name;

$domainClass="small opacity-75";

if (\Yii::$app->params['domains.fqdn_hostname']) { ?>
	<?= $hostname ?><span class="<?= $domainClass ?>">.<?= $model->domain->fqdn??'- ошибочный домен -' ?></span>
<?php } else { ?>
	<span class="<?= $domainClass ?>"><?= $model->domain->name??'- ошибочный домен -' ?>\</span><?= $hostname ?>
<?php }
