<?php
namespace common\forms\user;

use yii\base\Model;

class UserCreateForm extends Model
{
    public $username;
    public $email;
    public $password;
    public $password_confirm;
    public $role;

    public function rules()
    {
        return [
            [['username', 'email', 'password'], 'required'],
            ['email', 'email'],
            ['password', 'string', 'min' => 6],
            ['password_confirm', 'compare', 'compareAttribute' => 'password', 'skipOnEmpty' => false],
        ];
    }
}