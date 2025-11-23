<?php
//для рендера hostname в ОС и оборудовании

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $static_view boolean */
/* @var $hostname string */

$domainClass="small opacity-75";

if (\Yii::$app->params['domains.fqdn_hostname']) { ?>
	<?= $hostname ?><span class="<?= $domainClass ?>">.<?= $model->domain->fqdn??'- ошибочный домен -' ?></span>
<?php } else { ?>
	<span class="<?= $domainClass ?>"><?= $model->domain->name??'- ошибочный домен -' ?>\</span><?= $hostname ?>
<?php }