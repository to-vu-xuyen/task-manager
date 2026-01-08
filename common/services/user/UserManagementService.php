<?php
namespace common\services\user;

use Yii;
use common\models\User;
use common\forms\user\UserCreateForm;

class UserCreateService{

    public function registerByUser(UserCreateForm $form): User{
        if (!$form->validate()) {
            throw new Exception('User signup form is invalid');
        }
        $trans = Yii::$app->db->beginTransaction();
        
    }


    private function createUser(UserCreateForm $form): User{
        

        $user = new User();
        $user->username = $form->username;
        $user->email = $form->email;
        
        $user->setPassword($form->password);

        if (!$user->save()) {
            throw new \RuntimeException('Cannot save user');
        }

        return $user;
    }
}
