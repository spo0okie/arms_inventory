<?php
/**
 * Строчка АРМа в карте рабочих мест
 * User: Spookie
 * Date: 02.03.2018
 * Time: 14:14
 * @var \app\models\Arms $model
 * @var yii\web\View $this
 * @var string $cabinet_col первая колнка - название помещения
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

//объединение ячеек на все ОС
$rowspan=(count($comps)>1)?'rowspan="'.count($comps).'"':'';

//объединение ячеек на физ ОС
$rowspanPhys=($hwCount>1)?'rowspan="'.$hwCount.'"':'';

//может быть передан список столбцов, которые не нужно выводить
if (!isset($skip)) $skip=[];

$sortedComps=array_merge($hwComps,$vmComps);


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
    $comp=$comps[$i]; ?>
    <tr <?= $model->is_server?'class="server"':''?>>
	
		<?php //в самой первой строчке нужно вставить в начале колонку кабинета/помещения.
		// вставить надо только один раз, т.к. у нее rowspan=0 и она идет сквозняком до конца таблицы
		
		if (isset($cabinet_col)) {
			echo $cabinet_col;
			unset ($cabinet_col);
		}?>
		
		
		<?php if (!$i) { ?>
            <td class="arm_id" <?= $rowspan ?>><?= $this->render('/arms/item',['model'=>$model]) ?></td>
	    <?php } ?>
	    
	    <?php //если у нас есть ОС, то зададим ячейке класс свежести данных об этой ОС
	    	$age_class=is_object($comp)?$comp->updatedRenderClass:'';
	    ?>
        <td class="arm_hostname <?= $age_class ?>"><?= is_object($comp)?$this->render('/comps/item',['model'=>$comp]):$model->hostname ?></td>

		
        <?php if ($model->is_server) {
        	$services=[];
			if (isset($comp->services)) {
				$renderServices=$comp->services;
				\yii\helpers\ArrayHelper::multisort($renderServices,['name']);
				foreach ($renderServices as $svc)
					$services[]=$this->render('/services/item',['model'=>$svc]);
			}
			
				
			if (isset($comp->user))
				$services[]='<span class="glyphicon glyphicon-user small grayed-out href"></span> '.$this->render('/users/item',['model'=>$comp->user]);
			
			if (!empty($comp->comment))
				$services[]='<span class="grayed-out href"><span class="glyphicon glyphicon-comment small"></span> '.$comp->comment.'</span>';
	
			?>
            <td colspan="2" class="arm_services"><?= implode('<br />',$services); ?></td>
        <?php } else if (!$i) { ?>

            <td class="arm_uname" <?= $rowspan ?>>
                <?= (is_object($model->user))?$this->render('/users/item',['model'=>$model->user]):'' ?>
            </td>

            <td class="arm_uphone <?= count($model->voipPhones)?'tech_voip_phone':'' ?>" <?= $rowspan ?>>
		        <?php if (count($model->voipPhones)) {
		            $phones=[];
		            foreach ($model->voipPhones as $tech) $phones[]=$this->render('/techs/item',['model'=>$tech,'name'=>strlen($tech->comment)?$tech->comment:$tech->attachModel->shortest]);
		            echo implode('<br />',$phones);
		        } ?>
            </td>

        <?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
	    	if (!$i&&(array_search('arm_model',$skip)===false)) { ?>
            <td class="arm_model" <?= $rowspanPhys ?>>
				<?= $this->render('/tech-models/item',['model'=>$model->techModel,'compact'=>true]) ?>
			</td>
        <?php }} else { ?>
			<td class="arm_model">
				<abbr title="Virtual Machine">VM</abbr>
			</td>
		<?php } ?>

	    <?php if (!is_object($comp) || !$comp->ignore_hw) {
			if (!$i&&array_search('hardware',$skip)===false) { ?>
        	    <td class="hardware" <?= $rowspanPhys ?>>
					<?= $this->render('/hwlist/shortlist',['model'=>$model->hwList,'arm_id'=>$model->id]) ?>
					<?php if (count($model->ups)) {
						echo ' / ';
						foreach ($model->ups as $tech) {
							echo $this->render('/techs/item', ['model' => $tech, 'name' => $tech->model->shortest]);
						}
					}
					?>
				</td>
			<?php }
	    } else { ?>
			<td class="hardware">
				<?= $this->render('/hwlist/shortlist',['model'=>$comp->hwList,'vm'=>true,'comp_id'=>$comp->id]) ?>
			</td>
		<?php } ?>

	    <?php if (!$i) { ?>
            <td class="attachments" <?= $rowspan ?>>
			    <?= $this->render('/arms/item-attachments',compact('model'))?>
            </td>
	    <?php }?>

	    <?php if (!$i) { ?>
            <td class="item_status <?= strlen($model->stateName)?$model->state->code:'' ?>" title="<?= $model->comment ?>" <?= $rowspan ?>><?= $model->stateName ?></td>
	    <?php }?>

        <td class="item_ip"><?= is_object($comp)?$comp->currentIp:'' ?></td>

        <?php if (!$i) { ?>
            <td class="item_invnum"<?= $rowspan ?>>
				<?= $this->render('/arms/sn',compact('model'))?>
			</td>
	    <?php }?>
    </tr>

<?php }