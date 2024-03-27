 <?php

use app\components\DynaGridWidget;
use app\components\ModelFieldWidget;
use app\models\OrgStruct;
use app\models\Users;
use app\models\UsersSearch;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel UsersSearch */

if (!isset($contracts)) $contracts=$model->docs;

$this->title = $model->uname;
$this->params['breadcrumbs'][] = ['label' => 'Контрагенты', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="partners-view">
	<div class="row">
		<div class="col-md-6">
			<?= $this->render('card',['model'=>$model]) ?>
		</div>
		<div class="col-md-6">
			<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'services']) ?>
			<?= ModelFieldWidget::widget([
				'model'=>$model,
				'field'=>'docs',
				'item_options'=>['partner'=>false,'show_payment'=>true],
				'glue'=>'<br/>'
			]) ?>
		</div>
	</div>
	<div class="my-3">
		<?= Html::a(OrgStruct::$title,['org-struct/index','org_id'=>$model->id]) ?>
		//
		<?= Html::a('Новый '. Users::$title,['users/create','Users[org_id]'=>$model->id],[
			'class'=>'open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]) ?>
	</div>
	<?= $dataProvider->totalCount?DynaGridWidget::widget([
		'id' => 'org-struct-users-index',
		'columns' => require __DIR__.'/../users/columns.php',
		'header' => 'Пользователи организации',
		'defaultOrder' => ['employee_id','shortName','Doljnost','orgStruct_name','Login','Email','Phone','arms','Mobile'],
		//'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		//'hintButton' => \app\components\HintIconWidget::widget(['model'=>'\app\models\Users','cssClass'=>'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]):'' ?>
</div>
