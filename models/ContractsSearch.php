<?php

namespace app\models;

use app\components\DynaGridWidget;
use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ContractsSearch represents the model behind the search form of `app\models\Contracts`.
 */
class ContractsSearch extends Contracts
{
	
	/**
	 * @var mixed|null
	 */
    public $fullname;
    public $partners;
    public $users;

	/**
	 * @var mixed|null
	 */
	public $total;
	
	public $deliveryStatus;
	
	public function rules()
    {
        return [
            [['id', 'parent', 'state_id'], 'integer'],
            [['fullname', 'comment','total','deliveryStatus','name','partners','users','date','pay_id'], 'safe'],
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
	 * @param null  $tableId
	 * @return ActiveDataProvider
	 */
    public function search($params,$tableId='contracts-index')
    {
        $query = Contracts::find()->with([
			'currency',
			'partners',
	        'techs',
	        'licItems',
	        'services',
			'successor',
			'children',
			'users',
	        'state',
        ]);
	
		$showUsersInName=true;
		$showPartnersInName=true;
		$showPayIdInName=true;
		$showDateInName=true;

		if (!empty($tableId)) {
			$showUsersInName=!DynaGridWidget::tableColumnIsVisible($tableId,'users',Contracts::$defaultColumns);
			$showPartnersInName=!DynaGridWidget::tableColumnIsVisible($tableId,'partners',Contracts::$defaultColumns);
			$showPayIdInName=!DynaGridWidget::tableColumnIsVisible($tableId,'pay_id',Contracts::$defaultColumns);
			$showDateInName=!DynaGridWidget::tableColumnIsVisible($tableId,'date',Contracts::$defaultColumns);
		}
	
	
		$this->load($params);
        //if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
        //    return $dataProvider;
        //}
	
		/*
		 * В общем тут надо исповедаться, т.к. далее идет изврат, который не каждому будет по душе. Чтобы одновременно
		 * использовать join для поиска, и при этом не ломать пагинацию, в которую изза джойна попадают дополнительные
		 * строки, мы делаем так. Сначала делаем поиск по имени (которое включает в себя заджойненные таблицы) без
		 * пагинации. Этим поиском мы находим все контракты с нужным именем/пользователем/контрагентом. А потом мы
		 * ищем уже контракты у которых ID из найденного ранее набора.
		 *
		 * называется Subquery
		 * SELECT * FROM sometable WHERE Location IN (SELECT DISTINCT Location FROM sometable LIMIT 2);
		 */
		
		//как мы ищем контрагентов
		$partnersExpression="ifnull(`partners`.`uname`,'".static::$noPartnerSuffix."'),' (', ifnull(`partners`.`bname`,'') , ')'";
		
		
		//поисковый запрос в тексте повторяющем "шаблон вывода списка документов"
		// дата - наименование - контрагент - комментарий
		$nameExpression="concat(".
			($showDateInName?"ifnull(`contracts`.`date`,'нет даты'),' - ',":'').
			($showPayIdInName?"ifnull(`contracts`.`pay_id`,''),":'').
			"`contracts`.`name`,' - ',".
			($showPartnersInName?$partnersExpression.',':'').
			($showUsersInName?"ifnull(`users`.`Ename`,''),":'').
			"ifnull(`contracts`.`comment`,'')".
			")";
	
		//для сортировки убираем все джойны, так как сортировка будет только по одной таблице!
		$nameSort="concat(".
			($showDateInName?"ifnull(`contracts`.`date`,'нет даты'),' - ',":'').
			($showPayIdInName?"ifnull(`contracts`.`pay_id`,''),":'').
			"`contracts`.`name`,' - ',".
			"ifnull(`contracts`.`comment`,'')".
			")";
	
	
		$sort=[
			'attributes'=>[
				'date',
				'name'=>[
					'asc'=>[$nameSort=>SORT_ASC],
					'desc'=>[$nameSort=>SORT_DESC],
				],
				'pay_id',
				'total',
				'charge',
				'updated_at'
			],
			'defaultOrder'=>[
				'date'=>SORT_DESC
			]
		];
	
	
		//кусок запроса с джойнами которые идут у нас в имя и в контрагентов-пользователей
		$joinSubQuery=Contracts::find()
			->select('DISTINCT(contracts.id)')
			->joinWith(['partners','users']);
	
		$joinSubQuery->andFilterWhere(QueryHelper::querySearchString($nameExpression,$this->name));
		$joinSubQuery->andFilterWhere(QueryHelper::querySearchString("CONCAT($partnersExpression)",$this->partners));
		$joinSubQuery->andFilterWhere(QueryHelper::querySearchString('users.Ename',$this->users));
	
	
	
	
		$query
			->andFilterWhere(['contracts.state_id'=>$this->state_id])
			->andFilterWhere(QueryHelper::querySearchString('contracts.date',$this->date))
			->andFilterWhere(QueryHelper::querySearchString('contracts.pay_id',$this->pay_id));
	    
	    if (strlen($this->deliveryStatus)) {
			if ($this->deliveryStatus)
				$query->andWhere('ifnull(techs_delivery,0) + ifnull(materials_delivery,0) + ifnull(lics_delivery,0)>0');
			if (!$this->deliveryStatus)
				$query->andWhere('ifnull(techs_delivery,0) + ifnull(materials_delivery,0) + ifnull(lics_delivery,0)=0');
		}
	    
	    //если мы имеем какой-то фильтр внутри запроса с джойнами, то выполняем его и фильтруем основной запрос по ID
	    if (count($joinSubQuery->where))
	    	$query->andWhere('contracts.id in ('.$joinSubQuery->createCommand()->rawSql.')');
	    
	    
	    $query
		    //->andFilterWhere(\app\helpers\QueryHelper::querySearchString($nameExpression,$this->fullname))
		    ->andFilterWhere(QueryHelper::querySearchNumberOrDate('total',$this->total));

	    return new ActiveDataProvider([
		    'query' => $query,
			'sort' => $sort
	    ]);
    }
}
