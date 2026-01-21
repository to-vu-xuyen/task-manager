<?php

namespace backend\controllers;

use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use common\forms\user\UserLoginForm;
use common\models\User;
use common\models\LoginForm;
use common\forms\user\UserCreateForm;
use common\services\user\UserService;
use common\services\user\UserServiceInterface;
use common\services\user\auth\AuthService;
use common\services\user\auth\AuthServiceInterface;


class SiteController extends Controller {
    
    private UserServiceInterface $userService;
    private AuthServiceInterface $authService;
    
    public function __construct($id, $module, ?UserServiceInterface $userService = null, ?AuthServiceInterface $authService = null, $config = []) {
        $this->userService = $userService ?? new UserService();
        $this->authService = $authService ?? new AuthService();
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors() {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'first-time', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions() {
        return [
            'error' => [
                'class' => \yii\web\ErrorAction::class,
            ],
        ];
    }

    /**
     * Displays homepage.
     */
    public function actionIndex(): string {
        return $this->render('index');
    }

    /**
     * Login action.
     */
    public function actionLogin(): string|Response {

        // $auth = Yii::$app->authManager;
        // $role = $auth->getRole('admin'); 
        // $auth->revoke($role, 1);


        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        if ($this->isFirstTimeSetup()) {
            return $this->redirect(['site/first-time']);
        }

        $this->layout = 'blank';

        $model = new UserLoginForm();
        if ($model->load(Yii::$app->request->post()) && $this->authService->login($model)) {
            return $this->goBack();
        }

        $model->password = '';

        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     */
    public function actionLogout(): Response {
        Yii::$app->user->logout();
        return $this->goHome();
    }

    /**
     * Tạo user lần đầu tiên nếu chưa có admin.
     */
    public function actionFirstTime(): string|Response {
        $form = new UserCreateForm();
        $form->username = 'admin';
        $form->role = 'admin';
        $form->status = User::STATUS_ACTIVE;

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $user = $this->userService->createUser($form, 'admin');
                
                if ($user) {
                    Yii::$app->user->login($user);
                    Yii::$app->session->setFlash('success', 'Tạo tài khoản admin thành công!');
                    return $this->redirect(['site/index']);
                }
            } catch (\Exception $e) {
                Yii::$app->session->setFlash('error', 'Không thể tạo tài khoản: ' . $e->getMessage());
            }
        }

        return $this->render('first-time', [
            'model' => $form,
        ]);
    }
    
    /**
     * Check if this is first time setup (no admin exists).
     */
    private function isFirstTimeSetup(): bool {
        return empty(Yii::$app->authManager->getUserIdsByRole('admin'));
    }
}
