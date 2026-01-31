<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\controllers\BaseController;
use common\repositories\task\interfaces\TaskRepositoryInterface;
use common\services\task\TaskServiceInterface;
use common\forms\task\TaskCreateForm;
use common\forms\task\TaskUpdateForm;
use common\helpers\ActivityLogger;

class TaskController extends BaseController {
    private TaskRepositoryInterface $taskRepository;
    private TaskServiceInterface $taskService;

    public function __construct(
        $id, 
        $module, 
        TaskRepositoryInterface $taskRepository,
        TaskServiceInterface $taskService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->taskRepository = $taskRepository;
        $this->taskService = $taskService;
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
            ],
        ];
        return $behaviors;
    }

    public function actionIndex() {
        $param = Yii::$app->request->get();
        $dataProvider = $this->taskRepository->search($param);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate() {
        $model = new TaskCreateForm();
        $model->user_id = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post())) {
            $task = $this->taskService->createTask($model);
            
            if ($task !== null) {
                Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
                return $this->redirect(['view', 'id' => $task->id]);
            }
            
            Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id) {
        $task = $this->taskService->getTask($id, Yii::$app->user->id);
        if (!$task) {
            throw new NotFoundHttpException('Task not found');
        }
        $model = new TaskUpdateForm();
        $model->user_id = Yii::$app->user->id;
        $model->loadFromTask($task);

        $this->activityLogger->log(['message' => 'User update task', 'action' => 'update', 'targetType' => 'task', 'targetId' => $id]);
        if ($model->load(Yii::$app->request->post())) {
            $task = $this->taskService->updateTask($model);
            
            if ($task !== null) {
                Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
                return $this->redirect(['view', 'id' => $task->id]);
            }
            
            Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
        }
        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id) {
        return $this->render('delete', [
            'id' => $id,
        ]);
    }

    public function actionView($id) {
        $task = $this->taskService->getTask($id);
        
        if ($task === null) {
            throw new NotFoundHttpException('Task không tồn tại.');
        }

        return $this->render('view', [
            'model' => $task,
        ]);
    }
}