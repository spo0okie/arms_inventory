<?php

namespace app\models\ui;

use app\models\traits\AttributeDataModelTrait;
use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 * @property string $phone номер куда отправлять
 * @property string $text что отправлять
 */
class SmsForm extends Model
{
	use AttributeDataModelTrait;
	
	public $phone;
	public $text;
	public $response;
	

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
        	['phone', 'filter', 'filter' => function ($value) {
				//убираем пробелы по краям
        		$value=trim($value);

				/* убираем посторонние символы из номера*/
				$value=preg_replace('/[^0-9]/','',$value);
				return $value;
			}],
        	['phone','string', 'min'=>11, 'max'=>11,
				'tooShort'=>'Номер должен быть ровно 11 знаков без пробелов',
				'tooLong'=>'Номер должен быть ровно 11 знаков без пробелов',
			],
			['text','string', 'max'=>128, 'tooShort'=>'Дофига длинно. Давай покороче'],
        ];
    }

    public function attributeData()
	{
		return [
			'phone'=>'Номер телефона',
			'text'=>'Текст сообщения'
		];
	}
	
    public function send()
    {
		$url=Yii::$app->params['sms.url'];
		$url=str_replace('{phone}',$this->phone,$url);
		$url=str_replace('{text}',urlencode($this->text),$url);
		
		$arrContextOptions = [
			"ssl" => ["verify_peer" => false,"verify_peer_name" => false,],
		];
		$this->response=@file_get_contents($url,false,stream_context_create($arrContextOptions));
    }

}
