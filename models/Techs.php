<?php

namespace app\models;

use app\components\UrlListWidget;
use app\helpers\ArrayHelper;
use app\helpers\MacsHelper;
use app\helpers\QueryHelper;
use voskobovich\linker\LinkerBehavior;
use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "techs".
 *
 * @property int $id Идентификатор
 * @property int $domain_id Домен
 * @property string $hostname Имя
 * @property string $num Инвентарный номер
 * @property string $name
 * @property string $sname
 * @property string $inv_num Бухгалтерский инвентарный номер
 * @property int $comp_id Основная ОС рабочего места
 * @property int $model_id Модель оборудования
 * @property string $sn Серийный номер
 * @property string $uid Доп идентификатор
 * @property string $hw Аппаратное обеспечение
 * @property string $mac MAC адрес
 * @property string $specs Тех. спецификация
 * @property string $installed_pos Позиция установки в корзину/шкаф морды устройства
 * @property string $installed_pos_end Позиция установки в корзину/шкаф задницы устройства
 * @property string $comment Комментарий
 * @property string $updated_at Время изменения
 * @property string $stateName Статус
 * @property string $history история
 * @property string $ip IP адрес
 * @property string $formattedMac MAC адрес с двоеточиями
 * @property string $url Ссылка
 * @property string $commentLabel название поля комментарий (для этой модели)
 * @property string $servicesNames имена связанных сервисов (нужно для сортировки внутри arrDataProvider)
 *
 * @property int $arms_id Рабочее место
 * @property int $installed_id Куда вставлено (в другое оборудование)
 * @property int $state_id Состояние
 * @property int $places_id Помещение
 * @property int $user_id Пользователь
 * @property int $responsible_id Ответственный
 * @property int $head_id Руководитель отдела
 * @property int $it_staff_id Сотрудник ИТ
 * @property int $departments_id Подразделение
 * @property int $armTechsCount Количество техники в арм
 
 * @property boolean $isComputer является компьютером (исходя из модели оборудования)
 * @property boolean $isVoipPhone является компьютером (исходя из модели оборудования)
 * @property boolean $isUps является компьютером (исходя из модели оборудования)
 * @property boolean $isMonitor является компьютером (исходя из модели оборудования)
 * @property boolean $archived Списано
 * @property boolean $full_length Вся глубина корзины
 * @property boolean $installed_back Установлено с обратной стороны

 * @property array $contracts_ids Список документов
 * @property array $netIps_ids Список IP
 * @property array $portsList
 * @property array $ddPortsList
 *
 * @property Users $head
 * @property Users $admResponsible
 * @property Users $responsible
 * @property Users $user
 * @property Users $itStaff
 *
 * @property Places $place
 * @property Places $effectivePlace
 * @property Places $origPlace
 * @property Comps $comp
 * @property Comps $hwComp
 * @property Comps[] $comps
 * @property Comps[] $sortedComps
 * @property Comps[] $hwComps
 * @property Comps[] $vmComps
 *
 * @property Ports[] $ports
 * @property Ports[] $linkedPorts
 *
 * @property TechStates $state
 *
 * @property Techs $arm
 * @property Techs $installation
 * @property Techs[] $installedTechs
 * @property Techs[] $armTechs
 * @property Techs[] $voipPhones
 * @property Techs[] $ups
 * @property Techs[] $monitors

 * @property Departments $department
 *
 * @property TechModels $model
 * @property TechTypes $type
 * @property Contracts[] $contracts
 * @property Services[] $services
 * @property Services[] $compsServices
 * @property LicItems[] $licItems
 * @property LicKeys[] $licKeys
 * @property LicGroups[] $licGroups
 * @property MaterialsUsages[] $materialsUsages
 * @property NetIps[] $netIps
 * @property Segments[] $segments
 * @property Acls[] $acls
 *
 * @property int $scans_id Картинка - предпросмотр
 * @property Scans[] $scans
 * @property Scans $preview
 
 * @property HwList $hwList
 
 * @property MaintenanceReqs $maintenanceReqs
 * @property MaintenanceReqs $effectiveMaintenanceReqs
 
 */

class Techs extends ArmsModel
{
	public $renderedInFrontRack=[];	//позиции в передней корзине где уже отрендерилось
	public $renderedInBackRack=[];	//тоже самое в задней корзине
	
	public static $title='Оборудование';
	public static $titles='Оборудование';
	public static $armsTitle='АРМ';
	public static $armsTitles='АРМы';
	
	public static $descr='Оргтехника, сетевое оборудование, сервера и вообще все, что является предметом ответственности ИТ службы, имеет материальное представление, но не является АРМом.';
	
	private static $defaultPrefixFormat=['place','org','type'];	//порядок префиксов для инв. номера
	private static $defaultNumStrPad=[9,6,4];	//количество знаков в цифровой части номера в зависимости от количества токенов префикса
	private static $defaultNumMaxLen=15;	//максимальная длина инвентарного номера (если получится уложиться убирая нули в числовой части)
	
