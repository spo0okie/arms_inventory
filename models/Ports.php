<?php

namespace app\models;

use app\components\Forms\ArmsForm;
use app\models\base\ArmsModel;
use Yii;

/**
 * This is the model class for table "ports".
 *
 * @property int $id
 * @property int $techs_id
 * @property string  $name
 * @property string  $comment
 * @property int     $link_techs_id
 * @property int     $link_ports_id
 * @property string  $aggr Имя группы (Po1, BAGG1), в которую собран порт
 * @property string  sname
 * @property string  deviceName
 * @property string  fullName
 *
 * @property Ports   $linkPort
 * @property Techs   $linkTech
 * @property Techs   $tech
 */
class Ports extends ArmsModel
{
	public $link_arms_id;
	public $link_techs_id;

	public static $port_prefix='Порт ';
	public static $tech_postfix=': ';
	public static $null_port='0';

	public static $title='Сетевой порт';
	public static $titles='Сетевые порты';

	public static function modelDescription(): string
	{
		return 'Порты коммутации: порты оборудования и их соединения.';
	}
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ports';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['techs_id', 'link_techs_id'], 'integer'],
			//сначала обрезаем, потом меряем: «  Po1 » - это Po1
			[['aggr'], 'filter', 'filter' => fn($value) => is_null($value) ? null : (trim($value) ?: null)],
			[['aggr'], 'string', 'max' => 32],
			[['link_ports_id'],function ($attribute, $params, $validator) {
				if (
					!empty($this->link_ports_id)
				&&
					!is_numeric($this->link_ports_id)
				&&
					strpos($this->link_ports_id,'create:')!==0
				) {
					$this->addError($attribute, "Неверный порт устройства");
				}
			}],
			[['techs_id', 'link_techs_id','link_ports_id','name'], 'default','value'=>null],
			[['name'], 'required'],
			[['name'], 'unique', 'skipOnError' => true, 'skipOnEmpty'=>false, 'targetAttribute'=>['name','arms_id','techs_id'],'message'=>'Такой порт на этому устройстве уже объявлен'],
			[['name'], 'string', 'max' => 32],
            [['comment'], 'string', 'max' => 255],
            [['link_techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['link_techs_id' => 'id']],
			[['techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['techs_id' => 'id']],
        ];
    }

	public $linksSchema=[
		'techs_id'=>[Techs::class,'ports_ids'],
		'link_techs_id'=>[Techs::class,'techs_id'],
		'link_ports_id'=>[Ports::class,'link_ports_id'],
		'arms_id'=>Techs::class,
	];

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'techs_id' => [
				'На устройстве',
				'hint' => 'На каком устройстве расположен порт',
				'placeholder' => 'Выберите устройство',
			],
			'name' => [
				'Наименование',
				'hint' => 'Номер или маркировка порта (1/24/Combo 1/iLO/Console/Management)',
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Детали по порту / соединению до удаленного устройства',
				'typeClass'=>\app\types\StringType::class,
			],
			'link_techs_id' => [
				'Подсоединенное устройство',
				'hint' => 'Подсоединенное устройство',
				'placeholder' => 'Выберите устройство',
			],
			'link_ports_id' => [
				'Порт на устройстве',
				'hint' => 'Если оставить пустым, то будет объявлено соединение с устройством, без указания конкретного порта',
				'placeholder' => 'Укажите порт на устройстве',
			],
			'aggr' => [
				'Агрегат',
				'hint' => 'Имя агрегированного канала (Po1, BAGG1, LAG2), в который собран '
					.'этот порт, — одинаковое у всех портов группы.<br>'
					.'Это ярлык, а не порт: розетки у группы нет, кабель у каждого её порта '
					.'свой, и соединение записывается на каждом порту как обычно. Ярлык '
					.'нужен, чтобы видеть, какие порты работают как один, и чтобы опрос '
					.'коммутатора (он ведёт адреса на Po1, а не на портах) раскладывал '
					.'находки по портам группы',
				'placeholder' => 'Po1',
			],
		];
	}


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLinkPort()
    {
        return $this->hasOne(Ports::className(), ['id' => 'link_ports_id'])
			->from(['port_linked_ports'=>Ports::tableName()]);
    }


	public function getTemplateComment()
	{
		//у безымянного порта (устройство привязано без порта) шаблона нет
		if (is_object($this->tech) && strlen((string)$this->name))
			return $this->tech->getModelPortComment($this->name);

		return null;
	}
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getLinkTech()
	{
		return $this->hasOne(Techs::className(), ['id' => 'techs_id'])
			->via('linkPort');
	}

