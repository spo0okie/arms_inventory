<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * Поиск/фильтрация журнала интеграций (грид index).
 * Журнал только читается — записи создаются реестром интеграций.
 */
class IntegrationsLogSearch extends IntegrationsLog
{
	public function rules()
	{
		return [
			[['id', 'object_id', 'parent_id'], 'integer'],
			[['created_at', 'provider', 'action', 'class', 'result', 'ext_login', 'message', 'users_id'], 'safe'],
		];
	}

	public function scenarios()
	{
		// обходим scenarios() родителя (ArmsModel) — фильтры это отдельная модель
		return Model::scenarios();
	}

	/**
	 * @param array $params
	 * @param mixed $columns не используется (передаётся базовым actionIndex)
	 * @return ActiveDataProvider
	 */
	public function search($params, $columns = null)
	{
		$query = IntegrationsLog::find()->joinWith('user');

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
			'pagination' => ['pageSize' => 100],
		]);

		$this->load($params);

		if (!$this->validate()) {
			return $dataProvider;
		}

		$query->andFilterWhere(['integrations_log.id' => $this->id]);
		$query->andFilterWhere(['object_id' => $this->object_id]);
		$query->andFilterWhere(['result' => $this->result]);
		$query->andFilterWhere(QueryHelper::querySearchString('provider', $this->provider));
		$query->andFilterWhere(QueryHelper::querySearchString('action', $this->action));
		$query->andFilterWhere(QueryHelper::querySearchString('integrations_log.class', $this->class));
		$query->andFilterWhere(QueryHelper::querySearchString('ext_login', $this->ext_login));
		$query->andFilterWhere(QueryHelper::querySearchString('message', $this->message));
		$query->andFilterWhere(QueryHelper::querySearchNumberOrDate('created_at', $this->created_at));
		//users_id как поле фильтра переиспользуем под поиск по имени инициатора
		$query->andFilterWhere(QueryHelper::querySearchString('users.Ename', $this->users_id));

		return $dataProvider;
	}
}
