<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 13.07.2020
 * Time: 1:07
 */
/* @var $this yii\web\View */

use yii\helpers\Url;

$path=Yii::$app->request->getUrl();
?>

<div class="site-access-denied row col align-self-center">
	
	<div class="body-content">
		
		<?= $this->render('../_about') ?>

		<div class="row">
			<div class="col-lg-3">
			</div>
			<div class="col-lg-6 text-center">
				<div class="card text-center border-danger ">
					<div class="card-header bg-danger">
						<h2 class="card-title">Доступ закрыт</h2>
					</div>
					<div class="card-body">
						<h5>Доступ к этой секции закрыт, т.к. у вас недостаточно прав.</h5>
						<p class="p-3">Возможно необходимо авторизоваться под более привилегированной учетной записью или запросить права в отделе ИТ.</p>
						<!--suppress HtmlUnknownTarget -->
						<a class="btn btn-danger" href="<?= Url::to(['site/login']) ?>?return=<?= $path ?>">Авторизоваться</a>
					</div>
				</div>
			</div>
			<div class="col-lg-3">
			</div>
		</div>
	
	</div>
</div>
