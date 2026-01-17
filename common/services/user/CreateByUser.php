<?php
namespace common\services\user;

use Yii;
use common\models\User;
use common\forms\user\UserCreateForm;
use common\services\user\AbstractCreateUser;

class CreateByUser extends AbstractCreateUser
{
    public function __construct(UserCreateForm $form){
        parent::__construct($form);
    }

    protected function assignRole(): void {
        $auth = Yii::$app->authManager;
        
        $role = $auth->getRole('user');
        $auth->assign($role, $this->user->id);
    }
}
