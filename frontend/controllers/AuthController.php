<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\forms\user\UserLoginForm;
use common\forms\user\UserCreateForm;
use common\services\user\AuthService;
use common\services\user\CreateByUser;
use common\services\user\AuthServiceInterface;


class AuthController extends Controller{
	private AuthServiceInterface $authService;

	// public function __construct(AuthServiceInterface $authService){
	// 	$this->authService = $authService;
	// }
    public function __construct($id, $module, AuthServiceInterface $authService, $config = [])
    {
        $this->authService = $authService;  // DI tự inject
        parent::__construct($id, $module, $config);
    }

	public function actionIndex(){
		return $this->redirect(['auth/login']);
	}

	public function actionLogin(){
        $form = new UserLoginForm();
        if ($form->load(Yii::$app->request->post()) && $this->authService->login($form)) {
            return $this->goHome();
        }
        return $this->render('login', compact('form'));
    }


    public function actionSignup(){
    	$this->view->title = "Signup";
        $model = new UserCreateForm();

        if ($model->load(Yii::$app->request->post())) {
            $user = $this->authService->signup($model);
            if ($user) {
                Yii::$app->user->login($user);
                return $this->goHome();
            }
        }
        return $this->render('signup', [
        	'model' => $model,
        ]);
    }


    public function actionLogout(){
        $this->authService->logout();
        return $this->goHome();
    }

}