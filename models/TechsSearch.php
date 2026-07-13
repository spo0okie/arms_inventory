<?php

namespace app\models;

use app\helpers\MacsHelper;
use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TechsSearch represents the model behind the search form of `app\models\Techs`.
 */
class TechsSearch extends Techs
{
	
	public $ids;
	public $model;
	public $user;
	public $place;
	public $type_id;
	public $inv_sn;
	public $user_position;
	public $user_dep;
	public $comp_hw;
	public $comp_updated_at;
	public $is_computer;
	//переключатель показа оборудования в архивных состояниях (см. showArchived в списках)
	public $archived;

	
	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['ids','state_id'],'each','rule'=>['integer']],
			[['is_computer','archived'],'boolean'],
            [[
				'type_id',
				'model_id',
				'places_id',

			], 'integer'],
            [[
				'num',
				'hostname',

				'inv_num',
				'inv_sn',
				'sn',
				'uid',
	
				'user',
				'user_dep',
				'user_position',
				'departments_id',
	
				'comp_id',
				'comp_hw',
				'comp_updated_at',

				'partners_id',
				
				'ip',
				'mac',

				'model',
				'place',

				'comment',
				'history',	//записная книжка оборудования (issue #206)

			], 'safe'],
			['mac', 'filter', 'filter' => function ($value) {
				return MacsHelper::fixList($value);
			}]
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
    public function search($params)
    {
        $query = Techs::find()
            ->joinWith([
            	'place',
				'comp.netIps.network',
				'comps',
				//.marker сегмента/состояния — жадная загрузка цветовых маркеров (issue #141),
				//иначе раскраска каждой строки списка делает отдельный запрос
				'netIps.network.segment.marker',
				'user.orgStruct',
				'model.manufacturer',
				'model.type',
				'state.marker',
				'partner',
				'department',
				'contracts',
				'licItems',
				'licGroups',
				'licKeys',
				'services.maintenanceReqs',
				'maintenanceReqs'
			]);

        $this->load($params);

		//поиск по IP: адреса ОС берём через привязки ips_in_comps (они уже
		//вычищены от ip_ignore в Comps::beforeSave), а НЕ из сырого текста
		//comps.ip — иначе скрытые «глазом» технические адреса всё равно
		//находились бы в списке оборудования. Отдельный alias comp_ips, чтобы
		//не конфликтовать с net_ips из joinWith(comp.netIps/netIps). Джойним
		//только при поиске по IP, чтобы не утяжелять обычный список.
		if (strlen((string)$this->ip)) {
			$query
				->leftJoin('ips_in_comps comp_ips_link','comp_ips_link.comps_id = comps.id')
				->leftJoin('net_ips comp_ips','comp_ips.id = comp_ips_link.ips_id');
		}
	
		//строка полной маркировки такая сложная, т.к. надо проверить что каждая компонента не NULL
		//т.к. CONCAT в который передали хоть один NULL верент ответом также NULL
		$mark='CONCAT(IFNULL(techs.sn,""), ", ", IFNULL(techs.inv_num,""), ", " , IFNULL(techs.uid,""))';
		$num='UCASE(CONCAT(IFNULL(techs.hostname,""), IFNULL(techs.num,"")))';

		$sort=[
			//'defaultOrder' => ['num'=>SORT_ASC],
			'attributes'=>[
				'num'=>[
					'asc'=>[$num=>SORT_ASC],
					'desc'=>[$num=>SORT_DESC],
				],
				'inv_num',
				'sn',
				'hostname',
				'uid',
				'inv_sn'=>[
					'asc'=>[$mark=>SORT_ASC],
					'desc'=>[$mark=>SORT_DESC],
				],
				
				'user'=>[
					'asc'=>['users.Ename'=>SORT_ASC],
					'desc'=>['users.Ename'=>SORT_DESC],
				],
				'user_position'=>[
					'asc'=>['users.doljnost'=>SORT_ASC],
					'desc'=>['users.doljnost'=>SORT_DESC],
				],
				'user_dep'=>[
					'asc'=>['org_struct.name'=>SORT_ASC],
					'desc'=>['org_struct.name'=>SORT_DESC],
				],

				'departments_id'=>[
					'asc'=>['departments.name'=>SORT_ASC],
					'desc'=>['departments.name'=>SORT_DESC],
				],
				
				'partners_id'=>[
					'asc'=>['CONCAT(partners.uname,partners.bname)'=>SORT_ASC],
					'desc'=>['CONCAT(partners.uname,partners.bname)'=>SORT_DESC],
				],
				
				'state_id'=>[
					'asc'=>['tech_states.name'=>SORT_ASC],
					'desc'=>['tech_states.name'=>SORT_DESC],
				],

				'comp_id'=>[
					'asc'=>['comps.name'=>SORT_ASC],
					'desc'=>['comps.name'=>SORT_DESC],
				],

				'ip'=>[
					'asc'=>['concat(comps.ip,techs.ip)'=>SORT_ASC],
					'desc'=>['concat(comps.ip,techs.ip)'=>SORT_DESC],
				],
				'mac'=>[
					'asc'=>['concat(comps.mac,techs.mac)'=>SORT_ASC],
					'desc'=>['concat(comps.mac,techs.mac)'=>SORT_DESC],
				],
				
				'model'=>[
					'asc'=>['tech_models.name'=>SORT_ASC],
					'desc'=>['tech_models.name'=>SORT_DESC],
				],
				
				'place'=>[
					'asc'=>['getplacepath(techs.places_id)'=>SORT_ASC],
					'desc'=>['getplacepath(techs.places_id)'=>SORT_DESC],
				],
				
				'comment',
			]
		];
	
	
		if (!$this->validate()) {
            return new ActiveDataProvider([
				'query' => $query,
				'totalCount' => $query->count('distinct(techs.id)'),
				'pagination' => ['pageSize' => 100,],
				'sort'=> $sort,
			]);
        }
		
		
		//по умолчанию не показываем оборудование в архивных состояниях
		//(tech_states.archived=1). Показать их можно переключателем "Архивные"
		//(URL-параметр showArchived -> $this->archived), выбором конкретного
		//статуса, либо запросом явного списка id (напр. связанное с сервисом
		//оборудование — там пользователь ждёт все выбранные записи).
		//NULL (оборудование без состояния) остаётся видимым за счёт LEFT JOIN.
		if (!$this->archived && empty($this->state_id) && empty($this->ids)) {
			$query->andWhere(['or',
				['tech_states.archived'=>0],
				['tech_states.archived'=>null],
			]);
		}

        $query
			->andFilterWhere(['techs.id'=>$this->ids])
			->andFilterWhere(QueryHelper::querySearchString($num, $this->num))
			->andFilterWhere(QueryHelper::querySearchString('techs.hostname', $this->hostname))

			->andFilterWhere(QueryHelper::querySearchString('techs.inv_num', $this->inv_num))
			->andFilterWhere(QueryHelper::querySearchString('techs.sn', $this->sn))
			->andFilterWhere(QueryHelper::querySearchString('techs.uid', $this->uid))
			//поиск по инв-номеру ищет также и по записной книжке оборудования (issue #206)
			->andFilterWhere(QueryHelper::querySearchString(['AND/OR',$mark,'IFNULL(techs.history,"")'], $this->inv_sn))

			->andFilterWhere(QueryHelper::querySearchString('users.Ename', $this->user))
			->andFilterWhere(QueryHelper::querySearchString('users.Doljnost', $this->user_position))
			->andFilterWhere(QueryHelper::querySearchString('org_struct.name',$this->user_dep))
			->andFilterWhere(QueryHelper::querySearchString('departments.name',$this->departments_id))
	
			->andFilterWhere(QueryHelper::querySearchString('comps.name', $this->comp_id))
			->andFilterWhere(QueryHelper::querySearchString('comps.raw_hw',$this->comp_hw))
			->andFilterWhere(QueryHelper::querySearchNumberOrDate('comps.updated_at',$this->comp_updated_at))
	
	
			->andFilterWhere(QueryHelper::querySearchString('concat(manufacturers.name," ",tech_models.name)',$this->model))
			->andFilterWhere(QueryHelper::querySearchString('getplacepath(places.id)', $this->place))
			
			->andFilterWhere(QueryHelper::querySearchString('CONCAT(partners.uname,partners.bname)', $this->partners_id))

            ->andFilterWhere(QueryHelper::querySearchString(['OR','comp_ips.text_addr','techs.ip'], $this->ip))

			->andFilterWhere(['techs.model_id'=>$this->model_id])
			->andFilterWhere(['techs.state_id'=>$this->state_id])
			->andFilterWhere(['techs.places_id'=>$this->places_id])
			->andFilterWhere(['tech_models.type_id'=>$this->type_id])
			->andFilterWhere(['tech_types.is_computer'=>$this->is_computer])

			->andFilterWhere(QueryHelper::querySearchString('techs.comment', $this->comment))
			//отдельный фильтр по записной книжке оборудования (issue #206)
			->andFilterWhere(QueryHelper::querySearchString('techs.history', $this->history));

		//поиск MAC: обычный LIKE (частичный/точный) ИЛИ вхождение в сохранённый
		//диапазон MAC (issue #120). Ищем и по MAC оборудования, и по MAC его ОС.
		if (strlen((string)$this->mac)) {
			$macLike=QueryHelper::querySearchString(['OR','comps.mac','techs.mac'], $this->mac);
			$macRange=MacsHelper::rangeMemberCondition(['comps.mac','techs.mac'], $this->mac);
			$query->andWhere($macRange ? ['or',$macLike,$macRange] : $macLike);
		}

		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('techs.id'),
			'totalCount' => $totalQuery->count('distinct(techs.id)'),
			'pagination' => ['pageSize' => 100,],
			'sort'=> $sort,
		]);
    }
}
