<?php

namespace app\models;

use Yii;
use yii\web\JsExpression;

/**
 * This is the model class for table "contracts".
 *
 * !ВНИМАНИЕ! в модели используется свзь типа потомок родитель
 * в случае интенсивоного использования таких связей количество запросов к БД
 * будет расти невероятным образом, поэтмоу по-хорошему надо добавить в таблицу
 * ключит для хранения NESTED TREE!
 *
 * @property int $id id
 * @property int $parent_id Родительский договор
 * @property bool $is_successor Замещает родительский договор
 * @property string $date Дата документа
 * @property string $end_date Дата окончания действия документа
 * @property string $name Название документа
 * @property string $sname Полное наименование документа
 * @property string $sAttach строка с иконками приложений
 * @property string $comment Комментарий
 * @property string $partnersNames имена партнеров (ч/з запятую)
 * @property array $partners_ids массив ссылок на контрагентов в договоре
 * @property array $arms_ids массив ссылок на контрагентов в договоре
 * @property array $techs_ids массив ссылок на контрагентов в договоре
 * @property array $lics_ids массив ссылок на контрагентов в договоре
 * @property array $partners массив объектов контрагентов в договоре
 * @property array $scans массив сканов документов в договоре
 * @property array $scans_ids массив ссылок на сканы в договоре
 *
 * @property Contracts $parent
 * @property Contracts $successor
 * @property Contracts[] $successors
 * @property Contracts[] $successorsChain
 * @property Contracts[] $successorsRecursive
 * @property Contracts $predecessor
 * @property Contracts[] $childs
 * @property Arms[] $arms
 * @property Materials[] $materials
 * @property Techs[] $techs
 * @property LicItems[] $licItems
 * @property Arms[] $armsChain
 * @property Techs[] $techsChain
 * @property LicItems[] $licsChain
 * @property OrgPhones[] $orgPhones
 * @property OrgPhones[] $phonesChain
 * @property OrgInets[] $orgInets
 * @property OrgInets[] $inetsChain
 */
class Contracts extends \yii\db\ActiveRecord
{

	public static $title="Документы";


	public $scanFile;

	private $partners_cache=null;
	private $successors_chain_cache=null;
	private $techs_chain_cache=null;
	private $arms_chain_cache=null;
	private $inets_chain_cache=null;
	private $phones_chain_cache=null;
	private $lics_chain_cache=null;

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contracts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
	        [['lics_ids','partners_ids','arms_ids','techs_ids'], 'each', 'rule'=>['integer']],
	        //[['scanFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, pdf, gif', 'maxSize' => 1024*1024*30],
	        [['parent_id','state_id'], 'integer'],
	        [['total','charge'], 'number'],
	        [['is_successor'], 'boolean'],
            [['comment'], 'string'],
            [['name','date','end_date'], 'string', 'max' => 128],
	        [['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contracts::className(), 'targetAttribute' => ['parent' => 'id']],
        ];
    }

