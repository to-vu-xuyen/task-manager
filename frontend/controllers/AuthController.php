<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\forms\user\UserLoginForm;
use common\forms\user\UserCreateForm;
// NEW: Updated namespaces
use common\services\user\auth\AuthService;
use common\services\user\auth\AuthServiceInterface;
use common\services\user\UserService;
use common\services\user\UserServiceInterface;


class AuthController extends Controller
{
    private AuthServiceInterface $authService;
    private UserServiceInterface $userService;

    /**
     * Constructor với DI
     * 
     * AuthService: Login/Logout
     * UserService: Create users (signup)
     */
    public function __construct(
        $id, 
        $module, 
        AuthServiceInterface $authService, 
        UserServiceInterface $userService,
        $config = []
    ) {
        $this->authService = $authService;
        $this->userService = $userService;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex()
    {
        return $this->redirect(['auth/login']);
    }

    public function actionLogin()
    {
        $form = new UserLoginForm();
        if ($form->load(Yii::$app->request->post()) && $this->authService->login($form)) {
            return $this->goHome();
        }
        return $this->render('login', compact('form'));
    }


    /**
     * Signup - sử dụng UserService thay vì AuthService
     */
    public function actionSignup()
    {
        $this->view->title = "Signup";
        $model = new UserCreateForm();

        if ($model->load(Yii::$app->request->post())) {
            // NEW: Sử dụng UserService facade
            $user = $this->userService->createUser($model, 'user');
            if ($user) {
                Yii::$app->user->login($user);
                return $this->goHome();
            }
        }
        return $this->render('signup', [
            'model' => $model,
        ]);
    }


    public function actionLogout()
    {
        $this->authService->logout();
        return $this->goHome();
    }

}