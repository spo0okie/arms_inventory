<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tech_types".
 *
 * @property int $id id
 * @property bool $is_computer
 * @property bool $is_display
 * @property bool $is_ups
 * @property bool $is_phone
 * @property string $code Код
 * @property string $prefix Префикс
 * @property string $name Название
 * @property string $comment Комментарий
 * @property string $comment_name override Комментарий
 * @property string $comment_hint override Комментарий hint
 *
 * @property TechModels[] $techModels
 */
class TechTypes extends ArmsModel
{


	public static $title='Категории оборудования';
	public static $descr='Используемые категории различной техники для удобной группировки';
	
	/** @inheritdoc   */
	protected static $syncableFields=[
		'name',
		'code',
		'prefix',
		'comment',
		'comment_name',
		'comment_hint',
		'is_computer',
		'is_phone',
		'is_ups',
		'is_display',
		'updated_at',
		'updated_by',
	];
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['code', 'name', 'comment'], 'required'],
			[['is_computer','is_phone','is_display','is_ups','hide_menu'],'boolean'],
            [['comment'], 'string'],
	        [['code', 'name'], 'string', 'max' => 128,'min'=>2],
	        [['comment_hint'], 'string', 'max' => 128],
	        [['comment_name'], 'string', 'max' => 32],
	        [['prefix'], 'string', 'max' => 5,'min'=>2],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'id' => 'id',
			'code' => [
				'Код',
				'hint' => 'Может использоваться впоследствии для отдельных обработчиков событий и генерации CSS классов в формах просмотра и отчетах',
			],
			'name' => [
				'Название',
				'hint' => 'Понятное название категории оборудования<br><br>'.
					'Пример:<i>МФУ, VoIP Телефоны, ИБП, Радиостанции, WiFi AP, Маршрутизатор</i> ',
			],
			'prefix' => [
				'Префикс инв. номера',
				'hint' => 'При формировании инвентарного номера будет использоваться второй префикс<br>'.
					'для пояснения категории оборудования и объединения оборудования в одну группу<br>'.
					'Пример: <i>МСК-<b>МФУ</b>-0017, СПБ-<b>ИБП</b>-0102, ЧЕЛ-<b>ТЕЛ</b>-0033</i><br><br>'.
					'Если не указать, то всему оборудованию этой категории (и других без дополнительного префикса)<br>'.
 					'будет предложена сквозная нумерация на основе первоначального помещения<br>'.
					'(где оборудование изначально было введено в эксплуатацию)<br>'.
					'Пример: <i>МСК-000017, СПБ-000102, ЧЕЛ-000033</i>'
			],
			
			'techModelsCount' => [
				'# Моделей',
			],
			'usages' => [
				'# Обор-я',
			],
			'comment' => [
				'Шаблон описания моделей',
				'hint' => 'Те характеристики, которые нужно будет указывать при описании моделей оборудования<br>'.
					'Будет выводиться в форме создания/редактирования модели оборудования (этой категории),<br>'.
					'чтобы не забыть указать в описании модели важные (для этой категории оборудования) параметры и характеристики',
			],
			'comment_name' => [
				'Замена комментария',
				'hint' => 'Если заполнено, то у оборудования этой категории вместо поля "комментарий"<br>'.
					'будет выводиться внесенное здесь название (дополнительный ключевой параметр)<br>'.
				'Например <b>№ тел</b> для телефонов или <b>IMEI</b> для модемов',
			],
			'comment_hint' => [
				'Пояснение поля комментария',
				'hint' => 'Если предыдущий параметр заполнен,<br> '.
					'то этим параметром можно сделать подсказку для заполнения кастомного поля комментария.<br>'.
					'Выводиться будет также как эта подсказка',
			],
			'is_computer' => [
				'Компьютер',
				'hint' => 'Является разновидностью компьютера. <br>'.
					'Оборудование этой категории будет считаться обязательным для формирования АРМ<br>'.
					'Пример: <i>ПК, ноутбук, моноблок, сервер</i>',
			],
			'is_phone' => [
				'Телефон',
				'hint' => 'Является разновидностью телефона. <br>'.
					'При прикреплении к АРМ будет трактоваться как пользовательский телефон,<br>'.
					'а телефонный номер устройства будет прикреплен к пользователю АРМ как внутренний номер.<br>'.
					'<i>Пример: VoIP телефон, аналоговый телефон, DECT телефон</i>',
			],
			'is_ups' => [
				'ИБП',
				'hint' => 'Является разновидностью ИБП. <br>'.
					'При прикреплении к АРМ будет выводится как ИБП',
			],
			'is_display' => [
				'Дисплей',
				'hint' => 'Является разновидностью дисплея.<br>'.
					'При прикреплении к АРМ будет трактоваться как дисплей<br>'.
					'<i>Прим: монитор, проектор, ТВ</i>',
			],
			'hide_menu' => [
				'Не отображать в меню',
				'Скрыть этот элемент из списка категорий оборудования в главном меню'
			]
		];
	}


	/**
	 * @return ActiveQuery
	 */
	public function getTechModels()
	{
		return $this->hasMany(TechModels::className(), ['type_id' => 'id'])->joinWith('manufacturer')->orderBy(['manufacturers.name'=>SORT_ASC,'name'=>SORT_ASC]);
	}

	/**
	 * @return int
	 */
	public function getTechModelsCount()
	{
		return count($this->techModels);
	}


	/**
	 * @return int
	 */
	public function getUsages()
	{
		$sum=0;
		foreach ($this->techModels as $model) $sum+=$model->usages;
		return $sum;
	}
	
	public static function fetchNames()
	{
		$list = static::find()
			->select(['id', 'name'])
			->all();
		return ArrayHelper::map($list, 'id', 'name');
	}
	
	public static function fetchMenuNames()
	{
		$list = static::find()
			->select(['id', 'name','hide_menu'])
			->where('NOT ifnull(hide_menu,0)=1')
			->orderBy(['name'=>SORT_ASC])
			->all();
		return ArrayHelper::map($list, 'id', 'name');
	}
	
	
	
}