	/**
	 * В списке поведений прикручиваем many-to-many контрагентов
	 * @return array
	 */
    public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'partners_ids' => 'partners',
					'lics_ids' => 'licItems',
					'arms_ids' => 'arms',
					'techs_ids' => 'techs',
					'materials_ids' => 'materials'
				]
			]
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'parent' => 'Основной документ',
			'parent_id' => 'Основной документ',
			'is_successor' => 'Заменяет основной документ',
			'name' => 'Название документа',
			'scanFile' => 'Скан документа',
			'date' => 'Дата',
			'total' => 'Сумма',
			'charge' => 'НДС',
			'end_date' => 'Окончание',
			'partners_ids' => 'Контрагенты',
			'lics_ids' => 'Лицензии',
			'techs_ids' => 'Оборудование',
			'partnersNames' => 'Контрагент(ы)',
			'arms_ids' => 'АРМы',
			'scans_ids' => 'Сканы',
			'state_id' => 'Статус',
			'comment' => 'Комментарий',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'parent' => 'Если этот документ дополняет основной',
			'parent_id' => 'Если этот документ дополняет основной',
			'name' => 'Полное название документа как в нем написано (Счет № / договор № / накладная / и т.д.).',
			'date' => 'Дата документа / начало действия, если есть срок действия',
			'total' => 'Если по документу предполагается оплата какойто суммы, то ее надо вписать сюда',
			'end_date' => 'Если документ имеет срок действия - то до какой даты',
			'partners_ids' => 'Если отсутствуют, значит документ внутренний',
			'arms_ids' => 'С какими рабочими местами связан документ (если связан)',
			'techs_ids' => 'С каким оборудованием связан документ (если связан)',
			'state_id' => 'Для удобства контроля процессов оплаты',
			'scans_ids' => 'Отсканированная версия документа',
		];
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasOne(Contracts::className(), ['id' => 'parent_id']);
    }

	/**
	 * ищет одного наследника (один уровень наследования + самый молодой)
	 * @return \yii\db\ActiveQuery
	 */
	public function getSuccessor()
	{
		return $this->hasOne(Contracts::className(), ['parent_id' => 'id'])
			->andWhere(['is_successor'=>true])
			->orderBy(['date'=>SORT_DESC]);
	}

	/**
	 * Ищет всех непосредственных наследников (один уровень наследования)
	 * @return \yii\db\ActiveQuery
	 */
	public function getSuccessors()
	{
		return $this->hasMany(Contracts::className(), ['parent_id' => 'id'])
			->andWhere(['is_successor'=>true])
			->orderBy(['date'=>SORT_DESC]);
	}

	/**
	 * Ищет всех непосредственных наследников (один уровень наследования)
	 * @return \app\models\Contracts[]
	 */
	public function getSuccessorsRecursive()
	{
		//собираем непосредственных наследников
		$successors=$this->successors;
		//перебираем их
		foreach ($successors as $successor) {
			//если у них тоже есть наследники - добавляем
			$subSuccessors=$successor->getSuccessorsRecursive();
			if (count($subSuccessors)) $successors=array_merge($successors,$subSuccessors);
		}
		return $successors;
	}

	/**
	 * @return \app\models\Contracts
	 */
	public function getPredecessor()
	{
		if ($this->is_successor) return $this->parent;
		return null;
	}

	/**
	 * Цепь предшественников (не родителей)
	 * @return \yii\db\ActiveQuery[]
	 */
	public function getPredecessorChain()
	{
		$chain=[];
		$doc=$this;
		while (is_object($doc->predecessor)) {
			$doc=$doc->predecessor;
			$chain[]=$doc;
		}
		return $chain;
	}

	/**
	 * Цепь всех наследников (не просто потомков) отсортированная по дате
	 * @return \app\models\Contracts[]
	 */
	public function getSuccessorsChain()
	{
		if (!is_null($this->successors_chain_cache)) return $this->successors_chain_cache;

		$root=$this;
		while (is_object($root->predecessor)) $root=$root->predecessor;
		//составляем цепочку из всех потомков и их потомков
		$chain=$root->getSuccessorsRecursive();
		//и себя
		$chain[]=$root;
		//надо отсортировать по дате
		usort($chain,function($a,$b){if ($a->date==$b->date) return 0; return ($a->date<$b->date)?-1:1;});
		//кэшируем
		return $this->successors_chain_cache=$chain;
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getChilds()
	{
		return $this->hasMany(Contracts::className(), ['parent_id' => 'id'])
			->andWhere(['is_successor'=>false]);
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getChainChilds()
	{
		$chain=$this->successorsChain;
		$childs=[];
		foreach ($chain as $item) $childs=array_merge($childs,$item->childs);
		return $childs;
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgInets()
	{
		return $this->hasMany(OrgInet::className(), ['contracts_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgPhones()
	{
		return $this->hasMany(OrgPhones::className(), ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор контрагентов в договоре
	 * @return string
	 */
	public function getPartnersNames()
	{
		if (is_array($partners=$this->partners)&&count($partners)) {
			$names=[];
			foreach ($partners as $partner) $names[]=$partner->sname;
			return implode(',',$names);
		} else {
			return 'Внутр. документ';
		}
	}

	public function getDatePart()
	{
		if (strlen($this->date)) {
			if (strlen($this->end_date))
				return $this->date.' - '.$this->end_date;
			else
				return $this->date;
		} else
		return 'нет даты';
	}

	public function getSname()
	{
		return $this->datePart.' - '.$this->name.' - '.$this->partnersNames;
	}

	public function getSAttach()
	{
		$attaches='';
		if (count($this->childs)) $attaches.='<span class="glyphicon glyphicon-paperclip" title="Привязаны документы: '.(count($this->childs)).'шт"></span>';
		if (count($this->arms)) $attaches.='<span class="fas fa-desktop" title="Привязаны АРМы: '.(count($this->arms)).'шт"></span>';
		if (count($this->techs)) $attaches.='<span class="fas fa-print" title="Привязана техника: '.(count($this->techs)).'шт"></span>';
		if (count($this->licItems)) $attaches.='<span class="fas fa-award" title="Привязаны лицензии: '.(count($this->licItems)).'шт"></span>';
		if (count($this->orgInets)) $attaches.='<span class="glyphicon glyphicon-globe" title="Привязаны услуги интернет: '.(count($this->orgInets)).'шт"></span>';
		if (count($this->orgPhones)) $attaches.='<span class="glyphicon glyphicon-phone-alt" title="Привязаны услуги телефонии: '.(count($this->orgPhones)).'шт"></span>';
		return $attaches;
	}


	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getState()
	{
		return $this->hasOne(ContractsStates::className(), ['id' => 'state_id']);
	}

	public function getStateName()
	{
		if (is_object($this->state)) return $this->state->name;
		return '';
	}


	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getScans()
	{
		return static::hasMany(Scans::className(), ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанных Армов
	 */
	public function getArms()
	{
		return static::hasMany(Arms::className(), ['id' => 'arms_id'])
			->viaTable('{{%contracts_in_arms}}', ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанных Армов
	 */
	public function getMaterials()
	{
		return static::hasMany(Materials::className(), ['id' => 'materials_id'])
			->viaTable('{{%contracts_in_materials}}', ['contracts_id' => 'id']);
	}

	/**
	 * Возвращает набор связанной техники
	 */
	public function getTechs()
	{
		return static::hasMany(Techs::className(), ['id' => 'techs_id'])
			->viaTable('{{%contracts_in_techs}}', ['contracts_id' => 'id']);
	}

	public function getLicItems()
	{
		return static::hasMany(LicItems::className(), ['id' => 'lics_id'])
			->viaTable('{{%contracts_in_lics}}', ['contracts_id' => 'id']);
	}

	/**
	 * набор всех армов привязанных к цепочке документов
	 * @return \app\models\Arms[]
	 */
	public function getArmsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->arms_chain_cache)) return $this->arms_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->arms as $arm)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$arm->id] = $arm;
		}
		//кэшируем результат
		return $this->arms_chain_cache=$chain;
	}
	
	/**
	 * набор всей техники привязанной к цепочке документов
	 * @return null|\yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getTechsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->techs_chain_cache)) return $this->techs_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) $chain[]=$item->id;

		/*{
			//в них все армы
			foreach ($item->techs as $tech)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$tech->id] = $tech;
		}
		//кэшируем результат*/

		return static::hasMany(\app\models\Techs::className(),['id' => 'techs_id'])
			->viaTable('{{%contracts_in_techs}}', ['contracts_id' => $chain]);
	}

	/**
	 * набор всех вводов интернет привязанных к цепочке документов
	 * @return \app\models\OrgInet[]
	 */
	public function getInetsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->inets_chain_cache)) return $this->inets_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->orgInets as $inet)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$inet->id] = $inet;
		}
		//кэшируем результат
		return $this->inets_chain_cache=$chain;
	}

	/**
	 * набор всех телефонов привязанных к цепочке документов
	 * @return \app\models\OrgPhones[]
	 */
	public function getPhonesChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->phones_chain_cache)) return $this->phones_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->orgPhones as $phone)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$phone->id] = $phone;
		}
		//кэшируем результат
		return $this->phones_chain_cache=$chain;
	}

	/**
	 * набор всех лицензий привязанных к цепочке документов
	 * @return \app\models\LicItems[]
	 */
	public function getLicsChain()
	{
		//пытаемся обратиться к кэшу
		if (!is_null($this->lics_chain_cache)) return $this->lics_chain_cache;

		$chain=[];
		//перебираем все документы
		foreach ($this->successorsChain as $item) {
			//в них все армы
			foreach ($item->licItems as $lic)
				//добавляем по ИД, чтобы исключить задвоения
				$chain[$lic->id] = $lic;
		}
		//кэшируем результат
		return $this->lics_chain_cache=$chain;
	}
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getPartners()
	{
		//if (!is_null($this->partners_cache)) return $this->partners_cache;

		//return $this->partners_cache=Partners::fetchByField('contracts_id',$this->contracts_id)
		return $this->partners_cache=static::hasMany(Partners::className(), ['id' => 'partners_id'])
			->viaTable('{{%partners_in_contracts}}', ['contracts_id' => 'id']);
	}

	public static function fetchNames(){
		$list= static::find()->joinWith('partners')->viaTable('{{%partners_in_contracts}}', ['contracts_id' => 'id'])
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}


	/**
	 * Следующий id
	 * @return integer
	 */
	public static function fetchNextId() {
		return static::find()->max("id")+1;
	}

	public static function fetchArmsHint($ids,$form='') {
			//Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

		if (!is_array($ids))
			$ids=explode(',',$ids);
		$hint='Выберите АРМ';
		if (!count($ids)) return $hint;
		$arms=\app\models\Arms::find()
			->joinWith('contracts')
			->where(['arms_contracts.id'=>$ids])
			//->createCommand()->getRawSql();
				//
			->all();
		if (!count($arms)) return $hint;

		$hintTtip='В подсказке приведены АРМы из привязанных документов'.(strlen($form)?' (кликабельно)':'');
		$hint="<span title=\"$hintTtip\">Подсказка</span>: ";
		foreach ($arms as $arm) {
			$js=strlen($form)?(
				"onclick=\"$('#$form-arms_id,#$form-arms_ids').val({$arm->id}).trigger('change');\""
			):'';
			$ttip=\yii\helpers\Url::to(['/arms/ttip','id'=>$arm->id]);
			$hint.="<span class='href' qtip_ajxhrf='$ttip' $js>{$arm->num}</span> ";
			/* В общем вся эта прелесть убрана, т.к. Yii2 делает encode всем полям Tag включая онклик в котором кавычки ломаются
			я запарился искать как это починить
			 * \yii\helpers\Html::tag(
			'span',
			$arm->num,
			[
				'class'=>'pointer',
				'qtip_ajxhrf'=>\yii\helpers\Url::to(['/arms/ttip','id'=>$arm->id]),
				'onclick'=>new \yii\web\JsExpression("$('#$form-arms_id').select2('data', {id:'{$arm->id}',text:'{$arm->num}'});"),
				'options'=>['encode'=>false],
			]
		);*/
		}
		return $hint;
	}
	
	/**
	 * Код для подстановки НДС
	 * @param $model
	 * @param $total
	 * @param $charge
	 * @return string
	 */
	public static function chargeCalcHtml($model,$total,$charge) {
		return <<<HTML
		<span class="href" onclick="$('#{$model}-{$charge}').val($('#{$model}-{$total}').val()/1.2*0.2)">20%</span>
		/
		<span class="href" onclick="$('#{$model}-{$charge}').val('')">нет</span>
HTML;
	}
}
