<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\helpers\MacsHelper;
use app\helpers\QueryHelper;
use app\generation\context\GenerationContext;
use app\models\base\ArmsModel;
use app\models\traits\AclsFieldTrait;
use app\models\traits\CompsModelCalcFieldsTrait;
use app\models\traits\UnsatisfiedMaintenanceFieldTrait;
use OpenApi\Attributes\Schema;
use Throwable;
use voskobovich\linker\updaters\ManyToManySmartUpdater;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Exception;
use yii\db\Query;
use yii\db\StaleObjectException;

/**
 * This is the model class for table "comps".
 *
 * @property int $id Идентификатор
 * @property int $domain_id Домен
 * @property int $sandbox_id Окружение
 * @property string $name Имя
 * @property string $os ОС
 * @property string $fqdn FQDN
 * @property string $raw_hw Отпечаток железа
 * @property string $raw_soft Отпечаток софта
 * @property string $raw_version Версия скрипта отправившего данные
 * @property string $exclude_hw Скрытое из паспорта железо
 * @property string $ignore_hw Игнорировать железо на машине
 * @property string $mac MAC адреса через перенос строки
 * @property string $formattedMac MAC адреса (приведенные к приличному виду) через перенос строки
 * @property string $ip IP адреса через перенос строки
 * @property string $ip_ignore Игнорировать IP адреса
 * @property int $arm_id Рабочее место
 * @property int $user_id Пользователь
 * @property int[] $admins_ids Администраторы
 * @property int $platform_id ID Облачного сервиса
 * @property Services $platform облачный сервис
 * @property string   $comment Комментарий
 * @property string   $updated_at Время обновления
 * @property boolean  $isIgnored Софт находится в списке игнорируемого ПО
 * @property array    $softHits_ids Массив ID ПО, которое установлено на компе
 * @property array    $soft_ids Массив ID ПО, которое внесено в паспорт
 * @property array    $netIps_ids Массив ID IP
 * @property array    $comps Массив объектов ПО, которое установлено на компе
 * @property array    $lic_groups_ids Массив ID привязанных типов лицензий
 * @property array    $lic_items_ids Массив ID привязанных закупок лицензий
 * @property array    $lic_keys_ids Массив ID привязанных лицензионных ключей
 * @property boolean  $isWindows ОС относится к семейству Windows
 * @property boolean  $isLinux ОС относится к семейству Linux
 * @property boolean  $archived
 * @property Techs    $arm
 * @property Techs    $linkedArms
 * @property Comps[]  $dupes
 * @property Users    $user
 * @property Users		$responsible ответственный за ОС на основании сервисов на ней
 * @property Users      $servicesResponsible ответственный за ОС на основании сервисов на ней без учета отв. за инфраструктуру
 * @property Users[]    $supportTeam
 * @property Users[]    $servicesSupportTeam
 * @property Users[]    $admins
 * @property Domains                $domain
 * @property string                 $updatedRenderClass
 * @property string         $updatedText
 * @property string         $domainName
 * @property string         $currentIp
 * @property string[]       $ips
 * @property string[]       $ignoredIps
 * @property string[]       $filteredIps
 * @property LoginJournal[] $lastThreeLogins
 * @property LoginJournal[] $logins
 * @property NetIps[]       $netIps
 * @property Segments[]     $segments
 * @property HwList         $hwList
 * @property SwList         $swList
 * @property Services[]     $services
 * @property Places         $place
 * @property Acls[]         $acls
 * @property Aces[]         $aces
 * @property LicGroups[]    $licGroups
 * @property LicItems[]     $licItems
 * @property LicKeys[]      $licKeys
 * @property Soft[]         $soft
 * @property MaintenanceReqs[] $maintenanceReqs
 * @property MaintenanceJobs[] $maintenanceJobs
 * @property MaintenanceReqs[] $effectiveMaintenanceReqs
 * @property MaintenanceReqs[] $unsatisfiedMaintenanceReqs
 * @property int $unsatisfiedMaintenanceReqsCount
 * @property Sandboxes $sandbox
 * @property CompsRescanQueue $softRescans
 */
class Comps extends ArmsModel
{
	use CompsModelCalcFieldsTrait,AclsFieldTrait,UnsatisfiedMaintenanceFieldTrait;

	public static $title='Операционная система';
	public static $titles='Операционные системы';

	public static function modelDescription(): string
	{
		return 'Экземпляры операционных систем — на физическом оборудовании и виртуальные. '
			.'Хранят сетевые параметры (MAC/IP/домен), установленное ПО '
			.'и связи с оборудованием, пользователями и сервисами.';
	}
    private $hwList_obj=null;
    private $swList_obj=null;