	/**
     * @return \yii\db\ActiveQuery
    public function getPort()
    {
        return $this->hasOne(Ports::className(), ['link_ports_id' => 'id'])
			->from(['port_parent_ports'=>Ports::tableName()]);
    }
	 */

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTech()
	{
		return $this->hasOne(Techs::className(), ['id' => 'techs_id']);
	}


	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->name?$this->name:static::$null_port;
	}

	/**
	 * Name of port device
	 * @return string
	 */
	public function getDeviceName() {
		if ($this->techs_id) return $this->tech->num;
		return 'NO DEVICE (ERR)';
	}

	/**
	 * Name of device-port
	 * @return string
	 */
	public function getFullName($reverse=false)
	{
		return $reverse?
			(static::$port_prefix.$this->sname.static::$tech_postfix.$this->deviceName):
			($this->deviceName.static::$tech_postfix.static::$port_prefix.$this->sname);
	}

	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['name'=>SORT_ASC])
            ->all();
        if (!is_array($list)) $list=[];
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }





	/**
	 * Порт устройства по имени: существующая строка либо новая (несохранённая).
	 *
	 * Строки `ports` ленивые - они появляются только под связь, поэтому у
	 * объявленного, но ни к чему не подключённого порта записи может не быть
	 * вовсе.
	 */
	public static function forTech(Techs $tech, string $name): Ports
	{
		$port = static::find()->where(['techs_id' => $tech->id, 'name' => $name])->one();
		if (is_object($port)) return $port;

		$port = new Ports();
		$port->techs_id = $tech->id;
		$port->name = $name;
		//комментарий подтянется из шаблона модели (getTemplateComment)
		return $port;
	}

	/**
	 * Снять связь с порта.
	 *
	 * Встречный порт отвязывается сам ({@see afterSave()}), а строка порта,
	 * оставшаяся без связи и без комментария, там же и удаляется - тогда
	 * save() возвращает false, и это НЕ ошибка.
	 *
	 * Имя не unlink(): так называется метод ActiveRecord для разрыва связи
	 * между моделями, и переопределять его нельзя.
	 */
	public function dropLink(): bool
	{
		if (empty($this->link_ports_id)) return true;

		$this->link_ports_id = null;
		//link_techs_id - виртуальное поле, оно могло остаться в памяти от
		//привязки: с ним beforeSave() завёл бы безымянный порт на той стороне
		//и связал заново вместо того, чтобы отпустить
		$this->link_techs_id = null;
		$this->save(false);
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {

			//https://wiki.reviakin.net/инвентаризация:dev:ports
			//error_log('before_save '.$this->id.' -> '.$this->link_ports_id);

			$tpl=$this->getTemplateComment();
			$reversePort=null;
			if (!is_numeric($this->link_ports_id)) {
				//если нам нужно создать порт - создаем
				if (strlen($this->link_ports_id??'') && strpos($this->link_ports_id,'create:')===0) {
					$reversePort=new Ports();
					$tokens=explode(':',$this->link_ports_id);
					//нужно создать новый порт с именем
					if (strlen($tokens[1]))		$reversePort->name=$tokens[1];
					//привязываем оборудование
					if ($this->link_techs_id)	$reversePort->techs_id=$this->link_techs_id;

				} elseif ($this->link_techs_id) {
					//порт не передан числом (ID) и не тестовая директива создания - считаем что тогда NULL
					//если при этом указано оборудование/АРМ - значит надо привязаться к порту NULL на этом оборудовании
					//ищем такой порт
					$reversePort=Ports::find()
						->where(['and',
							['techs_id'=>$this->link_techs_id],
							['name'=>null]
						])
						->one();

					//если не нашли-создаем
					if (!is_object($reversePort)) {
						$reversePort=new Ports();
						$reversePort->techs_id=$this->link_techs_id;
					}

				} elseif ((!strlen(trim($this->comment??'')) || $this->comment==$tpl)
					//членство в группе - такое же содержимое, как комментарий:
					//порт с ярлыком Po1 пустым не считается
					&& !strlen((string)$this->aggr)) {
					//мы вообще ни к чему не привязываемся и у нас даже комментария нет
					if (!$insert) {
						//если это обновление (не вставка): прежде чем исчезнуть, снимаем
						//встречную ссылку - link_ports_id уже обнулён, и beforeDelete()
						//соседа не увидит; иначе на той стороне остаётся порт,
						//соединённый с несуществующей строкой
						$this->releasePeer((int)$this->getOldAttribute('link_ports_id'));
						$this->delete();
						//ну и такое мы не сохраняем. Привязок нет, комментариев нет. А что сохранять то?
						return false;
						//почему удаляем только на обновлении?
						//потому что возможен сценарий когда мы создаем одновременно 2 порта.
						//новый ссылается на новый (директива create)
						//тогда после create нам нужно его сохранить и получить ID,
						//но на момент сохранения у него не будет ничего
						//такой вроде бы пустой, но нужный порт надо сохранять
					}
				}
			}

			if (is_object($reversePort)) {
				//безымянный встречный порт («привязать к устройству без порта»)
				//правило required для name не пройдёт - а он именно такой по
				//замыслу: валидировать ему нечего, кроме techs_id, который мы
				//сами и поставили
				if (!$reversePort->save(strlen((string)$reversePort->name) > 0)) {
					$this->addError('link_ports_id', 'Не удалось завести порт на той стороне: '
						.implode('; ', $reversePort->firstErrors));
					return false;
				}
				$reversePort->refresh();
				$this->link_ports_id=$reversePort->id;
			}




			return true;
		}
		return false;
	}

	/**
	 * @inheritdoc
	 *
	 * Связь портов парная и хранится с обеих сторон, поэтому при удалении
	 * порта встречную ссылку надо снять: иначе на соседнем устройстве остаётся
	 * порт, соединённый с несуществующей строкой, и карточка показывает
	 * связь, которой нет.
	 *
	 * Ловится это легко: порт, оставшийся без связи и без комментария,
	 * удаляет себя сам ({@see beforeSave()}) — то есть снятие связи с одной
	 * стороны и есть удаление строки.
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) return false;
		$this->releasePeer((int)$this->link_ports_id);
		return true;
	}

	/**
	 * Снять встречную ссылку с порта на той стороне (если она на нас).
	 * Пустой порт-сирота при этом удаляется: он появился только ради связи.
	 */
	protected function releasePeer(int $peerId): void
	{
		if (!$peerId) return;
		$peer = static::findOne($peerId);
		if (!is_object($peer) || (int)$peer->link_ports_id !== (int)$this->id) return;

		//ссылку снимаем в памяти ДО удаления: beforeDelete() соседа смотрит на
		//неё и иначе вернулся бы к нам - по кругу
		$peer->link_ports_id = null;
		//без связи, комментария и группы порт не нужен (так же решил бы и
		//beforeSave), а save(false) тут запустил бы тот же круг через самоудаление
		if (!strlen(trim((string)$peer->comment)) && !strlen((string)$peer->aggr))
			$peer->delete();
		else
			$peer->save(false);
	}

	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		//если изменился порт
		if (isset($changedAttributes['link_ports_id'])) {
			//значит ранее были были привязаны к другому порту а теперь нет.
			if (
				!empty($changedAttributes['link_ports_id'])
				&&
				$changedAttributes['link_ports_id']!=(int)$this->link_ports_id
				&&
				is_object($oldPort=Ports::findOne($changedAttributes['link_ports_id']))
				&&
				$oldPort->link_ports_id==$this->id //тут может быть момент что тот с кем мы раньше были связаны уже от нас отвязался
			){
				//если получилось загрузить порт с которым были связаны
				//отвязываемся
				$oldPort->link_ports_id=null;
				if (empty($oldPort->name) && empty($oldPort->comment))
					$oldPort->delete();
				else
					$oldPort->save(false);
			}
		}

		//error_log($this->id.'->'.$this->link_ports_id);
		//if (!is_object($newPort=$this->linkPort)) error_log($this->id.'->'.$this->link_ports_id.' - not an obj');
		//else error_log($this->id.'->'.$this->link_ports_id.'->'.$this->linkPort->link_ports_id);
		//также это может означать что теперь мы привязаны к новому порту, который не привязан к нам
		if (
			!empty($this->link_ports_id)
			&&
			is_object($newPort=$this->linkPort)
			&&
			$newPort->link_ports_id!=$this->id
		){
			//error_log('reversing '.$newPort->id.'->'.$this->id);
			//если получилось загрузить порт с которым стали связаны
			//и который не связан с нами - связываем
			$newPort->link_ports_id=$this->id;
			$newPort->save(false);
			//$newPort->refresh();
		} //else error_log('no reverse link for '.$this->id);
	}
}
