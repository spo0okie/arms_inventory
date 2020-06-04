<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */


if (is_object($model)) {
	$icon=$model->is_end_user?
		'<span class="glyphicon glyphicon-user"></span>':
		'<span class="glyphicon glyphicon-cog"></span>';
	$icon='<small>'.$icon.'</small>&nbsp;';
	
	$name=$model->name;
	if (!empty($crop_site)) {
		// вырезаем имя площадки из имени
		if (count($model->sites)==1) {
			$site=$model->sites[0];
			$cropped=false;
			//ищем полное имя
			$pos_full=mb_strpos($name,$site->name);
			$pos_short=mb_strpos($name,$site->short);
			
			if ($pos_full===0) {
				$name=mb_substr($name,mb_strlen($site->name));
				//$name='{'.$name;
				$cropped=true;
			} elseif ($pos_full===(mb_strlen($name)-mb_strlen($site->name))) {
				$name=mb_substr($name,0,mb_strlen($name)-mb_strlen($site->name));
				//$name=$name.'}';
				$cropped=true;
			} elseif ($pos_short===0) {
				$name=mb_substr($name,mb_strlen($site->short));
				//$name='<'.$name;
				$cropped=true;
			} elseif ($pos_short===(mb_strlen($name)-mb_strlen($site->short))) {
				$name=mb_substr($name,0,mb_strlen($name)-mb_strlen($site->short));
				//$name=$name.'>';
				$cropped=true;
			}
			
			if ($cropped) {
				$name=trim($name," \t:-/\\");
			}
		}
	}
	?>
	<span class="services-item" qtip_ajxhrf="<?= \yii\helpers\Url::to(['/services/ttip','id'=>$model->id]) ?>">
		<?= Html::a($icon.$name,['/services/view','id'=>$model->id]	) ?>
    	<?= Html::a('<span class="glyphicon glyphicon-pencil"></span>',['/services/update','id'=>$model->id]) ?>
	</span>
<?php }