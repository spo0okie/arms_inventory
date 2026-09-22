<?php
/**
 * Строчка АРМа в карте рабочих мест
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var Techs 	$model
 * @var Techs[]	$techs
 * @var yii\web\View        $this
 * @var string              $cabinet_col первая колнка - название помещения
 */

//подгружаем все ОС АРМа
use app\helpers\ArrayHelper;
use app\models\Techs;

use app\components\ShowArchivedWidget;
use app\components\widgets\page\ModelWidget;
$comps=$model->comps;
//живые ОС вперёд (внутри групп HW/VM — порядок ignore_hw сохраняем, на нём держится rowspanPhys):
//архивные строки уходят в конец rowspan-диапазона и скрываются целиком <tr>, а первая строка несёт
//ячейки уровня АРМ и скрываться не должна
usort($comps,fn($a,$b)=>[(int)$a->ignore_hw,(int)$a->archived]<=>[(int)$b->ignore_hw,(int)$b->archived]);
//если ни одной не нашли, то создаем массив из пустого элемента чтобы вывести данные по АРМ без ОС
if (!count($comps)) $comps=[0=>null];


//если ОС больше одной, то готовим ROWSPAN для колонок не относящихся к ОС
$hwComps=$model->hwComps;
$vmComps=$model->vmComps;
$hwCount=count($hwComps);
$vmCount=count($vmComps);

if (!isset($show_archived)) $show_archived=true;

//строки архивных ОС живого АРМ, скрываемые целиком (первую не скрываем — в ней ячейки АРМ)
$trArchivedRows=[];
foreach ($comps as $i=>$comp)
	$trArchivedRows[$i]=$i && is_object($comp) && $comp->archived && !$model->archived;

/**
 * rowspan с двумя значениями: на все строки и без скрываемых архивных.
 * Сервер ставит актуальное, тогглер архивных переключает по data-rowspan-full/live
 * (ShowArchivedWidget::$scriptOn/$scriptOff).
 */
$rowspanAttr=function (int $full, int $live) use ($show_archived) {
	if ($full<=1) return '';
	if ($full==$live) return 'rowspan="'.$full.'"';
	return 'rowspan="'.($show_archived?$full:$live).'" data-rowspan-full="'.$full.'" data-rowspan-live="'.$live.'"';
};

//объединение ячеек на все ОС
$rowspan=$rowspanAttr(count($comps),count($comps)-count(array_filter($trArchivedRows)));

//объединение ячеек на физ ОС
$hwHidden=0;
foreach ($comps as $i=>$comp) if ($trArchivedRows[$i] && !$comp->ignore_hw) $hwHidden++;
$rowspanPhys=$rowspanAttr($hwCount,$hwCount-$hwHidden);

//может быть передан список столбцов, которые не нужно выводить
if (!isset($skip)) $skip=[];

$sortedComps=array_merge($hwComps,$vmComps);

$armTechs=$model->filterArmTechs($techs);

$voipPhones=[];
$ups=[];
$monitors=[];
foreach ($armTechs as $tech) {
	if ($tech->isVoipPhone)
		$voipPhones[]=$tech;
	if ($tech->isUps)
		$ups[]=$tech;
	if ($tech->isMonitor)
		$monitors[]=$tech;
}

$compsServices=$model->compsServices;
$is_server=(bool)(count($compsServices));

/*
 * Вдруг откуда ни возьмись дока внутри пых-файла
 * Как будем рисовать оборудования сервера. Учитывая что на один АРМ несколько ОС
 * Модель оборудования очевидно растянется на все ОС, ибо железо 1.
 * Затем все ос делим на HW и VM по признаку ignore_hw
 *
 * Оборудование фактическое (не модель и не спека) берем из списка железных ОС:
 *  из основной (если она железная) или с наименьшим id (иначе)
 *
 *
 */

//поехали!
/*
 * Архивность: ячейки уровня АРМ (с rowspan) скрываются только по архивности самого АРМ.
 * Архивная ОС живого АРМ: не первая строка — скрываем весь <tr> (display:none) и одновременно
 * уменьшаем rowspan ячеек АРМ на число скрытых строк (иначе rowspan съедает строки следующего
 * АРМ; visibility:collapse не годится — обрезает rowspan-ячейки, текст по центру режется пополам).
 * Первая строка — только содержимое её ячеек (hostname/сервисы/IP/VM). Прятать сами <td> нельзя:
 * ячейка с display:none выпадает из сетки, и соседние ячейки съезжают под чужие колонки.
 */
$archClass=($model->archived?'archived-item':'').' '.($is_server?'server':'');
$archDisplay=($model->archived&&!$show_archived)?'style="display:none"':'';
$archHidden=$model->archived&&!$show_archived;

