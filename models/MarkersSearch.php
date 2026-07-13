<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MarkersSearch — модель поиска/фильтрации маркеров
 */
class MarkersSearch extends Markers
{
	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['id'], 'integer'],
			[['name', 'color', 'text_color', 'border_color', 'border_style', 'comment'], 'safe'],
			[['archived'], 'boolean'],
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
	 * Создает data provider для модели Markers с примененными фильтрами
	 *
	 * @param array $params Параметры поиска
	 * @param array|null $columns Отображаемые колонки (не используется)
	 * @return ActiveDataProvider
	 */
	public function search($params, $columns = null)
	{
		$query = Markers::find();

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'defaultOrder' => ['name' => SORT_ASC],
			],
			'pagination' => [
				'pageSize' => 50,
			],
		]);

		$this->load($params);

		if (!$this->validate()) {
			return $dataProvider;
		}

		$query->andFilterWhere([
			'id' => $this->id,
			'archived' => $this->archived,
			'border_style' => $this->border_style,
		]);

		$query->andFilterWhere(['like', 'name', $this->name])
			->andFilterWhere(['like', 'color', $this->color])
			->andFilterWhere(['like', 'text_color', $this->text_color])
			->andFilterWhere(['like', 'border_color', $this->border_color])
			->andFilterWhere(['like', 'comment', $this->comment]);

		return $dataProvider;
	}
}
