<?php
namespace common\forms\user;

use yii\base\Model;

/**
 * UserLoginForm - Form validate input cho login
 * 
 * Chỉ validate FORMAT (required, min length).
 * Business logic (xác thực credentials) do AuthService xử lý.
 */
class UserLoginForm extends Model {

    public $username;
    public $password;
    public $rememberMe = true;

    public function rules() {
        return [
            [['username', 'password'], 'required'],
            ['password', 'string', 'min' => 6],
            ['rememberMe', 'boolean'],
        ];
    }
    
    public function attributeLabels() {
        return [
            'username' => 'Tên đăng nhập',
            'password' => 'Mật khẩu',
            'rememberMe' => 'Ghi nhớ đăng nhập',
        ];
    }
}