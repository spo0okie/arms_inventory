<?php

namespace app\models;

use app\helpers\QueryHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * DnsNamesSearch — модель поиска/фильтрации для `app\models\DnsNames`.
 */
class DnsNamesSearch extends DnsNames
{
	public $disablePagination = false;
	public $ids;
	public $fqdn;	//фильтр по полному имени (host + fqdn зоны, текстом)

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['id', 'domain_id'], 'integer'],
			[['host', 'fqdn', 'ip', 'comment', 'updated_at', 'updated_by'], 'safe'],
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
	 * @return ActiveDataProvider
	 */
	public function search($params)
	{
		//Запрос для данных (БЕЗ JOIN чтобы не ломалась пагинация)
		$query = DnsNames::find()->with(['domain', 'netIps.network', 'netIps.comps', 'netIps.techs']);

		//запрос для фильтра (с JOIN чтобы фильтровать по связанным объектам)
		$filter = DnsNames::find()
			->select('DISTINCT(dns_names.id)')
			->joinWith(['domain']);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => $this->disablePagination ? false :
				['pageSize' => Yii::$app->request->get('per-page', 100)],
			'sort' => [
				'defaultOrder' => ['domain_id' => SORT_ASC, 'host' => SORT_ASC],
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			return $dataProvider;
		}

		$filter->andFilterWhere([
			'dns_names.id' => $this->ids,
			'dns_names.domain_id' => $this->domain_id,
		]);

		$filter->andFilterWhere(['like', 'dns_names.host', $this->host])
			->andFilterWhere(['like', 'dns_names.ip', $this->ip])
			->andFilterWhere(['like', 'dns_names.comment', $this->comment])
			->andFilterWhere(['like', 'dns_names.updated_by', $this->updated_by])
			->andFilterWhere(QueryHelper::querySearchString(['AND/OR',
				//полное имя: у apex host пустой — CONCAT даст ".zone", поэтому склеиваем через TRIM
				'TRIM(LEADING "." FROM CONCAT(IFNULL(dns_names.host,""),".",IFNULL(domains.fqdn,"")))',
			], $this->fqdn));

		//если фильтруем, то делаем двухходовку в виде SUB-QUERY
		if ($filter->where) {
			$filterSubQuery = $filter->createCommand()->rawSql;
			$query->where('dns_names.id in (' . $filterSubQuery . ')');
		}

		return $dataProvider;
	}
}
