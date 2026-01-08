<?php
namespace common\services\user;

use common\models\User;
use common\forms\user\UserCreateForm;
use common\services\user\AbstractCreateUser;

class CreateByAdmin extends AbstractCreateUser
{
    // protected UserCreateForm $form;

    public function __construct(UserCreateForm $form){
        parent::__construct($form);
    }


}
