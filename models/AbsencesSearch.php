<?php

namespace app\models;

use app\helpers\QueryHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AbsencesSearch — модель поиска/фильтрации для `app\models\Absences`.
 */
class AbsencesSearch extends Absences
{
	public $disablePagination = false;
	public $ids;
	public $objects;   //поиск по сотруднику/организации (текстом)

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['id', 'user_id'], 'integer'],
			[['type', 'source', 'external_id', 'date_from', 'date_to', 'comment', 'updated_at', 'updated_by', 'objects'], 'safe'],
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
		$query = Absences::find()->with(['user']);

		//запрос для фильтра (с JOIN чтобы фильтровать по связанным объектам)
		$filter = Absences::find()
			->select('DISTINCT(absences.id)')
			->joinWith(['user']);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => $this->disablePagination ? false :
				['pageSize' => Yii::$app->request->get('per-page', 100)],
		]);

		$this->load($params);

		if (!$this->validate()) {
			// uncomment the following line if you do not want to return any records when validation fails
			// $query->where('0=1');
			return $dataProvider;
		}

		$filter->andFilterWhere([
			'absences.id' => $this->ids,
			'absences.user_id' => $this->user_id,
			'absences.type' => $this->type,
			'absences.source' => $this->source,
		]);

		$filter->andFilterWhere(['like', 'absences.external_id', $this->external_id])
			->andFilterWhere(['like', 'absences.comment', $this->comment])
			->andFilterWhere(['>=', 'absences.date_to', $this->date_from])
			->andFilterWhere(['<=', 'absences.date_from', $this->date_to])
			->andFilterWhere(QueryHelper::querySearchString(['AND/OR',
				'IFNULL(users.Ename,"")',
			], $this->objects));

		//если фильтруем, то делаем двухходовку в виде SUB-QUERY
		if ($filter->where) {
			$filterSubQuery = $filter->createCommand()->rawSql;
			$query->where('absences.id in (' . $filterSubQuery . ')');
		}

		return $dataProvider;
	}
}
