<?php

namespace app\models\traits;

use app\models\links\TagsLinks;
use app\models\Tags;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * Трейт для добавления функционала тегов к моделям
 * 
 * Использование:
 * 1. Добавить `use TaggableTrait;` в класс модели
 * 2. Добавить в linksSchema:
 *    'tags_ids' => 'tags_ids' => [Tags::class,'services_ids', 'loader' => 'tags','updater'=>[
 * 		'viaTableAttributesValue' => [
 * 			'model_class' => Services::class,
 * 		],
 * 	  ]],
 * 3. Добавить в rules(): [['tags_ids'], 'safe']
 * 4. Добавить в Tags обратные ссылки в linkSchema и getter-relation
 */
trait TaggableTrait
{
    /**
     * @var array Виртуальный атрибут для работы с формами
     */
    //public $tags_ids;
	
	
	/**
	 * Нам нужно предварительно отфильтровать наш junction table чтобы остались только записи по текущему классу
	 * @return ActiveQuery
	 */
	public function getTagLinks()
	{
		return $this->hasMany(TagsLinks::class, ['model_id' => 'id'])
			->onCondition(['tags_links.model_class' => static::class]);
	}
	
	/**
	 * Связь с тегами через junction table
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getTags()
	{
		return $this->hasMany(Tags::class, ['id' => 'tag_id'])
			->via('tagLinks');
	}
	
}