for ($i=0; $i<count($comps); $i++) {
    $comp=$comps[$i];
	$compArchived=is_object($comp)&&$comp->archived&&!$model->archived;
	$trArchived=$trArchivedRows[$i];
	//обёртка содержимого ячеек архивной ОС в первой строке
	$compWrap=fn(string $html)=>($compArchived&&!$trArchived)
		?'<span class="archived-item" '.($show_archived?'':'style="display:none"').'>'.$html.'</span>'
		:$html;
	?>
    <tr <?= $trArchived?('class="'.ShowArchivedWidget::$rowClass.'"'.($show_archived?'':' style="display:none"')):'' ?>>
	
		<?php //в самой первой строчке нужно вставить в начале колонку кабинета/помещения.
		// вставить надо только один раз, т.к. у нее rowspan=0 и она идет сквозняком до конца таблицы
		
		if (isset($cabinet_col)) {
			echo $cabinet_col;
			unset ($cabinet_col);
		}?>
		
		
		<?php if (!$i) { ?>
            <td class="arm_id <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspan ?>><?= ModelWidget::widget(['model'=>$model]) ?></td>
	    <?php } ?>
	    
	    <?php //если у нас есть ОС, то зададим ячейке класс свежести данных об этой ОС
	    	$age_class=is_object($comp)?$comp->updatedRenderClass:'';
		?>
        <td class="arm_hostname <?= $age_class ?> <?= $archClass ?>" <?=$archDisplay ?>><?= is_object($comp)?$compWrap(ModelWidget::widget(['model'=>$comp])):'' ?></td>

		
        <?php if (count($model->compsServices)) {
        	$services=[];
			if (isset($comp->services)) {
				$renderServices=$comp->services;
				\yii\helpers\ArrayHelper::multisort($renderServices,['name']);
				foreach ($renderServices as $svc)
					$services[]=ModelWidget::widget(['model'=>$svc,'options'=>['show_archived'=>$show_archived,'noDelete'=>true]]);
			}
			
				
			if (isset($comp->user))
				$services[]='<span class="fas fa-user small grayed-out href"></span> '.ModelWidget::widget(['model'=>$comp->user,'options'=>['noDelete'=>true]]);
			
			if (!empty($comp->comment))
				$services[]='<span class="grayed-out href"><span class="fas fa-comment small"></span> '.$comp->comment.'</span>';
	
			?>
            <td colspan="2" class="arm_services <?= $archClass ?> " <?= $archDisplay ?>><?= $compWrap(implode(' ',$services)); ?></td>
        <?php } else if (!$i) { ?>

            <td class="arm_uname <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspan ?>>
                <?= (is_object($model->user))?ModelWidget::widget(['model'=>$model->user]):'' ?>
            </td>

            <td class="arm_uphone <?= count($voipPhones)?'tech_voip_phone':'' ?>  <?= $archClass?>" <?= $archDisplay ?> <?= $rowspan ?>>
		        <?php if (count($voipPhones)) {
		            $phones=[];
		            foreach ($voipPhones as $tech) {
		            	$phones[]=$this->render('/techs/item',[
		            		'model'=>$tech,
							'name'=>strlen($tech->comment)?$tech->comment:$tech->attachModel->shortest
						]);
		            	$tech->num='rendered';
		            	ArrayHelper::deleteByField($techs,'num','rendered');
					}
		            
		            echo implode('<br />',$phones);
		        } ?>
            </td>

        <?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
	    	if (!$i&&(array_search('arm_model',$skip)===false)) { ?>
            <td class="arm_model <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspanPhys ?>>
				<?= ModelWidget::widget(['model'=>$model->model,'options'=>['compact'=>true]]) ?>
			</td>
        <?php }} else { ?>
			<td class="arm_model <?= $archClass ?>" <?= $archDisplay ?>>
				<?= $compWrap('<abbr title="Virtual Machine">VM</abbr>') ?>
			</td>
		<?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
			if (!$i&&array_search('hardware',$skip)===false) { ?>
        	    <td class="hardware <?= $archClass?>" <?= $archDisplay ?> <?= $rowspanPhys ?>>
					<?= $this->render('/hwlist/shortlist',['model'=>$model->hwList,'arm_id'=>$model->id]) ?>
					<?php if (count($monitors)) {
						echo ' / ';
						foreach ($monitors as $tech) {
							echo $this->render('/techs/item', [
								'model' => $tech,
								'name' => $tech->model->shortest,
								'static_view' => true
							]);
							$tech->num='rendered';
							ArrayHelper::deleteByField($techs,'id',$tech->id);
						}
					}
					?>
					<?php if (count($ups)) {
						echo ' / ';
						foreach ($ups as $tech) {
							echo $this->render('/techs/item', [
								'model' => $tech,
								'name' => $tech->model->shortest,
								'static_view' => true
							]);
							$tech->num='rendered';
							ArrayHelper::deleteByField($techs,'id',$tech->id);
						}
					}
					?>
				</td>
			<?php }
	    } else { ?>
			<td class="hardware <?= $archClass ?>" <?= $archDisplay ?>>
				<?= $compWrap($this->render('/hwlist/shortlist',['model'=>$comp->hwList,'vm'=>true,'comp_id'=>$comp->id])) ?>
			</td>
		<?php } ?>

	    <?php if (!$i) { ?>
            <td class="attachments <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspan ?>>
			    <?= $this->render('/techs/map/item-attachments',compact('model'))?>
            </td>
	    <?php }?>

	    <?php if (!$i) {
			//класс/стиль маркера состояния (легаси CSS-класс по коду — fallback);
			//display:none архивных вклеивается в тот же style-атрибут
			$stateClass=strlen($model->stateName)?$model->state->markerClass($model->state->code):'';
			$stateStyle=strlen($model->stateName)?$model->state->markerStyle():'';
			if ($archHidden) $stateStyle=trim($stateStyle.';display:none',';');
		?>
            <td class="item_status <?= $stateClass ?> <?= $archClass?>" style="<?= $stateStyle ?>" title="<?= $model->comment ?>" <?= $rowspan ?>><?= $model->stateName ?></td>
	    <?php }?>

        <td class="item_ip <?= $archClass ?>" <?= $archDisplay ?>><?= is_object($comp)?$compWrap((string)$comp->currentIp):'' ?></td>

        <?php if (!$i) { ?>
            <td class="item_invnum <?= $archClass ?>" <?= $archDisplay ?><?= $rowspan ?>>
				<?= $this->render('/arms/sn',compact('model'))?>
			</td>
	    <?php }?>
    </tr>

<?php }


