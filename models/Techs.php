<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "techs".
 *
 * @property int $id Идентификатор
 * @property string $num Инвентарный номер
 * @property string $inv_num Бухгалтерский инвентарный номер
 * @property int $model_id Модель оборудования
 * @property string $sn Серийный номер
 * @property int $arms_id Рабочее место
 * @property int $places_id Помещение
 * @property int $state_id Состояние
 * @property string $user_id Пользователь
 * @property string $it_staff_id Сотрудник службы ИТ
 * @property string $ip IP адрес
 * @property string $mac MAC адрес
 * @property string $url Ссылка
 * @property string $comment Комментарий
 * @property string $stateName статус
 * @property bool $isVoipPhone Является IP телефоном
 * @property bool $isUps Является UPS
 * @property array $contracts_ids Список документов
 *
 * @property Users $user
 * @property Users $itStaff
 * @property Arms $arm
 * @property Places $place
 * @property TechStates $state
 * @property TechModels $model
 * @property TechTypes $type
 * @property Contracts[] $contracts
 */

class Techs extends \yii\db\ActiveRecord
{

	private static $num_str_pad=4; //количество знаков в цифровой части номера
	private $model_cache=null;
	private $state_cache=null;
	private $type_cache=null;

	public static $title='Оборудование';

	public static $descr='Оргтехника, сетевое оборудование, сервера и вообще все, что является предметом ответственности ИТ службы, имеет материальное представление, но не является АРМом.';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'techs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['model_id'], 'required'],
	        [['model_id', 'arms_id', 'places_id', 'state_id'], 'integer'],
	        [['contracts_ids'], 'each', 'rule'=>['integer']],
	        [['url', 'comment'], 'string'],
	        [['history'], 'safe'],
	        [['num', 'user_id', 'it_staff_id', 'ip'], 'string', 'max' => 16],
	        [['mac'], 'string', 'max' => 17],
            [['inv_num', 'sn'], 'string', 'max' => 128],
	        [['user_id', 'it_staff_id'], 'filter', 'filter' => function ($value) {
        	    //заменяем пустые значения табельных номеров на NULL
		        return strlen($value)?$value:null;
	        }],
	        ['num', function ($attribute, $params, $validator) {
		        if (count(explode('-',$this->$attribute))!==3) {
			        $this->addError($attribute, 'Инвентарный номер должен быть в формате "ФИЛ-ТИП-НОМЕР", где ФИЛ - префикс филиала, ТИП - тип оборудования, НОМЕР - целочисленный номер уникальный в рамках филиала.');
		        }
	        }],
	        ['num', 'filter', 'filter' => function ($value) {
		        // выполняем определенные действия с переменной, возвращаем преобразованную переменную
		        $tokens=explode('-',$value);
		        $num=(int)$tokens[count($tokens)-1];
		        $tokens[count($tokens)-1]=str_pad((string)$num,static::$num_str_pad,'0',STR_PAD_LEFT);
		        return mb_strtoupper(implode('-',$tokens));
	        }],
	        ['num', function ($attribute, $params, $validator) {
		        $same=static::findOne([$attribute=>$this->$attribute]);
		        if (is_object($same)&&($same->id != $this->id)) {
			        $tok =explode('-',$this->$attribute);
			        if (count($tok)) unset($tok[count($tok)-1]);
			        $pref=implode('-',$tok);
			        $next=static::fetchNextNum($pref);
			        $this->addError($attribute, "Инвентарный номер {$this->$attribute} уже занят, следующий свободный номер с префиксом $pref - $next");
		        }
	        }],
	        ['num', 'unique'],
	        [['state_id'], 'exist', 'skipOnError' => true, 'targetClass' => TechStates::className(), 'targetAttribute' => ['state_id' => 'id']],
	        [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['it_staff_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['it_staff_id' => 'id']],
            [['arms_id'], 'exist', 'skipOnError' => true, 'targetClass' => Arms::className(), 'targetAttribute' => ['arms_id' => 'id']],
            [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
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
					'contracts_ids' => 'contracts',
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
            'id' => 'Идентификатор',
            'num' => 'Инвентарный номер',
            'inv_num' => 'Бухг. инв. номер',
            'model_id' => 'Модель оборудования',
            'sn' => 'Серийный номер',
	        'state_id' => 'Состояние',
	        'arms_id' => 'Рабочее место',
            'places_id' => 'Помещение',
	        'user_id' => 'Пользователь',
	        'contracts_ids' => 'Связанные документы',
            'it_staff_id' => 'Сотрудник службы ИТ',
	        'ip' => 'IP адрес',
	        'mac' => 'MAC адрес',
            'url' => 'Ссылки',
	        'comment' => 'Комментарий',
	        'history' => 'Записная книжка',
        ];
    }


	/**
	 * @inheritdoc
	 */
	public function attributeHints()
	{
		return [
			'num' => 'Внутренний инвентарный номер в службе ИТ.',
			'inv_num' => 'Бухгалтерский инвентарный / номенклатурный номер.',
			'model_id' => 'Модель устанавливаемого оборудования. Если нужная модель отустствует в списке, то нужно сначала завести в ее в соотв. категории оборудования',
			'sn' => 'Серийный номер оборудования. Если явно нет, то MAC/IMEI/и т.п. чтобы можно было однозначно идентифицировать оборудование',
			'state_id' => 'Состояние, в котором в данный момент находится оборудование',
			'arms_id' => 'Рабочее место, к которому прикреплено оборудование',
			'user_id' => 'Сотрудник которому установлено',
			'places_id' => 'Помещение, куда установлено',
			'it_staff_id' => 'Сотрудник службы ИТ, который отвечает за обслуживание оборудования',
			'head_id' => 'Руководитель отдела сотрудника которому установлено',
			'contracts_ids' => 'Счета, накладные, фотографии серийных номеров и т.п.',
			'url' => \app\components\UrlListWidget::$hint,
			'comment' => 'Краткое пояснение по этому оборудованию',
			'history' => 'Все важные и не очень заметки и примечания по жизненному циклу этого АРМ',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getState()
	{
		if (!is_null($this->state_cache)) return $this->state_cache;
		return $this->state_cache=$this->hasOne(TechStates::className(), ['id' => 'state_id']);
	}

	public function getStateName()
	{
		if (is_object($this->state)) return $this->state->name;
		return '';
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
	    if ($this->arms_id) return $this->arm->user;
        return $this->hasOne(Users::className(), ['id' => 'user_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getItStaff()
	{
		if ($this->arms_id) return $this->arm->itStaff;
		return $this->hasOne(Users::className(), ['id' => 'it_staff_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getModel()
	{
		if (!is_null($this->model_cache)) return $this->model_cache;
		return $this->model_cache=$this->hasOne(TechModels::className(), ['id' => 'model_id']);
	}

	/**
	 * @return TechTypes
	 */
	public function getType()
	{
		if (!is_null($this->type_cache)) return $this->type_cache;
		return $this->type_cache=$this->model->type;
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getArm()
    {
        return $this->hasOne(Arms::className(), ['id' => 'arms_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		if ($this->arms_id) return $this->arm->place;
		return $this->hasOne(Places::className(), ['id' => 'places_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrigPlace()
	{
		//if ($this->arms_id) return $this->arm->place;
		return $this->hasOne(Places::className(), ['id' => 'places_id'])
			->from(['places_techs'=>Techs::tableName()]);
	}

	public function getIsVoipPhone() {
    	return \app\models\TechTypes::getIsPhone($this->model->type_id);
    }

	public function getIsUps() {
		return \app\models\TechTypes::getIsUps($this->model->type_id);
	}


	/**
	 * Имя для поиска
	 * @return string
	 */
	public function getSname()
	{
		return $this->num.' ('.$this->type->name.' '.$this->model->name.')'.
			(
				!is_null($this->places_id)?
					(' в '.\app\models\Places::fetchFullName($this->places_id))
					:''
			);
	}


	/**
	 * Возвращает первый свободный инвентарный номер с заданным инв. номером
	 * @param $idx текущий инв. номер
	 * @return integer номер следующей позиции
	 */
	public static function fetchNextNum($idx) {
		$last=static::find()->where(['like','num',$idx])->orderBy(['num'=>SORT_DESC])->one();
		if (is_object($last)) {
			$tokens = explode('-', $last->num);
			$subidx = (int)$tokens[count($tokens)-1] + 1;
		} else $subidx=1;
		$num=str_pad((string)$subidx,static::$num_str_pad,'0',STR_PAD_LEFT);
		return $idx.'-'.$num;

	}

	/**
	 * Формирует префикс инвентарного номера оборудования на основании типа и места установки
	 * @param $model_id integer модель оборудования
	 * @param $place_id integer помещение
	 * @param $arm_id integer АРМ
	 * @return string префикс инвентарного номера
	 */
	public static function genInvPrefix($model_id,$place_id,$arm_id)
	{
		$tokens=[];

		if ($arm_id) {
			//если есть АРМ - то место установки там где АРМ
			$arm=\app\models\Arms::findOne($arm_id);
			if (is_object($arm)) $place=$arm->place;
		} elseif ($place_id) {
			//иначе там где место установки
			$place=\app\models\Places::findOne($place_id);
		} else $place=null;
		if (is_object($place)) {
			//если нашли место установки, то ищем там префикс
			$place_token=$place->prefTree;
			//если есть, то добавляем
			if (strlen($place_token)) $tokens[]=$place_token;
		}
		//ищем модель оборудования
		$model=\app\models\TechModels::findOne($model_id);
		if (is_object($model)) {
			//если все ок то берем префикс типа оборудования
			$tech_token=$model->type->prefix;
			//если он не пустой то добавляем
			if (strlen($tech_token)) $tokens[]=$tech_token;
		}

		//или цепочка из префиксов или пусто
		return count($tokens)?implode('-',$tokens):'';
	}

	/**
	 * Возвращает набор документов
	 */
	public function getContracts()
	{
		return static::hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_techs}}', ['techs_id' => 'id']);
	}

	public static function fetchNames(){
		$list= static::find()
			->joinWith('model')
			->joinWith('model.type')
			->joinWith('origPlace')
			->joinWith('arm')
			->joinWith('arm.place')
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}


}
