<?php

namespace app\models\links;

use yii\db\ActiveRecord;

class TagsLinks extends ActiveRecord
{
	public static function tableName()
	{
		return 'tags_links';
	}
	
	public function rules()
	{
		return [
			[['model_id', 'tag_id', 'model_class'], 'required'],
			[['model_id', 'tag_id'], 'integer'],
			[['model_class'], 'string', 'max' => 255],
		];
	}
}