	private static $armInheritance=[		//поля которые наследуются у оборудования подключенного к АРМ
		'places_id','user_id','it_staff_id','head_id','responsible_id','departments_id'
	];
	private static $installInheritance=[	//поля которые наследуются у оборудования установленного в другое
		'places_id'
	];
	//private $state_cache=null;
	private $type_cache=null;
	private $hwList_obj=null;
	private $voipPhones_cache=null;
	private $ups_cache=null;
	private $monitors_cache=null;
	private $hwComp_cache=null;  //тот комп с которого вытаскивать железо (если основная ОС - виртуальная)
	private $hwComps_cache=null; //для методов вытащить только поставленные на железо
	private $vmComps_cache=null; //и виртуальные ОС пригодится кэш (наверно)
	private $compsServices_cache=null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'techs';
    }
    
    public function extraFields()
	{
		return [
			'site','comp','supportTeam','responsible','stateName','model','manufacturer','type' //площадка - помещение верхнего уровня относительно помещения где размещено оборудование
		];
	}
	
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(),[
			'id' => ['Идентификатор'],
			'attach' => ['Связи'],
			'domain_id' => 'Домен',

			'num' => [
				'Инвентарный номер',
				'indexLabel'=>'Инв. номер',
				'hint' => 'Внутренний инвентарный номер в службе ИТ.<br/>'.
					'Заполняется автоматически на основании префиксов помещения, организации и оборудования<br/>'.
					'(нужно выбрать помещение и модель оборудования)<br/>'.
					'Вручную заполнять только если на то есть основания.',
				'indexHint' => 'Внутренний инвентарный номер в службе ИТ.<br/>'.
					QueryHelper::$stringSearchHint,
			],

			'inv_num' => [
				'Бухг. номер',
				'hint' => 'Бухгалтерский инвентарный / номенклатурный номер.',
			],
			'inv_sn' => [
				'label'=>'Бух/SN/Доп.',
				'indexHint' => 'Серийный, бухгалтерский инвентарный/номенклатурный, дополнительный номера через запятую<br>'.
					'Искать можно по всем номерам сразу<br/>'.QueryHelper::$stringSearchHint,
			],
			'sn' => [
				'Серийный номер',
				'hint' => 'Серийный номер оборудования. Если явно нет, то MAC/IMEI/и т.п. чтобы можно было однозначно идентифицировать оборудование',
			],
			
			'model_id' => [
				'Модель оборудования',
				'hint' => 'Модель устанавливаемого оборудования / компьютера.<br>'.
					'Если нужная модель отсутствует в списке, то нужно сначала завести в ее в соотв. категории оборудования',
				'indexHint' => 'Модель системного блока / ноутбука / сервера<br>'.
					'Производитель в таблице не выводится, но при поиске учитывается'.
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'model' => ['alias'=>'model_id'],
			'partners_id' => [
				'Организация',
				'hint' => 'Организация в которую приобретено оборудование / компьютер.<br>'.
					'Если в инвентаризации сопровождаются несколько организаций и нужно разделять оборудование между ними',
				'indexHint' => '{same}'.
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'specs' => [
				'Тех. спецификация',
				'hint' => 'Спецификация оборудования в случае, если модель оборудования не полностью определяет комплектацию каждого отдельного экземпляра',
			],
			'installed_id'=>[
				'Установлено в',
				'hint'=>'Если это устройство установлено в другое, то нужно указать в какое'
			],
			'installed_pos'=>[
				'Места установки',
				'hint'=>'Позиция этого устройства в корзине/шкафу<br>'.
					'Номера посадочных мест, которые занимает устройства<br>'.
					'(лицевая сторона, если позиции для обратной стороны отличаются)<br>'.
					'Одно или несколько через тире, можно несколько позиций через запятую<br>'.
					'<b>Пример 1:</b> 2 - занимает юнит №2<br>'.
					'<b>Пример 2:</b> 1-2 - занимает юниты с 1го по 2й<br>'.
					'<b>Пример 3:</b> 1-2,5-7 - занимает юниты с 1го по 2й и с 5го по 7й'
			],
			'installed_pos_end'=>[
				'C обр. стороны',
				'hint'=>'Если обратная сторона устройства занимает другие позиции,<br>'.
					'то тут нужно указать номера посадочных мест для обратной стороны<br>'.
					'Одно или несколько через тире, можно несколько позиций через запятую<br>'.
					'<b>Пример 1:</b> 2 - занимает юнит №2<br>'.
					'<b>Пример 2:</b> 1-2 - занимает юниты с 1го по 2й<br>'.
					'<b>Пример 3:</b> 1-2,5-7 - занимает юниты с 1го по 2й и с 5го по 7й'
			],
			'installed_back'=>[
				'Установлено с обратной стороны',
				'hint'=>'Устройство установлено с задней стенки. <br>'.
					'Либо в заднюю корзину либо в переднюю, но с обратной стороны'
			],
			'full_length'=>[
				'Полноразмерный модуль',
				'hint'=>'Устройство занимает всю глубину корзины/шкафа.<br>'.
					'В случае двусторонней корзины будет считаться что занимает обе стороны'
			],
			'uid' => [
				Yii::$app->params['techs.uidLabel']??'Доп. маркировка',
				'hint'=>Yii::$app->params['techs.uidHint']??'Какая-либо дополнительная маркировка нанесенная на оборудование',
			],
			'comp_id' => [
				'label'=>'Основная ОС',
				'indexLabel'=>'ОС',
				'hint' => 'Какую ОС отображать в паспорте',
				'indexHint' => 'Поиск ведется <b>только по основной</b> операционной системе.<br>'.
					'Найти АРМ по неосновной ОС можно через Компьютеры->ОС'.
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'comp_hw' => [
				'label'=>'Комплектация',
				'indexHint' => 'Строка оборудования обнаруженного <b>в основной ОС</b><br>'.
					'Чтобы увидеть оборудование в отформатированном виде - наведите мышку на строку'.
					'<br/>'.QueryHelper::$stringSearchHint,
			],
			'is_server' => [
				'label'=>'Сервер',
				'hint' => 'Это оборудование формирует сервер, на котором выполняются какие-то сервисы (будет отмечено другим оформлением, возможно повесить сервисы)',
			],

			'state_id' => [
				'Состояние',
				'hint' => 'Состояние, в котором в данный момент находится оборудование',
				'indexHint' => 'Статус этого АРМ/оборудования<br>'.
					'Можно выбрать несколько из выпадающего списка <br>'.
					'Позиции выбираются кликом'
			],
			'state' => ['Статус'],

			'arms_id' => [
				'Рабочее место',
				'hint' => 'Рабочее место, к которому прикреплено оборудование',
			],

			'places_id' => [
				'Помещение',
				'hint' => 'Помещение, куда установлено',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'place'=>['alias'=>'places_id'],

			'contracts_ids' => [
				'Связанные документы',
				'hint' => 'Счета, накладные, фотографии серийных номеров и т.п.',
			],

			'user_id' => [
				'Пользователь',
				'hint' => 'Сотрудник которому установлено',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'user' => ['alias'=>'user_id'],
			'user_dep' => [
				'Отдел',
				'hint' => 'Отдел в котором числится пользователь',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'user_position' => [
				'Должность',
				'hint' => 'Должность пользователя АРМ/оборудования',
				'indexHint' => '{same}<br/>'.QueryHelper::$stringSearchHint,
			],
			'departments_id' => [
				'label'=>'Подразделение',
				'hint' => 'Закрепить оборудование/АРМ за подразделением.<br><i>'.Departments::$hint.'</i>',
				'indexHint' => 'Подразделение, за которым закреплено.<br><i>'
					.Departments::$hint.
					'</i><hr/>'.QueryHelper::$stringSearchHint,
			],
			'it_staff_id' => [
				'Сотрудник службы ИТ',
				'hint' => 'Сотрудник службы ИТ, который отвечает за обслуживание оборудования/рабочего места',
			],
			'responsible_id' => [
				'label'=>'Адм.Ответственный',
				'hint' => 'Административно ответственный. Указывается, если пользователю выдаются административные полномочия.<br/>'.
					'В таком случае ответственное лицо самостоятельно несет ответственность за любые нелегитимные действия этого АРМ/оборудования.<br />'.
					'Также в этом случае в паспорте появится дополнительный пункт, в котором ответственное лицо должно расписаться.',
			],
			'admResponsible'=>['alias'=>'responsible'],
			'head_id' => [
				'label'=>'Руководитель отдела',
				'hint' => 'Руководитель отдела сотрудника которому установлено',
			],
			
			'ip' => [
				'IP адреса',
				'hint' => 'По одному в строке',
			],
			'mac' => [
				'MAC адреса',
				'hint' => 'MAC адреса сетевых интерфейсов оборудования<br>'.
					'Заполняются по одному в строке',
			],
			'url' => [
				'Ссылки',
				'hint' => UrlListWidget::$hint,
			],
			'comment' => [
				'Примечание',
				'hint' => 'Краткое пояснение по этому оборудованию',
			],
			'history' => [
				'Записная книжка',
				'hint' => 'Все важные и не очень заметки и примечания по жизненному циклу этого оборудования/АРМ',
			],
			'maintenance_reqs_ids'=>[
				MaintenanceReqs::$titles,
				'hint'=>'Какие предъявлены требования по обслуживанию этого оборудования '
					.'<br>По хорошему требования должны предъявлять сервисы, работающие на '
					.'этом оборудовании, но можно задать их и явно.'
			],
			'effectiveMaintenanceReqs'=>[
				'Обслуживание',
				'indexHint'=>'Какие предъявлены требования по обслуживанию.'
					.'<br>Как распространенные с сервисов, так и заданные явно. '
					.'<br>Избыточно предъявленные требования помечаются как "архивные"'
			],
			'services_ids' => [
				'Сервисы',
				'hint' => 'Работу каких сервисов обеспечивает это оборудование',
				'indexHint' => '{same}<br />'.QueryHelper::$stringSearchHint,
			],
		
		]);
	}
	
	
	
	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			['hostname', 'filter', 'filter' => function ($value) {
				return Domains::validateHostname($value,$this);
			}],
			[['domain_id'], 'required', 'when' => function(){return (bool)$this->hostname;}],
			[['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domains::class, 'targetAttribute' => ['domain_id' => 'id']],
			[['domain_id', 'hostname'], 'unique', 'targetAttribute' => ['domain_id', 'hostname']],
            [['model_id'], 'required'],
			[['model_id', 'state_id', 'scans_id', 'departments_id','comp_id','domain_id'], 'integer'],
			[['installed_id', 'arms_id', 'places_id','partners_id'], 'integer'],
			[['user_id', 'responsible_id', 'head_id', 'it_staff_id'], 'integer'],
			[['installed_back','full_length'],'boolean'],

	        [['contracts_ids','lic_items_ids','lic_groups_ids','lic_keys_ids','maintenance_reqs_ids','services_ids'], 'each', 'rule'=>['integer']],

			[['url', 'comment','updated_at','history','hw','specs','external_links'], 'safe'],
			[['inv_num', 'sn','installed_pos','installed_pos_end'], 'string', 'max' => 128],
	
			[['ip','mac'], 'string', 'max' => 768],
			['ip', function ($attribute) {
				NetIps::validateInput($this,$attribute);
			}],
			['ip', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
			['mac', 'filter', 'filter' => function ($value) {
				return MacsHelper::fixList($value);
			}],
			[['num','uid'], 'string', 'max' => 16],
	        ['num', function ($attribute) {
        		$tokens=explode('-',$this->$attribute);
		        if (count($tokens)>4 || !strlen($tokens[0])) {
		        	
			        $this->addError($attribute, 'Инвентарный номер должен быть в формате "ПРЕФ1-[ПРЕФ2-][ПРЕФ3-]НОМЕР", где ПРЕФ1-N - префикс филиала/организации/оборудования, НОМЕР - целочисленный номер уникальный для этого набора префиксов.');
		        }
	        }],
	        ['num', 'filter', 'filter' => ['\app\models\Techs','formatInvNum']],
	        ['num', function ($attribute) {
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
			['sn', 'unique','targetAttribute'=>['sn','model_id'],'message'=>'Оборудование с этим серийным номером уже заведено'],
			['arms_id',function ($attribute){
				$this->validateRecursiveLink($attribute, $params=['getLink'=>'arm']);
			}],
			['installed_id',function ($attribute){
				$this->validateRecursiveLink($attribute, $params=['getLink'=>'installation']);
			}],
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
				'class' => LinkerBehavior::class,
				'relations' => [
					'contracts_ids' => 'contracts',
					'services_ids' => 'services',
					'netIps_ids' => 'netIps',
					'lic_items_ids' => 'licItems',
					'lic_keys_ids' => 'licKeys',
					'lic_groups_ids' => 'licGroups',
					'maintenance_reqs_ids' => 'maintenanceReqs',
				]
			]
		];
	}
	
	/**
	 * Возвращает массив размеров числовой части номера в зависимости от количества токенов
	 */
	public static function invNumStrPads() {
		return Yii::$app->params['techs.invNumStrPads']??static::$defaultNumStrPad;
	}
	
	public static function invNumMaxLen() {
		return Yii::$app->params['techs.invNumMaxLen']??static::$defaultNumMaxLen;
	}
	
	public static function invNumPrefixFormat() {
		return Yii::$app->params['techs.prefixFormat']??static::$defaultPrefixFormat;
	}
	
	/**
	 * Возвращает размер числовой части инв. номера в зависимости от количества токенов
	 * @param $tokens - количество токенов
	 * @return int
	 */
	public static function getNumStrPad($tokens) {
		$pads=static::invNumStrPads();
		return $pads[$tokens]??$pads[count($pads)-1];
	}
	
	public static function formatInvNum($value)
	{
		// выполняем определенные действия с переменной, возвращаем преобразованную переменную
		$tokens = explode('-', $value);
		//
		$num_str_pad = static::getNumStrPad(count($tokens)-1);
		
		$num = (int)$tokens[count($tokens) - 1];
		unset($tokens[count($tokens) - 1]);
		$prefix=implode('-', $tokens);
		$num_str_pad=min($num_str_pad,static::invNumMaxLen()-mb_strlen($prefix));
		$num = str_pad((string)$num, $num_str_pad, '0', STR_PAD_LEFT);
		return mb_strtoupper($prefix . '-' . $num);
	}
	
	
	
	/**
	 * Возвращает первый свободный инвентарный номер с заданным префиксом
	 * @param string $prefix текущий инв. номер
	 * @return integer номер следующей позиции
	 */
	public static function fetchNextNum(string $prefix) {
		//ищем запись с таким префиксом (сортируем по префиксу и выбираем один самый большой)
		$query=static::find()
			->where(['like','num',$prefix.'-%',false]);
		
		
		if (strpos($prefix,'-')===false) //если в переданном префиксе нет "-", то ищем записи в которых
			$query->andWhere('LOCATE("-",num,'.(mb_strlen($prefix)+2).')=0'); //после первого "-" второго уже нет
		//иначе он вместо чел-0000018 найдет чел-тел-0002 и все неправильно посчитает
		
		//$sql=$query->createCommand()->getRawSql();
		/** @var Techs $last */
		$last=$query
			->orderBy(['num'=>SORT_DESC])
			->one();
		
		if (is_object($last)) {
			$tokens = explode('-', $last->num);
			$subIndex = (int)$tokens[count($tokens)-1] + 1;
		} else $subIndex=1;
		//в зависимости от количество токенов в префиксе выбираем длину номерной части
		
		return static::formatInvNum($prefix.'-'.$subIndex);
		
	}
	
	/**
	 * Формирует префикс инвентарного номера оборудования на основании типа и места установки
	 * @param $model_id integer модель оборудования
	 * @param $place_id integer помещение
	 * @param $org_id integer организация
	 * @param $arm_id integer АРМ
	 * @param $installed_id integer Куда установлено
	 * @return string префикс инвентарного номера
	 */
	public static function genInvPrefix(int $model_id, int $place_id, int $org_id, int $arm_id, int $installed_id)
	{
		$tokens=[];
		
		foreach (static::invNumPrefixFormat() as $token) {
			switch ($token) {
				case 'place':
					$place=null;
					if ($installed_id) {
						//если есть АРМ - то место установки там где АРМ
						$arm= Techs::findOne($installed_id);
						if (is_object($arm)) $place=$arm->place;
					} elseif ($arm_id) {
						//если есть АРМ - то место установки там где АРМ
						$arm= Techs::findOne($arm_id);
						if (is_object($arm)) $place=$arm->place;
					} elseif ($place_id) {
						//иначе там где место установки
						$place= Places::findOne($place_id);
					}
					
					if (is_object($place)) {
						//если нашли место установки, то ищем там префикс
						$place_token=$place->prefTree;
						//если есть, то добавляем
						if (strlen($place_token)) $tokens[]=$place_token;
					}
					break;
				case 'org':
					/** @var Partners $org */
					$org= Partners::findOne($org_id);
					if (is_object($org)) {
						//если все ок то берем префикс типа оборудования
						$org_token=$org->prefix;
						//если он не пустой то добавляем
						if (strlen($org_token)) $tokens[]=$org_token;
					}
					break;
				case 'type':
					//ищем модель оборудования
					$model= TechModels::findOne($model_id);
					if (is_object($model)) {
						//если все ок то берем префикс типа оборудования
						$tech_token=$model->type->prefix;
						//если он не пустой то добавляем
						if (strlen($tech_token)) $tokens[]=$tech_token;
					}
					break;
			}
		}
		
		//или цепочка из префиксов или пусто
		return count($tokens)?implode('-',$tokens):'';
	}
	
	
	
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getState()
	{
		//if (!is_null($this->state_cache)) return $this->state_cache;
		return $this->hasOne(TechStates::class, ['id' => 'state_id']);
	}

	public function getStateName()
	{
		if (is_object($this->state)) return $this->state->name;
		return '';
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getComp()
	{
		return $this->hasOne(Comps::class, ['id' => 'comp_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::class, ['id' => 'partners_id']);
	}
	
	/**
	 * Возвращает все ОС привязанные к этому АРМ
	 * @return ActiveQuery
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['arm_id' => 'id'])
			->from(['arms_comps'=>Comps::tableName()])
			->orderBy([
				'arms_comps.ignore_hw'=>SORT_ASC,
				'arms_comps.name'=>SORT_ASC
			]);
	}
	
	
	//возвращает список ОС таким образом, что та, на которую указывает сам АРМ будет первой
	public function getSortedComps()
	{
		$comps=$this->comps;
		if ($comps[0]->id!=$this->comp_id && is_object($this->comp)) {
			foreach ($comps as $idx=>$comp)
				if ($comp->id == $this->comp_id)
					unset($comps[$idx]);
			
			array_unshift($comps,$this->comp);
		}
		return $comps;
	}
	
	public function buildHwAndVms() {
		if (!is_null($this->hwComp_cache)) return;
		$this->hwComps_cache=[];
		$this->vmComps_cache=[];
		$this->hwComp_cache=$this->comp;
		if (count($this->comps)) foreach ($this->comps as $comp) {
			if ($comp->ignore_hw)
				$this->vmComps_cache[]=$comp;
			else {
				if (!is_object($this->hwComp_cache) || ($this->hwComp_cache->ignore_hw)) $this->hwComp_cache=$comp;
				$this->hwComps_cache[]=$comp;
			}
		}
		
	}
	
	
	/**
	 * Возвращает все не виртуальные ОС привязанные к этому АРМ
	 * @return ActiveQuery
	 */
	public function getHwComps()
	{
		$this->buildHwAndVms();
		return $this->hwComps_cache;
	}
	
	
	/**
	 * Возвращает тот комп, с которого снимать железо АРМ
	 * @return ActiveQuery
	 */
	public function getHwComp()
	{
		$this->buildHwAndVms();
		return $this->hwComp_cache;
	}
	
	
	/**
	 * Возвращает все виртуальные ОС привязанные к этому АРМ
	 * @return ActiveQuery
	 */
	public function getVmComps()
	{
		$this->buildHwAndVms();
		return $this->vmComps_cache;
	}
	
	/**
	 * Возвращает все оборудование в виде HwList
	 */
	public function getHwList()
	{
		if (!is_null($this->hwList_obj)) return $this->hwList_obj;
		$this->hwList_obj = new HwList();
		$this->hwList_obj->loadJSON($this->hw);
		if (is_object($this->hwComp))
			$this->hwList_obj->loadFound($this->hwComp->hwList);
		return $this->hwList_obj;
	}
	
	/**
	 * @return Services[]
	 */
	public function getCompsServices()
	{
		if (is_null($this->compsServices_cache)){
			$this->compsServices_cache=[];
			foreach ($this->comps as $comp) {
				foreach ($comp->services as $service)
					$this->compsServices_cache[$service->id]=$service;
			}
		}
		return $this->compsServices_cache;
	}
	
	/**
	 * @return Users
	 */
	public function getResponsible()
	{
		if (is_object($user=Services::responsibleFrom($this->services))) return $user;
		
		return $this->itStaff;
	}
	
	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return Users[]
	 * @noinspection PhpUnused
	 */
	public function getSupportTeam()
	{
		$team=Services::supportTeamFrom($this->services);
		//if (is_object($this->user)) $team[$this->user->id]=$this->user;
		
		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->responsible)) {
			if (isset($team[$responsible->id])) unset($team[$responsible->id]);
		}
		
		return array_values($team);
	}
	
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getScans()
	{
		/** @var Scans $scans */
		$scans=Scans::find()->where(['techs_id' => $this->id ])->all();
		$scans_sorted=[];
		foreach ($scans as $scan) if($scan->id == $this->scans_id) $scans_sorted[]=$scan;
		foreach ($scans as $scan) if($scan->id != $this->scans_id) $scans_sorted[]=$scan;
		return $scans_sorted;
	}
	
	/**
	 * Возвращает набор сканов в договоре
	 */
	public function getPreview()
	{
		//ищем собственную картинку
		if ($this->scans_id && is_object($scan=Scans::find()->where(['id' => $this->scans_id ])->one())) return $scan;

		//ищем картинку от модели
		if (is_object($this->model)) return $this->model->preview;

		//сдаемся
		return null;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getArmTechs()
	{
		return $this->hasMany(Techs::class, ['arms_id' => 'id'])->from(['arms_techs'=>Techs::tableName()]);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getInstalledTechs()
	{
		return $this->hasMany(Techs::class, ['installed_id' => 'id'])->from(['arms_techs'=>Techs::tableName()]);
	}
	
	/**
	 * отфильтровать из переданного массива $models те, которые привязаны к этому АРМ
	 * @param Techs[] $models
	 * @return Techs[]
	 */
	public function filterArmTechs(array $models)
	{
		$filtered=[];
		foreach ($models as $model)
			if ($model->arms_id == $this->id)
				$filtered[]=$model;
		return $filtered;
	}
	

	/**
	 * @return array
	 */
	public function getVoipPhones()
	{
		if (!is_null($this->voipPhones_cache)) return $this->voipPhones_cache;
		$this->voipPhones_cache=[];
		foreach ($this->armTechs as $tech) if ($tech->isVoipPhone) $this->voipPhones_cache[]=$tech;
		return $this->voipPhones_cache;
	}
	
	/**
	 * @return array
	 */
	public function getUps()
	{
		if (!is_null($this->ups_cache)) return $this->ups_cache;
		$this->ups_cache=[];
		foreach ($this->armTechs as $tech) if ($tech->isUps) $this->ups_cache[]=$tech;
		return $this->ups_cache;
	}
	
	/**
	 * @return array
	 */
	public function getMonitors()
	{
		if (!is_null($this->monitors_cache)) return $this->monitors_cache;
		$this->monitors_cache=[];
		foreach ($this->armTechs as $tech) if ($tech->isMonitor) $this->monitors_cache[]=$tech;
		return $this->monitors_cache;
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getItStaff()
	{
		return $this->hasOne(Users::class, ['id' => 'it_staff_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getHead()
	{
		return $this->hasOne(Users::class, ['id' => 'head_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAdmResponsible()
	{
		return $this->hasOne(Users::class, ['id' => 'responsible_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(Users::class, ['id' => 'user_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
	}
	
	/**
	 * @return Places
	 */
	public function getSite()
	{
		return is_object($this->place)?$this->place->top:null;
	}


	public function getManufacturer() {
		return is_object($this->model)?$this->model->manufacturer:null;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getModel()
	{
		return $this->hasOne(TechModels::class, ['id' => 'model_id']);
	}
	
	/**
	 * Возвращает название поля комментарий
	 */
	public function getCommentLabel()
	{
		if (is_object($model=$this->model)) {
			if (is_object($type=$model->type)) {
				if (strlen($type->comment_name))
					return $type->comment_name;
			}
		}
		return $this->getAttributeLabel('comment');
	}

	/**
	 * @return ActiveQuery
	 */
	public function getAttachModel()
	{
		return $this->hasOne(TechModels::class, ['id' => 'model_id'])
			->from(['attached_tech_models'=>TechModels::tableName()]);

	}
	
	
	/**
	 * Возвращает набор документов
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::class, ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_arms}}', ['arms_id' => 'id']);
	}
	
	/**
	 * Возвращает набор документов
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::class, ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_arms}}', ['arms_id' => 'id']);
	}
	
	/**
	 * Возвращает набор документов
	 */
	public function getLicGroups()
	{
		return $this->hasMany(LicGroups::class, ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_arms}}', ['arms_id' => 'id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getMaterialsUsages()
	{
		return $this->hasMany(MaterialsUsages::class, ['techs_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getPorts()
	{
		return $this->hasMany(Ports::class, ['techs_id' => 'id']);
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
	 * @return ActiveQuery
	 */
	public function getArm()
	{
		return $this->hasOne(Techs::class, ['id' => 'arms_id']);
	}
	
	public function getMaintenanceReqs()
	{
		return $this->hasMany(MaintenanceReqs::class, ['id' => 'reqs_id'])
			->viaTable('maintenance_reqs_in_techs', ['techs_id' => 'id']);
	}
	
	public function getEffectiveMaintenanceReqs()
	{
		$reqs=[];
		
		foreach ($this->maintenanceReqs as $maintenanceReq) {
			$reqs[$maintenanceReq->id]=$maintenanceReq;
		}
		
		foreach ($this->services as $service) {
			foreach ($service->maintenanceReqsRecursive as $maintenanceReq) {
				$reqs[$maintenanceReq->id]=$maintenanceReq;
			}
		}

		$reqs=ArrayHelper::findByField($reqs,'spread_techs',1);
		
		return MaintenanceReqs::filterEffective($reqs);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getInstallation()
	{
		return $this->hasOne(Techs::class, ['id' => 'installed_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getDepartment()
	{
		return $this->hasOne(Departments::class, ['id' => 'departments_id']);
	}
	
	
	public function getIsComputer() {
		return $this->model->type->is_computer;
	}
	
	public function getIsVoipPhone() {
		return $this->model->type->is_phone;
	}
	
	public function getIsUps() {
		return $this->model->type->is_ups;
	}
	
	public function getIsMonitor() {
		return $this->model->type->is_display;
	}
	
	public function getArchived()
	{
		return is_object($this->state)?$this->state->archived:false;
	}
	
	public static function formatMacs($raw,$glue="\n") {
		
		$macs=explode("\n",$raw);
		$formatted=[];
		foreach ($macs as $k=>$mac) if ($mac) {
			$rawMac=preg_replace('/[^0-9A-F]/', '', mb_strtoupper($mac));
			$macTokens=[];
			for ($i=0;$i<mb_strlen($rawMac);$i++){
				if (!isset($macTokens[(int)($i/2)])) $macTokens[(int)($i/2)]='';
				$macTokens[(int)($i/2)].=mb_substr($rawMac,$i,1);
			}
			$formatted[]=implode(':',$macTokens);
			//$macs[$k]=
		}
		
		return implode($glue,$formatted);
	}
	
	public function getFormattedMac() {
		
		return static::formatMacs($this->mac);
	}
	
	
	
	/**
	 * Возвращает IP адреса
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])->from(['techs_ip'=>NetIps::tableName()])
			->viaTable('{{%ips_in_techs}}', ['techs_id' => 'id']);
	}
	
	
	public function getSegments() {
		$segments=[];
		foreach ($this->netIps as $ip)
			if (is_object($ip)){
				if (is_object($segment=$ip->segment))
					$segments[$segment->id]=$segment;
			}
		return $segments;
	}
	
	/**
	 * Возвращает набор документов
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['id' => 'contracts_id'])
			->from(['techs_contracts'=>Contracts::tableName()])
			->viaTable('{{%contracts_in_techs}}', ['techs_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id' => 'service_id'])
			->viaTable('{{%techs_in_services}}', ['tech_id' => 'id']);
	}
	
	//нужно только для сортировки моделей внутри ArrayDataProvider
	public function getServicesNames() {
		$names=ArrayHelper::getColumn($this->services,'name',false);
		sort($names);
		return implode('',$names);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['techs_id' => 'id']);
	}
	
	
	/**
	 * Возвращает массив портов (фактически объявленных или потенциально возможных исходя из модели оборудования)
	 */
	public function getPortsList()
	{
		//Порты которые должны быть у этой модели оборудования
		$model_ports=[];

		//Порты которые объявлены в БД конкретно для этого устройства
		$custom_ports=$this->ports;
		if (!is_array($custom_ports)) $custom_ports=[];
		
		//если корректно пришита модель оборудования и у модели есть набор портов
		if (is_object($this->model) && count($this->model->portsList)) {
			//перебираем распарсеные порты
			foreach ($this->model->portsList as $port_name=>$port_comment) {
				//ищем есть ли порт-объект к этому порту
				$port_link=null;
				foreach ($custom_ports as $i=>$custom_port) if ($custom_port->name == $port_name) {
					$port_link=$custom_port;
					unset($custom_ports[$i]); //убираем обработанный порт из пула существующих для этого устройства
				}
				$model_ports[$port_name]=compact('port_name','port_comment','port_link');
			}
		}
		
		//если какие-то порты не ушли через список выше - добавляем их в конец
		foreach ($custom_ports as $port) {
			$model_ports[$port->name]=[
				'port_name'=>$port->name,
				'port_comment'=>$port->comment,
				'port_link'=>$port
			];
		}
		
		return $model_ports;
	}
	
	public function getDdPortsList()
	{
		$out=[];
		foreach ($this->portsList as $name=>$port) {
			$out[]=[
				'id'=>is_object($port['port_link'])?$port['port_link']->id:"create:$name",
				'name'=>$name
			];
		}
		return $out;
	}
	
	/**
	 * Возвращает комментарий порта из шаблона модели
	 * @param string $port
	 * @return mixed|null
	 */
	public function getModelPortComment(string $port)
	{
		if (is_object($this->model))
			return $this->model->getPortComment($port);
		else
			return null;
	}
	
	
	public function getArmTechsCount(){
		return count($this->armTechs);
	}
	
	public function getVoipPhonesCount(){
		return count($this->voipPhones);
	}
	
	public function getUpdatedRenderClass(){
		if (is_object($this->comp)) {
			return $this->comp->updatedRenderClass;
		} else return '';
	}
	
	
	/**
	 * Имя для поиска
	 * @return string
	 */
	public function getSname()
	{
		$tokens=[$this->num];
		if (is_object($this->comp)) $tokens[]=$this->comp->name;
		if (is_object($this->user)) $tokens[]=$this->user->shortName;
		if (!is_object($this->comp)) {
			$tokens[]='('.$this->type->name.' '.$this->model->name.')';
			if (is_object($this->place)) $tokens[]=$this->place->fullName;
		}
		return implode(' / ',$tokens);
	}
	
	/**
	 * Проверяет установлено ли оборудование в модуль $unit
	 * installed_pos может иметь значение 1,2,7-8
	 * @param      $unit
	 * @param bool $front
	 * @return bool
	 */
	public function isInstalledAt($unit, bool $front=true) {
		if (!$this->full_length) {
			if (($front == $this->installed_back)) return false;
		}
		
		//если смотрим на задницу устройства и для нее другие позиции
		if ($front==$this->installed_back && $this->installed_pos_end)
			$intervals=explode(',',$this->installed_pos_end);
		else
			$intervals=explode(',',$this->installed_pos);
		
		foreach ($intervals as $interval) {
			if (strpos($interval,'-')!==false) {
				$limits=explode('-',$interval);
				if ($limits[0]<=$unit and $limits[1]>=$unit) return true;
			} elseif ($unit==$interval) return true;
		}
		return false;
	}
	
	public static function fetchNames(){
		$list= static::find()
			->joinWith(['model.type','place','user','comp'])
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}
	
	public static function fetchArmNames(){
		$list= static::find()
			->joinWith(['model.type','place','user','comp'])
			->where(['tech_types.is_computer'=>true])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			//fix: https://github.com/spo0okie/arms_inventory/issues/15
			//если привязаны к АРМ,
			if (is_object($this->arm)) {
				//то отвязываемся от собственных помещения и пользователя
				//и перепривязываемся к наследуемым
				foreach (static::$armInheritance as $attr) {
					$this->$attr = $this->arm->$attr;
				}
			}
			
			if (is_object($this->installation)) {
				$this->arms_id=null; //отвязываемся от АРМ (нельзя быть в составе АРМ и шкафа одновременно)
				//то отвязываемся от собственных помещения
				//и перепривязываемся к наследуемым
				foreach (static::$installInheritance as $attr) {
					$this->$attr = $this->installation->$attr;
				}
			}
			
			if (!is_null($this->hwList_obj)) {
				$this->hw=$this->hwList->onlySaved()->saveJSON();
			}
			
			$this->mac= MacsHelper::fixList($this->mac);
			
			//если ОС которая была назначена основной удалена или сменила АРМ
			if (!is_object($this->comp) || $this->comp->arm_id != $this->id)
				$this->comp_id = null; //удаляем основную ОС
			
			//если основной ОС нет, но есть привязанные, то выбираем первую из привязанных как основную
			if (is_null($this->comp_id) && is_array($comps=$this->comps) && count($comps)) {
				foreach ($comps as $comp) {
					$this->comp_id = $comp->id;
					break;
				}
			}
			
			/* взаимодействие с NetIPs */
			$this->netIps_ids=NetIps::fetchIpIds($this->ip);
			
			//грузим старые значения записи
			$old=static::findOne($this->id);
			if (!is_null($old)) {
				//находим все IP адреса которые от этой ОС отвалились
				$removed = array_diff($old->netIps_ids, $this->netIps_ids);
				//если есть отвязанные от это ос адреса
				if (count($removed)) foreach ($removed as $id) {
					//если он есть в БД
					if (is_object($ip=NetIps::findOne($id))) $ip->detachTech($this->id);
				}
			}
			return true;
		}
		return false;
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}
		
		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachTech($this->id);
		}
		
		return true;
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		
		//если изменились поля которые наследуются для оборудования входящего в АРМ ($armInheritance)
		$updateArmInheritance=false;
		foreach (array_keys($changedAttributes) as $attr) {
			if (array_search($attr,static::$armInheritance)!==false) {
				$updateArmInheritance=true;
			}
		}
		
		//если изменились поля которые наследуются для оборудования установленного в другое оборудование ($installInheritance)
		$updateInstallInheritance=false;
		foreach (array_keys($changedAttributes) as $attr) {
			if (array_search($attr,static::$installInheritance)!==false) {
				$updateInstallInheritance=true;
			}
		}
		
		//если поля изменились - перебираем все входящие в АРМ экз. оборудования
		if ($updateArmInheritance) {
			foreach ($this->armTechs as $tech) {
				//и проставляем им наследуемые поля
				foreach (static::$armInheritance as $attr) {
					$tech->$attr = $this->$attr;
				}
				$tech->save();
			}
		}

		//если поля изменились - перебираем все входящие в АРМ экз. оборудования
		if ($updateInstallInheritance) {
			foreach ($this->installedTechs as $tech) {
				//и проставляем им наследуемые поля
				foreach (static::$installInheritance as $attr) {
					$tech->$attr = $this->$attr;
				}
				$tech->save();
			}
		}
	}
	
	public function getName() {
		return $this->hostname?$this->hostname:$this->num;
	}
	
	//имя в списке оборудования и ОС сервиса (для сортировки)
	public function getInServicesName() {return mb_strtolower($this->model->nameWithVendor);}
	
	public function reverseLinks()
	{
		return [
			$this->comps,
			$this->armTechs,
			$this->installedTechs,
			$this->materialsUsages,
			$this->contracts,
			$this->licItems,
			$this->licGroups,
			$this->licKeys,
			$this->ports
		];
	}
	
}
