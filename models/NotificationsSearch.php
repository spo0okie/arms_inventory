<?php

namespace app\models;

use app\helpers\QueryHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * NotificationsSearch — модель поиска/фильтрации очереди уведомлений
 * `app\models\Notifications` (свежие записи сверху).
 */
class NotificationsSearch extends Notifications
{
	public $disablePagination = false;
	public $ids;
	public $objects;   //поиск по получателю (текстом)

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['id', 'user_id', 'attempts'], 'integer'],
			[['event_key', 'subject', 'body', 'last_error', 'created_at', 'sent_at', 'objects'], 'safe'],
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
		$query = Notifications::find()->with(['user']);

		//запрос для фильтра (с JOIN чтобы фильтровать по связанным объектам)
		$filter = Notifications::find()
			->select('DISTINCT(notifications.id)')
			->joinWith(['user']);

		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
			'pagination' => $this->disablePagination ? false :
				['pageSize' => Yii::$app->request->get('per-page', 100)],
		]);

		$this->load($params);

		if (!$this->validate()) {
			return $dataProvider;
		}

		$filter->andFilterWhere([
			'notifications.id' => $this->ids,
			'notifications.user_id' => $this->user_id,
			'notifications.attempts' => $this->attempts,
		]);

		$filter->andFilterWhere(['like', 'notifications.event_key', $this->event_key])
			->andFilterWhere(['like', 'notifications.subject', $this->subject])
			->andFilterWhere(['like', 'notifications.body', $this->body])
			->andFilterWhere(['like', 'notifications.last_error', $this->last_error])
			->andFilterWhere(['like', 'notifications.created_at', $this->created_at])
			->andFilterWhere(['like', 'notifications.sent_at', $this->sent_at])
			->andFilterWhere(QueryHelper::querySearchString(['AND/OR',
				'IFNULL(users.Ename,"")',
				'IFNULL(users.Email,"")',
			], $this->objects));

		//если фильтруем, то делаем двухходовку в виде SUB-QUERY
		if ($filter->where) {
			$filterSubQuery = $filter->createCommand()->rawSql;
			$query->where('notifications.id in (' . $filterSubQuery . ')');
		}

		return $dataProvider;
	}
}
