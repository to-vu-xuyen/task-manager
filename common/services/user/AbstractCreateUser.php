<?php
namespace common\services\user;

use common\models\User;
use common\forms\user\UserCreateForm;

abstract class AbstractCreateUser
{
    protected UserCreateForm $form;
    protected User $user;


    public function __construct(UserCreateForm $form){
        $this->form = $form;
        $this->user = $this->setUserInstance();
    }

    protected function setUserInstance(): User{
        return new User();
    }


    public function create(): User
    {
        $this->loadData();
        $this->beforeSave();
        if (!$this->user->validate()) {
            throw new \RuntimeException('Cannot validate user');
        }
        if (!$this->user->save()) {
            throw new \RuntimeException('Cannot save user');
        }
        $this->assignRole();
        $this->afterSave();

        return $this->user;
    }

    private function loadData(): void{
        $this->user->username = $this->form->username;
        $this->user->email = $this->form->email;
        $this->user->setPassword($this->form->password);

    }

    protected function beforeSave(): void{}
    protected function afterSave(): void{}

    abstract protected function assignRole(): void;
}