	/** @var bool форсировать синхронный рескан ПО при сохранении
	 * (выставляют консольные comps/rescan и comps/resave) */
	public $forceRescan=false;
	/** @var bool рескан ПО требуется (изменился отпечаток/паспорт), вычисляется в beforeSave */
	private $rescanNeeded=false;
	/** @var bool рескан ПО реально выполнен в этом цикле сохранения */
	private $rescanPerformed=false;
    private $ip_cache=null;
	private $ip_ignore_cache=null;
	private $ip_filtered_cache=null;
	private $servicePartialWeightCache=[];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comps';
    }

    public function extraFields()
	{
		return [
			'arm',
			'backupReqs',
			'backupReqsCount',
			'domain',
			'fqdn',
			'place',
			'responsible',
			'renderFqdn',
			'sandbox',
			'services',
			'servicesCount',
			'servicesNames',
			'servicesResponsible',
			'servicesSupportTeam',
			'site',
			'supportTeam',
			'unsatisfiedBackupReqsCount'
		];
	}

	public $linksSchema=[
		'arm_id' =>				Techs::class,
		'domain_id' =>			Domains::class,
		'user_id' =>			Users::class,
		'sandbox_id' =>			Sandboxes::class,
		'platform_id' =>		[Services::class,'provide_comps_ids'],

		'linked_arms_ids'=>		[Techs::class,'comp_id','deletable'=>true],
		'services_ids'=>		[Services::class,'comps_ids'],
		'admins_ids'=>			[Users::class,'admin_comps_ids'],
		'aces_ids'=>			[Aces::class,'comps_ids'],
		'acls_ids'=>			[Acls::class,'comp_ids'],
		'lic_groups_ids' =>		[LicGroups::class,'comp_ids'],
		'lic_items_ids' =>		[LicItems::class,'comp_ids'],
		'lic_keys_ids' =>		[LicKeys::class,'comp_ids'],
		'netIps_ids' => 		[NetIps::class,'comps_ids','deletable'=>true],
		'softRescan_ids' => 	[CompsRescanQueue::class,'comps_id'],

		'soft_ids' => 			[Soft::class,'comps_ids','loader'=>'soft',
			'updater' => ['class' => ManyToManySmartUpdater::class],
		],
		'softHits_ids' => 		[Soft::class,'hits_ids','deletable'=>true,
			'updater' => ['class' => ManyToManySmartUpdater::class,],
		],

		'maintenance_reqs_ids'=>[MaintenanceReqs::class,'comps_ids'],
		'maintenance_jobs_ids'=>[MaintenanceJobs::class,'comps_ids'],
	];

    /**
     * @inheritdoc
	 */
    public function rules()
    {
        return [
			//тут принципиален порядок правил валидации, т.к. validateHostname должен формировать domain_id из name
			//поэтому порядок правил именно такой: name=>required, validateHostname, domain_id=>required
            [['name', 'os'], 'required'],
			['name', 'filter', 'filter' => function ($value) {return Domains::validateHostname($value,$this);}],
            [['domain_id'], 'required'],
			[['sandbox_id'],'default','value'=>null],
            [['domain_id', 'arm_id', 'ignore_hw', 'user_id','archived','sandbox_id'], 'integer'],
            [['soft_ids','netIps_ids','services_ids','maintenance_reqs_ids','maintenance_jobs_ids','admins_ids'], 'each', 'rule'=>['integer']],
            [['raw_hw', 'raw_soft','exclude_hw','raw_version'], 'string'],
            [['updated_at', 'comment','external_links'], 'safe'],
			[['raw_version'], 'string', 'max' => 32],
            [['name','os'], 'string', 'max' => 128],
			[['ip', 'mac'], 'string', 'max' => 768],
			[['ip_ignore'], 'string', 'max' => 512],

			['ip', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],

			['mac', 'filter', 'filter' => function ($value) {
				return MacsHelper::fixList($value);
			}],

			[
				['domain_id', 'name', 'sandbox_id'],
				'unique',
				'targetAttribute' => ['domain_id', 'name', 'sandbox_id'],
				'skipOnEmpty'=>false,
				'message' => 'В этом домене этого окружения/песочницы уже есть такой hostname'
			],
			[['arm_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::class, 'targetAttribute' => ['arm_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['user_id' => 'id']],
            [['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domains::class, 'targetAttribute' => ['domain_id' => 'id']],
			[['arm_id','platform_id'], function () {
				if ($this->arm_id && $this->platform_id) {
					$this->addError('arm_id', 'ОС не может работать на оборудовании и предоставляться услугой одновременно');
					$this->addError('platform_id', 'ОС не может работать на оборудовании и предоставляться услугой одновременно');
				} else {
					$this->clearErrors('arm_id');
					$this->clearErrors('platform_id');
				}
			}, 'skipOnEmpty'=> false],
			[
				['arm_id'], 'required', 'when' => function($model){
        			return count($model->soft) && $model->getOldAttribute('arm_id');
				},
				'message' => 'В паспорте АРМ есть оборудование привязанное к этой ОС. Нельзя отвязать ее от АРМ'
			],
        ];
    }

	public function afterGenerate(GenerationContext $context, array $options = []): void
	{
		parent::afterGenerate($context, $options);

		if ($this->arm_id && $this->platform_id) {
			$this->platform_id = null;
		}

		// Согласуем hostname с реально созданным доменом: HostnameType не знает,
		// какой Domain был сгенерирован через linksSchema, и может выдать FQDN
		// с «фиктивным» суффиксом (например, `.domain.local`), которого нет в БД.
		// В таком случае validateHostname не сумеет срезать суффикс и сохранит
		// name сырым — поиск `comps/item-by-name` потом возвращает 404.
		if (
			is_string($this->name) && $this->name !== ''
			&& is_object($this->domain) && !empty($this->domain->fqdn)
		) {
			$dotPos = mb_strpos($this->name, '.');
			if ($dotPos !== false) {
				$netbios = mb_substr($this->name, 0, $dotPos);
				$this->name = $netbios . '.' . $this->domain->fqdn;
			}
		}
	}

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return ArrayHelper::recursiveOverride(parent::attributeData(),[
			//read-only вычисляемые ссылки (категория C): только вывод
			'place' => ['ref'=>\app\models\Places::class],
			'site' => ['ref'=>\app\models\Places::class],
			'responsible' => [
				'Ответственный',
				'hint' => 'Кто отвечает за эту ОС: закреплённый пользователь, иначе ответственный '
					.'сервисов на ней, иначе ответственный сервиса управления АРМ',
				'ref'=>\app\models\Users::class,
			],
			'servicesResponsible' => ['ref'=>\app\models\Users::class],
			'supportTeam' => [
				'Поддержка',
				'hint' => 'Команда поддержки: ответственные и поддержка сервисов этой ОС '
					.'и сервиса управления АРМ',
				'ref'=>\app\models\Users::class, 'refMulti'=>true,
			],
			'servicesSupportTeam' => ['ref'=>\app\models\Users::class, 'refMulti'=>true],
			'backupReqs' => ['ref'=>\app\models\MaintenanceReqs::class, 'refMulti'=>true],
			'effectiveMaintenanceReqs' => [
				'ref'=>\app\models\MaintenanceReqs::class, 'refMulti'=>true,
				'join'=>['maintenanceReqs','services.maintenanceReqs','maintenanceJobs'],
			],
			'admins_ids' => [
				'Предоставлены полномочия администратора',
				'viewLabel' => 'Полномочия администратора',
				'hint' => 'Если административные привилегии на этой ОС/ВМ выданы рядовым пользователям,<br>'
					.'то необходимо перечислить их здесь. (Состав ИТ отдела перечислять не нужно)',
				'placeholder' => 'Только у ИТ отдела',
				'join'=> ['admins'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'archived' => [
				'Архивирован',
				'hint' => 'Если эта ОС уже не используется, но на нее есть ссылки из других объектов <br />'.
					'(например есть заархивированный сервис, который был развернут на этой ОС),<br />'.
					'то можно не удалять ее, а заархивировать, чтобы не разрушать взаимосвязи объектов<br />'.
					'ОС останется в БД для истории, но исчезнет из списков и меню выбора (показать можно переключателем «Архивные»)',
				'typeClass'=>\app\types\BooleanType::class,
			],
			'arm_id' => [
				'АРМ',
				'hint' => 'ПК/сервер или облачная платформа, на которой работает эта ОС.<br>'
					.'Если к АРМ прикреплен пользователь, искать АРМ можно и по имени пользователя',
				'indexHint' => '{same}',
				'absorb' => 'ifEmpty',
				'placeholder' => 'Выберите АРМ/сервер',
				'join' => ['arm','platform'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'arm_state'=>[
				'Статус АРМ',
				'indexHint'=>'Статус АРМ на котором работает эта ОС',
				'typeClass'=>\app\types\StringType::class,
				'join'=>['arm.state.marker'],
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Заполняется руками; выводится в шапке ОС и в тултипе '
					.'при наведении на ОС в карте помещений',
				'absorb' => 'ifEmpty','type'=>'text','typeClass'=>\app\types\TextType::class,
			],
			'domain_id' => [
				'Домен',
				'hint' => 'Домен операционной системы.<br>'
					.'Заполняется скриптом; менять руками — только при переводе машины '
					.'в другой домен, иначе появятся дубли',
				'absorb' => 'ifEmpty','typeClass'=>\app\types\LinkType::class,
			],
			'exclude_hw' => [
				'Скрытое из паспорта железо',
				'hint' => 'Элементы отпечатка железа, скрытые из паспорта АРМ',
				'absorb' => 'ifEmpty','typeClass'=>\app\types\HwListType::class,
			],
			'netIps_ids' => [
				'IP адреса',
				'hint' => 'Распознанные IP адреса этой ОС (ссылки на объекты IP).<br>'
					.'Технические адреса можно скрыть значком «глаз» — они перейдут '
					.'в игнорируемые и перестанут учитываться.',
			],
			'soft_ids' => [
				'Распознанное ПО',
				'hint' => 'Программные продукты, распознанные в отпечатке софта этой ОС',
			],
			'ignore_hw' => [
				'Виртуальная машина',
				'indexLabel' => 'VM',
				'hint' => 'Является виртуальной машиной.<br>'
					.'Обычно проставляется скриптом PowerCLI; вручную — если синхронизации с гипервизором нет',
				'absorb' => 'ifEmpty',
				'typeClass'=>\app\types\BooleanType::class,
			],
			'ip' => [
				'IP Адреса',
				//hint (а не indexHint), чтобы смысловая часть была видна и в форме;
				//формат заполнения подскажет IpsType (inputHint)
				'hint' => 'IP адреса сетевых интерфейсов настроенных в ОС',
				'typeClass'=>\app\types\IpsType::class,
				'join'=>['netIps.network'],
			],
			'ip_ignore' => [
				'Скрытые (игнорируемые) адреса',
				'hint' => 'Технические адреса ОС (виртуальные интерфейсы, туннели, '
					.'служебные), скрытые «глазом» из карты адресов. Игнорируемые '
					.'адреса не попадают в реестр IP, сегменты и поиск оборудования; '
					.'вернуть адрес можно тем же значком, раскрыв скрытые '
					.'переключателем «Архивные».',
				'absorb' => 'ifEmpty','typeClass'=>\app\types\IpsType::class,
			],
			'lastThreeLogins' => [
				'Входы',
				'viewLabel'=>'Журнал входов',
				'hint'=>'Журнал входов пользователей на компьютеры, заполняемый скриптами инвентаризации.<br>'
					.'Показываются последние входы на этот скомпьютер не более 3 разных пользователей',
			],
			'lics' => [
				'Лицензии',
				'hint' => 'Все привязанные лицензии:<br>Типы лицензий, закупки, ключи',
				'indexHint' => '{same}',
				'typeClass'=>\app\types\LinkType::class,
				'join'=>['licItems','licGroups','licKeys'],
			],
			'mac' => [
				'MAC Адрес(а)',
				//формат заполнения/поиска подскажет MacsType (inputHint/searchHint)
				'hint' => 'MAC адреса сетевых интерфейсов настроенных в ОС.<br>'
					.'Иконка поиска рядом с адресом ищет его среди оборудования, '
					.'среди ОС и на портах коммутаторов',
				'indexHint' => '{same}',
				'typeClass'=>\app\types\MacsType::class,
			],
			'maintenance_jobs_ids' => [
				MaintenanceJobs::$titles,
				'hint' => 'Какие операции регламентного обслуживания проводятся над этой ОС/ВМ',
				'indexHint' => '{same}',
				'placeholder' => 'Отсутствует',
				'typeClass'=>\app\types\LinkType::class,
				'join'=>['maintenanceJobs'],
			],
			'maintenanceReqs' => ['alias' => 'maintenance_reqs_ids'],
			'maintenance_reqs_ids' => [
				MaintenanceReqs::$titles,
				'hint' => 'Какие предъявлены требования по обслуживанию ОС/ВМ.<br>'
					.'По-хорошему требования должны предъявлять сервисы,<br>'
					.'работающие на ОС/ВМ, но можно задать их и явно',
				'indexHint' => '{same}',
				'placeholder' => 'Получать из сервисов',
				'typeClass'=>\app\types\LinkType::class,
			],
			'name' => [
				'Имя компьютера',
				'hint' => 'Сетевое имя компьютера, настроенное в ОС. '
					.'FQDN (домен+имя) должен быть уникальным; клоны с одинаковым FQDN '
					.'изолируются в <a href="#doc-anchor:sandbox">песочницах</a>.<br>'
					.'Заполняется скриптом; менять руками — только при переименовании машины, '
					.'иначе появятся дубли',
				'indexHint' => 'Сетевое имя компьютера настроенное в ОС.<br>'
					.'Домен не выводится, но при поиске можно указывать.<br>'
					.'Цвет ячейки отображает давность последних данных от скрипта инвентаризации '
					.'(шкала от синего к красному — от свежего к устаревшему):<table>'
					.'<tr><td class="arm_hostname hour_fresh">до часа - синий</td></tr>'
					.'<tr><td class="arm_hostname day_fresh">до суток - голубой</td></tr>'
					.'<tr><td class="arm_hostname week_fresh">до недели - зелёный</td></tr>'
					.'<tr><td class="arm_hostname month_fresh">до месяца - жёлтый</td></tr>'
					.'<tr><td class="arm_hostname over_month_fresh">свыше - красный</td></tr></table>'
					.'Вводимый текст ищется в строке формата DOMAIN\\computer',
				'typeClass'=>\app\types\HostnameType::class,
			],
			'os' => [
				'Наименование и версия операционной системы',
				'hint' => 'Обязательное поле; заполняется скриптом инвентаризации',
				'indexHint' => 'В ячейке выводится только название ОС (она тоже софт).<br>'
					.'Поиск же ведется по ПОЛНОМУ списку софта и по отпечатку железа (в сыром виде): '
					.'найденные записи отфильтруются, но сам найденный софт в ячейке не показывается '
					.'(выводить сотни строк в таблицу нельзя).<br>'
					.'Можно искать по названию/версии программы, серийному номеру платы и т.п.',
				'typeClass'=>\app\types\StringType::class,
			],
			'places_id' => [
				'Помещение',
				'indexHint' => 'Помещение, в котором размещено оборудование',
				'absorb' => 'ifEmpty',
				'typeClass'=>\app\types\LinkType::class,
				'join'=>['arm.place'],
			],
			'platform_id' => [
				'Предоставляется услугой',
				'viewLabel' => 'Платформа',
				'hint' => 'Если эта ОС/ВМ запущена на облачной платформе виртуализации/датацентре и указать АРМ невозможно,<br>'
					.'то можно указать какой услугой предоставляются вычислительные мощности для нее.',
				'placeholder' => function () {
					return 'Работает на нашем оборудовании '.(is_object($this->arm)?(' '.$this->arm->num):'');
				}
			],
			'raw_hw' => [
				'Hardware',
				'hint' => 'Отпечаток железа, обнаруженного внутри ОС (JSON); заполняется скриптом',
				'indexHint' => 'Строка оборудования обнаруженного Операционной Системой<br>'
					.'Чтобы увидеть оборудование в отформатированном виде - наведите мышку на строку',
				'typeClass'=>\app\types\HwListType::class,
			],
			'raw_soft' => [
				'Отпечаток софта',
				'hint' => 'Софт, обнаруженный внутри ОС (JSON).<br>'
					.'Собирается только скриптами Windows-инвентаризации: PowerCLI внутрь ВМ '
					.'не заглядывает, на Linux сбор софта не реализован',
				'typeClass'=>\app\types\SwListType::class,
			],
			'raw_version' => [
				'Скрипт',
				'hint' => 'Версия скрипта инвентаризации, приславшего последние данные по этой ОС',
				'indexHint' => 'Скрипт, который внес последние данные по этой ОС',
				'typeClass'=>\app\types\StringType::class,
			],
			'sandbox_id' => [
				'placeholder' => 'ОС не изолирована в песочнице',
				'hint' => 'Изолированное окружение в которое помещена ОС.<br/>'
					.'Позволяет вести учет клонов/копий ВМ, восстановленных из архива, и т.п.',
				'typeClass'=>\app\types\LinkType::class,
			],
			'services_ids' => [
				'Сервисы',
				'hint' => 'Какие сервисы развернуты на этой ОС',
				'indexHint' => '{same}',
				'placeholder' => 'Нет сервисов',
				'typeClass'=>\app\types\LinkType::class,
				'join'=>['services'],
			],
			'softRescans' => [
				'Ожидается сканирование ПО',
				'typeClass'=>\app\types\LinkType::class,
			],
			'updated_at' => [
				'Время обновления',
				//searchHint типа (DatetimeType) сборщик тултипа добавит сам — руками не дописывать
				'indexHint' => 'Когда в последний раз эта запись обновлялась<br/>'
					.'(либо когда в последний раз эта ОС сообщала о себе в инвентаризацию,<br/>'
					.'либо когда в последний раз вручную правили запись)',
				'typeClass'=>\app\types\DatetimeType::class,
			],
			'user_id' => [
				'Пользователь',
				'hint' => 'Имеет смысл только для серверов и ВМ в случае, '
					.'<br>если пользователь ОС отличается от пользователя АРМ',
				'absorb' => 'ifEmpty',
				'placeholder' => function () {
					if (is_object($this->arm) && is_object($this->arm->user)) {
						return ($this->arm->user->shortName.' (пользователь АРМ)');
					}
					return 'Использовать пользователя АРМ';
				},
				'typeClass'=>\app\types\LinkType::class,
				'join'=>['arm.user'],
			],
			'vCpuCores' => [
				'vCPU',
				'indexHint' => 'Количество CPU ядер VM',
				'typeClass'=>\app\types\IntegerType::class,
			],
			'vHddGb' => [
				'vHDD',
				'indexHint' => 'Объем дискового пространства (GB)',
				'typeClass'=>\app\types\IntegerType::class,
			],
			'vRamGb' => [
				'vRAM',
				'indexHint' => 'Оперативная память VM (GB)',
				'typeClass'=>\app\types\IntegerType::class,
			],
			'vm_uuid' => [
				'VMWare UUID',
				'indexHint' => 'UUID виртуальной машины в VMWare',//QueryHelper::$stringSearchHint,
				//Поисковые запросы по этому полю не поддерживаются, т.к. оно не выделено в отдельный столбец,
				//а является частью поля 'external_links' компа.
				'typeClass'=>\app\types\StringType::class,
			],
		]);
    }



	/**
	 * @return ActiveQuery
	 */
	public function getArm()
	{
		return $this->hasOne(Techs::class, ['id' => 'arm_id']);
	}

	/**
	 * Оборудование с теми же MAC-адресами — кандидаты в АРМ этой ОС
	 * (issue #218): ОС без привязанного АРМ, но с MAC, скорее всего стоит
	 * на железе, у которого этот адрес записан. Карточка предлагает
	 * привязку, решение остаётся за человеком.
	 *
	 * Не getter: это не атрибут и не связь модели, а разовый поиск для
	 * карточки. Считается только у ОС без АРМ (иначе пустой массив, без
	 * запроса), диапазоны адресов не участвуют — сопоставляем конкретные.
	 *
	 * @param int $limit сколько кандидатов показывать
	 * @return Techs[]
	 */
	public function macArmCandidates(int $limit=5): array
	{
		if ($this->arm_id || !strlen((string)$this->mac)) return [];

		$condition=['or'];
		foreach (explode("\n",$this->mac) as $line) {
			$mac=preg_replace('/[^0-9a-f]/', '', mb_strtolower($line));
			if (strlen($mac)!==12 || hexdec($mac)===0) continue;
			$condition[]=['like','techs.mac',$mac];
		}
		if (count($condition)<2) return [];

		return Techs::find()->where($condition)->limit($limit)->all();
	}

	/**
	 * @return ActiveQuery
	 */
	public function getLinkedArms()
	{
		return $this->hasMany(Techs::class, ['comp_id' => 'id']);
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
	public function getDomain()
	{
		return $this->hasOne(Domains::class, ['id' => 'domain_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getSandbox()
	{
		return $this->hasOne(Sandboxes::class, ['id' => 'sandbox_id']);
	}

	/**
	 * Подозрения на записи-двойники этой ОС — живые записи с тем же именем
	 * в том же окружении ({@see dupeIds()} — то же правило для всего списка).
	 * Архивная ОС дублей не имеет: архив хранится ради истории и в поиске
	 * двойников не участвует ни одной из сторон.
	 * @return ActiveQuery
	 */
	public function getDupes()
	{
		$query = $this->hasmany(Comps::class, ['name' => 'name'])
			->where(['not',['id'=>$this->id]])
			->andWhere(['sandbox_id'=>$this->sandbox_id])
			->andWhere(static::notArchivedCondition());

		if ($this->archived) $query->andWhere('0=1');

		return $query;
	}

	/**
	 * Условие «запись не в архиве» для поиска дублей.
	 * IFNULL: comps.archived nullable, а `NOT (archived=1)` на NULL даёт NULL
	 * и молча выбросил бы неархивную запись из выборки.
	 * @return array
	 */
	protected static function notArchivedCondition(): array
	{
		return ['not',['IFNULL(archived,0)'=>1]];
	}

	/**
	 * Id всех ОС, у которых имя не уникально внутри своего окружения —
	 * подозрения на записи-двойники (список /comps/dupes).
	 *
	 * Дубли ищутся в пределах одного окружения (sandbox_id), а не по всей таблице:
	 * клон продуктива в песочнице намеренно носит то же имя (уникальный ключ
	 * domain_id+name+sandbox_id, отображаемое имя различается суффиксом песочницы)
	 * и дублем не является — в этом смысл изоляции песочниц.
	 * Архивные записи не учитываются вовсе: они не попадают в список и не делают
	 * дублем живого тёзку.
	 * Та же логика, что и у {@see getDupes()} для отдельной ОС.
	 *
	 * @return int[]
	 */
	public static function dupeIds(): array
	{
		$groups = (new Query())
			->select(['GROUP_CONCAT(id) ids','name','sandbox_id','COUNT(*) c'])
			->from(static::tableName())
			->where(static::notArchivedCondition())
			->groupBy(['name','sandbox_id'])	//NULL-песочницы (продуктив) MySQL сгруппирует в одну группу
			->having('c > 1')
			->all();

		$ids=[];
		foreach ($groups as $item) foreach (explode(',',$item['ids']) as $id) $ids[]=(int)$id;
		return $ids;
	}



	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getSoft()
	{
		return $this->hasMany(Soft::class, ['id' => 'soft_id'])
			->viaTable('{{%soft_in_comps}}', ['comp_id' => 'id']);
	}

	/**
	 * Возвращает обнаруженное на компе ПО
	 */
	public function getSoftHits()
	{
		return $this->hasMany(Soft::class, ['id' => 'soft_id'])
			->from(['installed_soft'=>Soft::tableName()])
			->viaTable('{{%soft_hits}}', ['comp_id' => 'id']);
	}

	/**
	 * Возвращает работающие на компе сервисы
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id' => 'services_id'])
			->viaTable('{{%comps_in_services}}', ['comps_id' => 'id']);
	}

	/**
	 * Возвращает работающие на компе сервисы
	 */
	public function getPlatform()
	{
		return $this->hasOne(Services::class, ['id' => 'platform_id'])
			->from(['platforms'=>Services::tableName()]);
	}

	/**
	 * Возвращает список админов
	 */
	public function getAdmins()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->viaTable('{{%admins_in_comps}}', ['comps_id' => 'id']);
	}

	//нужно только для сортировки моделей внутри ArrayDataProvider
	public function getServicesNames() {
		$names=ArrayHelper::getColumn($this->services,'name',false);
		sort($names);
		return implode('',$names);
	}

	/**
	 * Возвращает закрепленные на компе лицензии
	 */
	public function getLicGroups()
	{
		return $this->hasMany(LicGroups::class, ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_comps}}', ['comps_id' => 'id']);
	}

	/**
	 * Возвращает закрепленные на компе лицензии
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::class, ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_comps}}', ['comps_id' => 'id']);
	}

	/**
	 * Возвращает закрепленные на компе лицензии
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::class, ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_comps}}', ['comps_id' => 'id']);
	}

	/**
	 * Песочницы, суффикс которых стоит на конце введённого имени —
	 * кандидаты для WYSIWYG-поиска клона по отображаемому имени {@see renderName()}.
	 * Более длинные суффиксы первыми: интерпретация '1C_TEST' специфичнее 'TEST'
	 * @param string $name
	 * @return Sandboxes[]
	 */
	public static function sandboxSuffixMatches(string $name) {
		$matches=[];
		foreach (Sandboxes::getAllItems(true) as $sandbox) {
			/** @var Sandboxes $sandbox */
			$len=mb_strlen($sandbox->suffix??'');
			if (!$len || mb_strlen($name)<=$len) continue;
			if (mb_strtolower(mb_substr($name,-$len))===mb_strtolower($sandbox->suffix))
				$matches[]=$sandbox;
		}
		usort($matches,fn($a,$b)=>mb_strlen($b->suffix)-mb_strlen($a->suffix));
		return $matches;
	}

	/**
	 * WYSIWYG-интерпретации введённого имени ОС — так, как оно отображается
	 * ({@see renderName()}), в порядке приоритета:
	 *  1. имя как введено + продуктив (sandbox_id IS NULL);
	 *  2. имя без суффикса песочницы + клон в этой песочнице
	 *     (суффикс срезается до разбора домена: в FQDN-форме он стоит после домена);
	 *  3. имя как введено без учёта песочницы — легаси-фоллбек для клона без продуктива.
	 * @param string $name
	 * @return array [[имя для поиска, условие по sandbox_id (null - не фильтровать)],...]
	 */
	public static function nameInterpretations(string $name): array {
		$attempts=[[$name,['sandbox_id'=>null]]];
		foreach (static::sandboxSuffixMatches($name) as $sandbox) {
			$attempts[]=[mb_substr($name,0,-mb_strlen($sandbox->suffix)),['sandbox_id'=>$sandbox->id]];
		}
		$attempts[]=[$name,null];
		return $attempts;
	}

	/**
	 * Найти комп по полному имени (Domain\comp или comp.domain.local)
	 * Имя резолвится WYSIWYG с учётом песочниц ({@see nameInterpretations()})
	 * @param        $name
	 * @param string $defaultDomain домен который присвоить ОС, если не найден домен в $name
	 * @return ActiveRecord|Comps|null|false
	 * @noinspection PhpUnusedLocalVariableInspection
	 */
	public static function findByAnyName($name,$defaultDomain='') {
		$notFound=null;
		foreach (static::nameInterpretations($name) as $i=>[$tryName,$sandboxCondition]) {
			$nameParse=Domains::fetchFromCompName($tryName,$defaultDomain);
			if (!is_array($nameParse)) {		//ошибка формата имени компа
				if ($i===0) $notFound=false;	//для имени как введено сохраняем прежнюю семантику ответа
				continue;
			}
			[$domain_id,$compName,$domainName]=$nameParse;
			if (is_null($domain_id)) continue;	//не найден домен этой интерпретации (например суффикс в FQDN-форме)

			$filter=['LOWER(name)'=>strtolower($compName)];
			if ($domain_id!==false) $filter['domain_id']=$domain_id;

			$query=static::find()->where($filter);
			//andWhere а не andFilterWhere: условие ['sandbox_id'=>null] должно дать IS NULL, а не отброситься
			if (!is_null($sandboxCondition)) $query->andWhere($sandboxCondition);

			if (is_object($model=$query->one())) return $model;
		}
		return $notFound;
	}

	public function getLastThreeLogins() {
		return LoginJournal::fetchUniqUsers($this->id);
	}

	public function getLogins() {
		return $this->hasmany(LoginJournal::class, ['comps_id' => 'id']);
	}

	public function getSoftRescans() {
		return $this->hasmany(CompsRescanQueue::class, ['comps_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['comps_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])->from(['comp_aces'=>Aces::tableName()])
			->viaTable('{{%comps_in_aces}}', ['comps_id' => 'id']);
	}

	public function getMaintenanceReqs()
	{
		return $this->hasMany(MaintenanceReqs::class, ['id' => 'reqs_id'])
			->viaTable('maintenance_reqs_in_comps', ['comps_id' => 'id']);
	}

	public function getMaintenanceJobs()
	{
		return $this->hasMany(MaintenanceJobs::class, ['id' => 'jobs_id'])
			->viaTable('maintenance_jobs_in_comps', ['comps_id' => 'id']);
	}


	/**
	 * @return ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id'])//->from(['comp_places'=>Places::tableName()])
			->via('arm');
	}


	/**
	 * Возвращает IP адреса
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])->from(NetIps::tableName())
			->viaTable('{{%ips_in_comps}}', ['comps_id' => 'id']);
	}



	public function getUpdatedRenderClass() {
		if (strlen($this->updated_at)) {
			$data_age=$this->secondsSinceUpdate;
			if ($data_age < 3600) return 'hour_fresh';
			elseif ($data_age < 3600*24) return 'day_fresh';
			elseif ($data_age < 3600*24*7) return 'week_fresh';
			elseif ($data_age < 3600*24*30) return 'month_fresh';
			else return 'over_month_fresh';
		} else return '';
	}

	public function getUpdatedText() {
		if (strlen($this->updated_at)) {
			$data_age=$this->secondsSinceUpdate;
			if ($data_age < 3600) return (int)($data_age/60).' мин.';
			elseif ($data_age < 3600*72) return (int)($data_age/3600).' ч.';
			else return (int)($data_age/3600/24).' дн.';
		} else return '';
	}

	/**
	 * Возвращает долю веса сервиса (с учетом дочерних)
	 * @param $serviceId
	 * @return float|int|mixed
	 */
	public function recursiveServicePartialWeight($serviceId) {
		if (isset($this->servicePartialWeightCache[$serviceId]))
			return $this->servicePartialWeightCache[$serviceId];
		$total=0;
		$current=0;
		foreach ($this->services as $service) {
			$total+=$service->weight;
			if ($service->inService($serviceId)) {
				$current+=$service->weight;
			}
		}

		if (!$total)
			$this->servicePartialWeightCache[$serviceId]=0; //no services
		else
			$this->servicePartialWeightCache[$serviceId]=$current/$total;

		return $this->servicePartialWeightCache[$serviceId];
	}

	/**
	 * @return Users
	 */
	public function getResponsible()
	{
		//если есть явно закрепленный юзер за ОС
		if (is_object($this->user)) return $this->user;

		//если есть ответственный за сервисы на компе - возвращаем его
		if ($servicesResponsible=$this->servicesResponsible) {
			return $servicesResponsible;
		}

		//последний вариант смотрим, кто сопровождает АРМ
		return $this->arm->managementService->responsible??null;
	}

	/**
	 * @return Users
	 */
	public function getServicesResponsible()
	{
		if (is_object($this->user)) return $this->user;

		return Services::responsibleFrom($this->services,true);
	}

	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return Users[]
	 * @noinspection UnusedElement
	 */
	public function getSupportTeam()
	{
		//берем сервисы ОС
		$services=$this->services;
		if (is_object($managementService=$this->arm->managementService??null)) {
			//если есть сервис управления АРМ, то берем его в сервисы
			$services[]=$managementService;
		}

		$team=Services::supportTeamFrom($services);
		if (is_object($this->user)) $team[$this->user->id]=$this->user;

		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->responsible)) {
			if (isset($team[$responsible->id])) unset($team[$responsible->id]);
		}

		return array_values($team);
	}

	/**
	 * Возвращает группу пользователей ответственный + поддержка всех сервисов на компе
	 * @return Users[]
	 * @noinspection UnusedElement
	 */
	public function getServicesSupportTeam()
	{
		$team=Services::supportTeamFrom($this->services,true);
		if (is_object($this->user)) $team[$this->user->id]=$this->user;

		//убираем из команды ответственного за ОС
		if (is_object($responsible=$this->servicesResponsible)) {
			if (isset($team[$responsible->id])) unset($team[$responsible->id]);
		}

		return array_values($team);
	}

	/**
	 * @param Comps $comp
	 * @throws Throwable
	 * @throws StaleObjectException
	 */
	public function absorbComp(Comps $comp) {
		//всё поглощение атомарно: любой сбой откатывает и перенос ссылок, и удаление клона
		$transaction=static::getDb()->beginTransaction();
		try {
			//журнал огромный и по одной записи менять это гемор
			LoginJournal::updateAll(['comps_id'=>$this->id],['comps_id'=>$comp->id]);

			//поглощаем все поля и ссылки переданной ОС и удаляем ее
			$this->absorbModel($comp,true);

			//легаси-запись может не проходить текущую валидацию - поглощение это срывать не должно
			if (!$this->save() && !$this->save(false))
				throw new Exception('Не удалось сохранить поглотившую ОС: '.print_r($this->errors,true));

			$transaction->commit();
		} catch (Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}



	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			//стандарт хранения MAC - строки голого hex (MacsHelper::fixList),
			//на него рассчитан поиск (LIKE по подстроке). Фильтр валидации
			//save(false) обходит, а так пишут агенты - поэтому нормализация
			//живёт здесь, как у Techs: мимо beforeSave не пишет никто
			$this->mac=MacsHelper::fixList($this->mac);

			/* Распознавание ПО (softHits_ids из raw_soft+soft_ids) нужно только когда
			   менялся отпечаток софта или паспортное ПО; рядовое сохранение записи
			   (правка полей руками, пуш с неизменным отпечатком) скан не запускает.
			   soft_ids проверяется по значению (attributeLinkChanged), а не по dirty-флагу:
			   EachValidator переприсваивает *_ids при validate(), так что после любого
			   сохранения с валидацией soft_ids всегда dirty даже без реальных изменений.
			   В режиме soft.deferred_rescan скан не выполняется даже при изменениях —
			   afterSave поставит задание в CompsRescanQueue, отработает cron comps/rescan
			   (он форсирует скан через $forceRescan). */
			$this->rescanNeeded=!Soft::$disable_rescan && (
				$this->forceRescan
				|| $this->isAttributeChanged('raw_soft')
				|| $this->attributeLinkChanged('soft_ids')
			);
			$this->rescanPerformed=false;
			if ($this->rescanNeeded
				&& (!\Yii::$app->params['soft.deferred_rescan'] || $this->forceRescan)
			) {
				$this->softHits_ids=array_keys($this->swList->items);
				$this->rescanPerformed=true;
			}

			/* взаимодействие с NetIPs: в реестр адресов (а значит и в сегменты/сети
			   и в пропагацию поиска оборудования) попадают только НЕ игнорируемые
			   адреса — ip_ignore вычитается из ip (см. getFilteredIps). Скрытые
			   «глазом» технические адреса теряют привязку к ОС и уходят из реестра. */
			$this->ip_cache=$this->ip_ignore_cache=$this->ip_filtered_cache=null;
			$this->netIps_ids=NetIps::fetchIpIds(implode("\n",$this->filteredIps));

			if ($this->platform_id) $this->arm_id=null;

			//грузим старые значения записи
			$old=static::findOne($this->id);
			if (!is_null($old)) {

				/* Взаимодействие с АРМ */

				//если поменялся АРМ, то надо из старого АРМа выкинуть эту ОСь
				if (!is_null($old->arm) && ($old->arm_id != $this->arm_id)) {

					//если у старого АРМа не только эта операционка привязана - назначим основной другую
					if (count($old->arm->comps) > 1) {
						foreach ($old->arm->comps as $comp) {
							if ($comp->id != $this->id) {
								$old->arm->comp_id = $comp->id;
								break;
							}
						}
					} else {
						//иначе удаляем в старом АРМ основную ОС
						$old->arm->comp_id = null;
					}
					//сохраняем старый арм
					$old->arm->save();
				}

				//находим все IP адреса которые от этой ОС отвалились
				$removed=array_diff($old->netIps_ids,$this->netIps_ids);
				//если есть отвязанные от это ос адреса
				if (count($removed)) foreach ($removed as $id) {
					//если он есть в БД
					if (is_object($ip=NetIps::findOne($id))) $ip->detachComp($this->id);
				}
			}


		}
		return true;
	}


	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}

		//отключаем рескан чтобы при сохранении софт не привязался обратно
		Soft::$disable_rescan=true;
		$this->softHits_ids=[];
		$this->silentSave(false);

		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachComp($this->id);
		}

		foreach ($this->linkedArms as $arm) {
			$arm->comp_id=null;
			$arm->save();
		}

		foreach ($this->softRescans as $queue) $queue->delete();

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave($insert,$changedAttributes)
	{
		parent::afterSave($insert,$changedAttributes);
		if ($this->rescanPerformed) {
			//скан выполнен - все ранее запланированные задания на рескан отработаны
			foreach ($this->softRescans as $queue) $queue->delete();
		} elseif ($this->rescanNeeded) {
			//скан требовался, но отложен - ставим задание на полный рескан (soft_id=null)
			if (!CompsRescanQueue::find()->where(['comps_id'=>$this->id,'soft_id'=>null])->exists()) {
				(new CompsRescanQueue(['comps_id'=>$this->id]))->save();
			}
		}
		//если в новом арме не назначена основная ОС, то назначим эту
		if (!is_null($this->arm_id)) {
			if (is_object($arm=$this->arm)) {
				if (empty($arm->comp_id)) {
					$arm->comp_id=$this->id;
					$arm->save();
				}
			}
		}
		/*
		 * Если у этой есть МАК и это не виртуалка
		 * и есть АРМы ссылающиеся на эту ОС
		 * и у них МАК пустой
		 * тогда вписываем им МАК от этой ОС
		 */
		if ($this->mac && !$this->ignore_hw) {
			foreach ($this->linkedArms as $arm) {
				if (empty($arm->mac)) {
					$arm->mac=$this->mac;
					$arm->save();
				}
			}
		}
		return true;
	}

	public function getIsWindows()
	{
		return (mb_stripos($this->os,'windows')!==false);
	}

	public function getIsLinux()
	{
		if (mb_stripos($this->os,'debian')!==false) return true;
		if (mb_stripos($this->os,'centos')!==false) return true;
		if (mb_stripos($this->os,'ubuntu')!==false) return true;
		if (mb_stripos($this->os,'fedora')!==false) return true;
		if (mb_stripos($this->os,'red hat')!==false) return true;
		if (mb_stripos($this->os,'suse')!==false) return true;
		return false;
	}

	public function getInServicesName() {return strtolower($this->fqdn);}

}
