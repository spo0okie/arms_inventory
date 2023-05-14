<?php
/**
 * Строчка АРМа в карте рабочих мест
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Techs 	$model
 * @var \app\models\Techs[]	$techs
 * @var yii\web\View        $this
 * @var string              $cabinet_col первая колнка - название помещения
 */

//подгружаем все ОС АРМа
$comps=$model->comps;
//если ни одной не нашли, то создаем массив из пустого элемента чтобы вывести данные по АРМ без ОС
if (!count($comps)) $comps=[0=>null];


//если ОС больше одной, то готовим ROWSPAN для колонок не относящихся к ОС
$hwComps=$model->hwComps;
$vmComps=$model->vmComps;
$hwCount=count($hwComps);
$vmCount=count($vmComps);

if (!isset($show_archived)) $show_archived=true;

//объединение ячеек на все ОС
$rowspan=(count($comps)>1)?'rowspan="'.count($comps).'"':'';

//объединение ячеек на физ ОС
$rowspanPhys=($hwCount>1)?'rowspan="'.$hwCount.'"':'';

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
for ($i=0; $i<count($comps); $i++) {
	$archClass=($model->archived?'archived-item':'').' '.($is_server?'server':'');
	$archDisplay=($model->archived&&!$show_archived)?'style="display:none"':'';
    $comp=$comps[$i]; ?>
    <tr>
	
		<?php //в самой первой строчке нужно вставить в начале колонку кабинета/помещения.
		// вставить надо только один раз, т.к. у нее rowspan=0 и она идет сквозняком до конца таблицы
		
		if (isset($cabinet_col)) {
			echo $cabinet_col;
			unset ($cabinet_col);
		}?>
		
		
		<?php if (!$i) { ?>
            <td class="arm_id <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspan ?>><?= $this->render('/techs/item',['model'=>$model]) ?></td>
	    <?php } ?>
	    
	    <?php //если у нас есть ОС, то зададим ячейке класс свежести данных об этой ОС
	    	$age_class=is_object($comp)?$comp->updatedRenderClass:'';
	    ?>
        <td class="arm_hostname <?= $age_class ?> <?= $archClass ?>" <?=$archDisplay ?>><?= is_object($comp)?$this->render('/comps/item',['model'=>$comp]):'' ?></td>

		
        <?php if (count($model->compsServices)) {
        	$services=[];
			if (isset($comp->services)) {
				$renderServices=$comp->services;
				\yii\helpers\ArrayHelper::multisort($renderServices,['name']);
				foreach ($renderServices as $svc)
					$services[]=$this->render('/services/item',['model'=>$svc,'show_archived'=>$show_archived]);
			}
			
				
			if (isset($comp->user))
				$services[]='<span class="fas fa-user small grayed-out href"></span> '.$this->render('/users/item',['model'=>$comp->user]);
			
			if (!empty($comp->comment))
				$services[]='<span class="grayed-out href"><span class="fas fa-comment small"></span> '.$comp->comment.'</span>';
	
			?>
            <td colspan="2" class="arm_services <?= $archClass ?> " <?= $archDisplay ?>"><?= implode(' ',$services); ?></td>
        <?php } else if (!$i) { ?>

            <td class="arm_uname <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspan ?>>
                <?= (is_object($model->user))?$this->render('/users/item',['model'=>$model->user]):'' ?>
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
		            	\app\helpers\ArrayHelper::deleteByField($techs,'num','rendered');
					}
		            
		            echo implode('<br />',$phones);
		        } ?>
            </td>

        <?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
	    	if (!$i&&(array_search('arm_model',$skip)===false)) { ?>
            <td class="arm_model <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspanPhys ?>>
				<?= $this->render('/tech-models/item',['model'=>$model->model,'compact'=>true]) ?>
			</td>
        <?php }} else { ?>
			<td class="arm_model <?= $archClass ?>" <?= $archDisplay ?>>
				<abbr title="Virtual Machine">VM</abbr>
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
							\app\helpers\ArrayHelper::deleteByField($techs,'id',$tech->id);
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
							\app\helpers\ArrayHelper::deleteByField($techs,'id',$tech->id);
						}
					}
					?>
				</td>
			<?php }
	    } else { ?>
			<td class="hardware <?= $archClass ?>" <?= $archDisplay ?>>
				<?= $this->render('/hwlist/shortlist',['model'=>$comp->hwList,'vm'=>true,'comp_id'=>$comp->id]) ?>
			</td>
		<?php } ?>

	    <?php if (!$i) { ?>
            <td class="attachments <?= $archClass ?>" <?= $archDisplay ?> <?= $rowspan ?>>
			    <?= $this->render('/techs/map/item-attachments',compact('model'))?>
            </td>
	    <?php }?>

	    <?php if (!$i) { ?>
            <td class="item_status <?= strlen($model->stateName)?$model->state->code:'' ?> <?= $archClass?>" <?= $archDisplay ?> title="<?= $model->comment ?>" <?= $rowspan ?>><?= $model->stateName ?></td>
	    <?php }?>

        <td class="item_ip <?= $archClass ?>" <?= $archDisplay ?>><?= is_object($comp)?$comp->currentIp:'' ?></td>

        <?php if (!$i) { ?>
            <td class="item_invnum <?= $archClass ?>" <?= $archDisplay ?><?= $rowspan ?>>
				<?= $this->render('/arms/sn',compact('model'))?>
			</td>
	    <?php }?>
    </tr>

<?php }