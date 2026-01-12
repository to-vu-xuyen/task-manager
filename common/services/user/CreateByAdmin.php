<?php
namespace common\services\user;

use Yii;
use common\models\User;
use common\forms\user\UserCreateForm;
use common\services\user\AbstractCreateUser;

class CreateByAdmin extends AbstractCreateUser
{
    // protected UserCreateForm $form;

    public function __construct(UserCreateForm $form){
        parent::__construct($form);
    }

    protected function assignRole(): void {
        $auth = \Yii::$app->authManager;
        $roleName = $this->form->roleName;
        
        $role = $auth->getRole($roleName);
        if ($role === null) {
            throw new \RuntimeException('Role not found');
        }
        $auth->assign($role, $this->user->id);
    }

}
