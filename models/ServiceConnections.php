<?php

namespace app\models;

use app\models\traits\ServiceConnectionsModelCalcFieldsTrait;
use voskobovich\linker\LinkerBehavior;

/**
 * This is the model class for table "service_connections".
 *
 */
class ServiceConnections extends ArmsModel
{
	use ServiceConnectionsModelCalcFieldsTrait;
	
	public static $title='Связь сервисов';
	public static $titles='Связи сервисов';
	
	public $archived=false;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'service_connections';
    }

	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'initiator_comps_ids'=>'initiatorComps',
					'initiator_techs_ids'=>'initiatorTechs',
					'target_comps_ids'=>'targetComps',
					'target_techs_ids'=>'targetTechs',
				]
			]
		];
	}


/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['initiator_id', 'target_id', 'initiator_details', 'target_details', 'comment', 'updated_at', 'updated_by'], 'default', 'value' => null],
            [['initiator_id', 'target_id'], 'integer'],
            [['updated_at'], 'safe'],
            [['initiator_details', 'target_details', 'comment'], 'string', 'max' => 255],
            [['updated_by'], 'string', 'max' => 32],
			[['initiator_comps_ids','initiator_techs_ids','target_comps_ids','target_techs_ids'], 'each', 'rule'=>['integer']],
		];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return array_merge(parent::attributeData(),	[
			'initiator_id' => [
				'Сервис-инициатор',
				'hint'=>'Сервис, который инициирует связь',
			],
			'initiator_service'=>['alias'=>'initiator_id'],
			'target_id' => [
				'Сервис-ответчик',
				'hint'=>'Сервис, который принимает/отвечает на входящее соединение/взаимодействие',
			],
			'target_service'=>['alias'=>'target_id'],
			'initiator_details' => [
				'Параметры исх. соединения',
				'hint'=>'Если есть дополнительная важная информация',
			],
			'target_details' => [
				'Параметры вх. соединения',
				'hint'=>'Если есть какие-то дополнения: Входящие порты, шары обмена, URI и т.д.',
			],
			'initiator_comps_ids' => [
				'ОС/ВМ-инициатор(ы)',
				'hint'=>'Какие ОС/ВМ инициируют связь.'
					.'<br>Заполняется, если сервис работает на нескольких узлах и надо указать только участвующие в связи',
			],
			'initiator_techs_ids' => [
				'Оборудование-инициатор',
				'hint'=>'Какое оборудование инициирует связь'
					.'<br>Заполняется, если сервис работает на нескольких узлах и надо указать только участвующие в связи',
			],
			'initiator_nodes' => [
				'Узлы-источники',
				'hint'=>'Какие узлы инициируют исходящие соединения',
			],
			'target_comps_ids' => [
				'ОС/ВМ-ответчики',
				'hint'=>'Какие ОС/ВМ принимают соединения'
					.'<br>Заполняется, если сервис работает на нескольких узлах и надо указать только участвующие в связи',
			],
			'target_techs_ids' => [
				'Оборудование-ответчик',
				'hint'=>'Какое оборудование принимают соединения'
					.'<br>Заполняется, если сервис работает на нескольких узлах и надо указать только участвующие в связи',
			],
			'target_nodes' => [
				'Узлы-ответчики',
				'hint'=>'Какие узлы отвечают на входящие соединения',
			],
			'comment' => [
				'Описание взаимодействия',
				'hint'=>'Что собственно при этой связи происходит, какие данные передаются, зачем'
			]
        ]);
    }
	
	public function getInitiatorComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('comps_in_initiators', ['connection_id' => 'id']);
	}
	
	public function getInitiatorTechs()
	{
		return $this->hasMany(Techs::class, ['id' => 'techs_id'])
			->viaTable('techs_in_initiators', ['connection_id' => 'id']);
	}
	
	public function getTargetComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('comps_in_targets', ['connection_id' => 'id']);
	}
	
	public function getTargetTechs()
	{
		return $this->hasMany(Techs::class, ['id' => 'techs_id'])
			->viaTable('techs_in_targets', ['connection_id' => 'id']);
	}
	
	public function getInitiator()
	{
		return $this->hasOne(Services::class,['id'=>'initiator_id'])
			->from(['initiator_services'=>Services::tableName()]);
	}
	public function getTarget()
	{
		return $this->hasOne(Services::class,['id'=>'target_id'])
			->from(['target_services'=>Services::tableName()]);
	}
	
	public function reverseLinks()
	{
		return [
			$this->initiatorComps,
			$this->initiatorTechs,
			$this->targetComps,
			$this->targetTechs,
		
		];
	}
}