<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AcesSearch represents the model behind the search form of `app\models\Aces`.
 */
class AcesSearch extends Aces
{
	public $ids;
	public $subjects;
	public $resource;
	public $access_types;
	public $archived;
	
	public $services_subject_ids;
	public $services_resource_ids;
	//сервисы, чьи узлы/адреса считаются ресурсом, когда соединение вписывается в стандартные
	//доступы сервиса (Services::getIncomingViaNodesAcesIds) — по ИЛИ с services_resource_ids
	public $services_nodes_resource_ids;
	//сегмент как субъект ACE / как ресурс ACL (вкладки доступов на странице сегмента)
	public $segments_subject_ids;
	public $segments_resource_ids;
	//адрес как субъект ACE / как ресурс ACL (вкладки доступов на странице IP) — сетевой
	//взгляд: сам адрес ИЛИ узлы, к которым он привязан (субъекты — ОС и пользователи,
	//ресурсы — ОС и оборудование): доступ узла = доступ всех его адресов
	public $ips_subject_ids;
	public $ips_resource_ids;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
            	'ips',
				'comment',
				'access_types',
				'resource',
				'subjects',
				'name',
				'services_subject_ids',
				'services_resource_ids',
				'services_nodes_resource_ids',
				'segments_subject_ids',
				'segments_resource_ids',
				'ips_subject_ids',
				'ips_resource_ids',
			], 'safe'],
			[['ids'],'each','rule'=>['integer']]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params,$columns=null)
    {
	
		[$query,$filter]=(new Aces())->prepareSearch($columns);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider(['query' => $query]);
        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $dataProvider;
        }
	
		//если ИД указаны, то ограничиваем
		if (isset($this->ids) && is_array($this->ids)) {
			if (count($this->ids))
				$filter->andFilterWhere(['aces.id'=>$this->ids]);
			else //если они пустые, то блокируем дальнейший поиск
				$filter->where('0=1');
		}

        // grid filtering conditions
		$filter->andFilterWhere([
            'id' => $this->id,
			'services_subjects.id' => $this->services_subject_ids,
            'updated_at' => $this->updated_at,
        ]);

		//доступ к сервису: ACL на сам сервис и (если задано) записи на его узлы/адреса,
		//вписывающиеся в стандартные доступы сервиса — подзапросами, по ИЛИ
		$incoming=['or'];
		if (!empty($this->services_resource_ids)) {
			$incoming[]=['aces.acls_id'=>(new \yii\db\Query())->select('id')
				->from('acls')->where(['services_id'=>$this->services_resource_ids])];
		}
		if (!empty($this->services_nodes_resource_ids)) {
			$viaNodes=[];
			foreach (Services::find()->where(['id'=>(array)$this->services_nodes_resource_ids])->all() as $service)
				$viaNodes=array_merge($viaNodes,$service->getIncomingViaNodesAcesIds());
			if (count($viaNodes)) $incoming[]=['aces.id'=>array_values(array_unique($viaNodes))];
		}
		if (count($incoming)>1) $filter->andWhere($incoming);
		elseif (!empty($this->services_nodes_resource_ids)) $filter->andWhere('0=1');
		
		//сегментные фильтры — подзапросами: не зависят от того, какие связи заджойнены
		//под видимые колонки (см. prepareSearch)
		if (!empty($this->segments_subject_ids)) {
			$filter->andWhere(['aces.id'=>(new \yii\db\Query())->select('aces_id')
				->from('segments_in_aces')->where(['segments_id'=>$this->segments_subject_ids])]);
		}
		if (!empty($this->segments_resource_ids)) {
			$filter->andWhere(['aces.acls_id'=>(new \yii\db\Query())->select('id')
				->from('acls')->where(['segments_id'=>$this->segments_resource_ids])]);
		}
		if (!empty($this->ips_subject_ids)) {
			$ips=$this->ips_subject_ids;
			$filter->andWhere(['or',
				['aces.id'=>(new \yii\db\Query())->select('aces_id')
					->from('ips_in_aces')->where(['ips_id'=>$ips])],
				['aces.id'=>(new \yii\db\Query())->select('aces_id')
					->from('comps_in_aces')->where(['comps_id'=>static::ipsNodesQuery('ips_in_comps','comps_id',$ips)])],
				['aces.id'=>(new \yii\db\Query())->select('aces_id')
					->from('users_in_aces')->where(['users_id'=>static::ipsNodesQuery('ips_in_users','users_id',$ips)])],
			]);
		}
		if (!empty($this->ips_resource_ids)) {
			$ips=$this->ips_resource_ids;
			$filter->andWhere(['aces.acls_id'=>(new \yii\db\Query())->select('id')
				->from('acls')->where(['or',
					['ips_id'=>$ips],
					['comps_id'=>static::ipsNodesQuery('ips_in_comps','comps_id',$ips)],
					['techs_id'=>static::ipsNodesQuery('ips_in_techs','techs_id',$ips)],
				])]);
		}

		//архивная запись доступа - это доступ, которым уже некому или незачем пользоваться:
		//истекло расписание, ушел в архив ресурс ACL либо кончились живые субъекты
		if (!($this->archived ?? false)) {
			$filter->joinWith(array_merge(['acl.schedule'],Acls::resourceJoins('acl.')));
			$filter->andWhere(Acls::activeScheduleCondition());
			$filter->andWhere(Acls::aliveResourceCondition());
			$filter->andWhere(Aces::aliveSubjectsCondition());
		}
		
		$filter
			->andFilterWhere(['or',
				QueryHelper::querySearchString('users_subjects.Ename', $this->subjects),
				QueryHelper::querySearchString('comps_subjects.name', $this->subjects),
				QueryHelper::querySearchString('services_subjects.name', $this->subjects),
				QueryHelper::querySearchString('networks_subjects.text_addr', $this->subjects),
				QueryHelper::querySearchString('segments_subjects.name', $this->subjects),
				QueryHelper::querySearchString('ips_subjects.text_addr', $this->subjects),
			])
			->andFilterWhere(['or',
				QueryHelper::querySearchString('techs_resources.num', $this->resource),
				QueryHelper::querySearchString('comps_resources.name', $this->resource),
				QueryHelper::querySearchString('services_resources.name', $this->resource),
				QueryHelper::querySearchString('networks_resources.text_addr', $this->resource),
				QueryHelper::querySearchString('segments_resources.name', $this->resource),
				QueryHelper::querySearchString('ips_resources.text_addr', $this->resource),
			])
			->andFilterWhere(QueryHelper::querySearchString('aces.name', $this->name))
			->andFilterWhere(QueryHelper::querySearchString('access_types.name', $this->access_types));
		
		if ($filter->where) {
			//фильтруем запрос данных по ID из фильтра, который мы только что получили при помощи разных WHERE
			$query->where(static::tableName().'.id in ('.$filter->createCommand()->rawSql.')');
		}

        return $dataProvider;
    }

	/**
	 * Подзапрос id узлов (ОС/оборудования/пользователей), к которым привязаны адреса
	 * @param string $junction ips_in_comps | ips_in_techs | ips_in_users
	 * @param string $key      comps_id | techs_id | users_id
	 * @param int[]  $ips      net_ips.id
	 * @return \yii\db\Query
	 */
	protected static function ipsNodesQuery(string $junction, string $key, $ips): \yii\db\Query
	{
		return (new \yii\db\Query())->select($key)->from($junction)->where(['ips_id'=>$ips]);
	}
}
