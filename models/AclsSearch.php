<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Acls;

/**
 * AclsSearch represents the model behind the search form of `\app\models\Acls`.
 */
class AclsSearch extends Acls
{
	/**
	 * Вычисляемые колонки списка (см. views/acls/columns.php): собственных колонок
	 * в БД у них нет, поиск идет по связанным объектам отдельным подзапросом (см. search)
	 */
	public $subjects;
	public $resource;
	public $access_types;

	/**
	 * Показывать архивные списки доступа (архивный ресурс либо истекшее расписание).
	 * Своей колонки в БД у архивности ACL нет - она вычисляется, см. Acls::getArchived()
	 */
	public $archived;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'schedules_id', 'services_id', 'ips_id', 'comps_id', 'techs_id'], 'integer'],
            [['comment', 'notepad', 'subjects', 'resource', 'access_types'], 'safe'],
			['archived','boolean'],
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
     * @param string[]|null $columns отображаемые колонки грида - для жадной загрузки
     *   их связей (join-аннотации attributeData, см. ArmsModel::prepareSearch)
     *
     * @return ActiveDataProvider
     */
    public function search($params,$columns=null)
    {
        $query = Acls::find();
		$table=Acls::tableName();

		//жадная загрузка связей отображаемых колонок: без нее каждый ACL грузит
		//свои ACE и их субъектов (пользователей/ОС/IP/сети) отдельными запросами
		if (count($joins=(new Acls())->attributesJoins($columns)))
			$query->with($joins);

        // add conditions that should always apply here


        $sort=[
			'defaultOrder' => ['id'=>SORT_ASC],
			'attributes'=>[
				'id',
				'name',
			]
		];

        $this->load($params);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => $sort,
		]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            $table.'.id' => $this->id,
            'schedules_id' => $this->schedules_id,
            'services_id' => $this->services_id,
            'ips_id' => $this->ips_id,
            'comps_id' => $this->comps_id,
            'techs_id' => $this->techs_id,
        ]);

        $query->andFilterWhere(['like', $table.'.comment', $this->comment])
            ->andFilterWhere(['like', $table.'.notepad', $this->notepad]);

		//поиск по колонкам-агрегатам (значения лежат в связанных объектах) идет
		//отдельным запросом по ID: связи подключаются JOIN-ом, а основной запрос
		//остается без join-ов (иначе ломается пагинация).
		//JOIN добавляется ТОЛЬКО под непустой фильтр: join-цепочки всех колонок разом
		//сталкиваются алиасами (одна и та же таблица приезжает разными путями)
		$filter=Acls::find()->select('DISTINCT('.$table.'.id)');

		//архивный список доступа - это доступ к тому, чего уже нет: ресурс ушел
		//в архив (списан/архивирован) либо истекло расписание временного доступа
		if (!($this->archived ?? false)) {
			$filter->joinWith(array_merge(['schedule'],Acls::resourceJoins()));
			$filter->andWhere(Acls::activeScheduleCondition());
			$filter->andWhere(Acls::aliveResourceCondition());
		}

		if (strlen((string)$this->subjects)) {
			$filter->joinWith(['aces.users','aces.comps','aces.services','aces.netIps','aces.networks']);
			$filter->andFilterWhere(['or',
				QueryHelper::querySearchString('users_subjects.Ename', $this->subjects),
				QueryHelper::querySearchString('comps_subjects.name', $this->subjects),
				QueryHelper::querySearchString('services_subjects.name', $this->subjects),
				QueryHelper::querySearchString('networks_subjects.text_addr', $this->subjects),
				QueryHelper::querySearchString('ips_subjects.text_addr', $this->subjects),
				//текстовый субъект («Прочее» в ACE) - тоже субъект доступа
				QueryHelper::querySearchString('aces.comment', $this->subjects),
			]);
		}

		if (strlen((string)$this->resource)) {
			$filter->joinWith(['comp','tech','service','ip','network']);
			$filter->andFilterWhere(['or',
				QueryHelper::querySearchString('comps_resources.name', $this->resource),
				QueryHelper::querySearchString('techs_resources.num', $this->resource),
				QueryHelper::querySearchString('services_resources.name', $this->resource),
				QueryHelper::querySearchString('networks_resources.text_addr', $this->resource),
				QueryHelper::querySearchString('ips_resources.text_addr', $this->resource),
				//текстовый ресурс («Другое») - тоже ресурс
				QueryHelper::querySearchString($table.'.comment', $this->resource),
			]);
		}

		if (strlen((string)$this->access_types)) {
			$filter->joinWith(['aces.accessTypes']);
			$filter->andFilterWhere(QueryHelper::querySearchString('access_types.name', $this->access_types));
		}

		if ($filter->where) {
			//фильтруем запрос данных по ID из фильтра, который мы только что получили при помощи разных WHERE
			$query->andWhere($table.'.id in ('.$filter->createCommand()->rawSql.')');
		}

        return $dataProvider;
    }
}
