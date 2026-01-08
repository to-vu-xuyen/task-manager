<?php
namespace common\services\user;

use common\models\User;
use common\forms\user\UserCreateForm;
use common\services\user\AbstractCreateUser;

class CreateByAdmin extends AbstractCreateUser
{
    public function create()
    {
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
