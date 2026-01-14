<?php
namespace common\forms\user;

use yii\base\Model;
use common\models\user\User;

class UserLoginForm extends Model{

    public $username;
    public $password;
    public $rememberMe = true;
	protected User $user;

    public function rules(){
        return [
            [['username', 'email', 'password'], 'required'],
            ['email', 'email'],
            ['password', 'string', 'min' => 6],

            ['password', 'validatePassword'],
        ];
    }



    public function validatePassword($attribute, $params) {
        $user = $this->getUser();
        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Sai mật khẩu');
        }
    }


    public function getUser(){
        if ($this->user === null) {
            $this->user = User::findByUsername($this->username);
        }

        return $this->user;
    }
}