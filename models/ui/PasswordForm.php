<?php

namespace app\models\ui;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class PasswordForm extends Model
{
	public $user_id;
	public $password;
    public $passwordRepeat;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
        	['user_id','integer'],
            [['passwordRepeat', 'password'], 'required'],
			['password','string', 'min'=>6, 'max'=>64, 'tooShort'=>'Password is too short (minimum is 6 characters)'],
            ['passwordRepeat', 'validatePassword'],
        ];
    }

    public function attributeLabels()
	{
		return [
			'passwordRepeat'=>'Repeat',
		];
	}
	
	/**
     * Validates the password repeat.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            if ($this->passwordRepeat != $this->password) {
                $this->addError($attribute, 'Repeat password mismatch');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function update()
    {
    	if (!$this->validate()) return false;
    	$user=$this->getUser();
		$user->setPassword($this->password);
	    
        return $user->save();
    }

    /**
     * Finds user by [[username]]
     *
     * @return \app\models\Users
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = \app\models\Users::findOne($this->user_id);
        }

        return $this->_user;
    }
}
