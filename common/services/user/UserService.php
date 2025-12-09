<?php
namespace common\services\user;

use common\models\User;
use common\forms\user\UserCreateForm;

class UserService
{
    public function create(UserCreateForm $form): User
    {
        $user = new User();
        $user->username = $form->username;
        $user->email = $form->email;
        $user->role = $form->role;
        $user->setPassword($form->password);

        if (!$user->save()) {
            throw new \RuntimeException('Cannot save user');
        }

        return $user;
    }
}
