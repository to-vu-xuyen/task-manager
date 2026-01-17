<?php

namespace common\services\user;

use common\services\user\CreateByUser;
use common\forms\user\UserCreateForm;

class AuthService implements AuthServiceInterface{

    private CreateByUser $createUserService;

    public function __construct(CreateByUser $createUserService){
        $this->createUserService = $createUserService;
    }

	public function login(UserLoginForm $form): bool{
        return $this->render('login');
    }

    public function signup(UserSignupForm $form): ?User{
        if (!$form->validate()) {
            return null;
        }


        $createForm = new UserCreateForm();
        $createForm->username = $form->username;
        $createForm->email = $form->email;
        $createForm->setPassword($form->password);

        $this->createUserService->create();
        return $this->render('signup', [

        ]);
    }

    public function logout(): void{
        Yii::$app->user->logout;
    }
}