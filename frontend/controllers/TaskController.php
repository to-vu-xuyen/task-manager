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
use common\services\task\TaskAttachmentServiceInterface;
use common\forms\task\TaskCreateForm;
use common\forms\task\TaskUpdateForm;
use common\forms\task\TaskAttachmentForm;
use yii\web\UploadedFile;
use common\helpers\ActivityLogger;

class TaskController extends BaseController
{
    // private TaskRepositoryInterface $taskRepository;
    private TaskServiceInterface $taskService;
    private TaskAttachmentServiceInterface $taskAttachmentService;

    public function __construct(
        $id,
        $module,
        TaskAttachmentServiceInterface $taskAttachmentService,
        TaskServiceInterface $taskService,
        $config = []
    ) {
        parent::__construct($id, $module, $config);
        $this->taskAttachmentService = $taskAttachmentService;
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

    public function actionIndex()
    {
        $param = Yii::$app->request->get();
        $dataProvider = $this->taskService->search($param);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionCreate()
    {
        $model = new TaskCreateForm();
        $model->user_id = Yii::$app->user->id;

        if ($model->load(Yii::$app->request->post())) {
            $task = $this->taskService->create($model);

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

    public function actionUpdate($id)
    {
        $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if (!$task) {
            throw new NotFoundHttpException('Task not found');
        }
        $model = new TaskUpdateForm();
        $model->user_id = Yii::$app->user->id;
        $model->loadFromTask($task);

        $this->activityLogger->log(['message' => 'User update task', 'action' => 'update', 'targetType' => 'task', 'targetId' => $id]);
        if ($model->load(Yii::$app->request->post())) {
            $task = $this->taskService->update($id, $model);

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

    public function actionDelete($id)
    {
        return $this->render('delete', [
            'id' => $id,
        ]);
    }

    public function actionView($id)
    {
        $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);

        if ($task === null) {
            throw new NotFoundHttpException('Task không tồn tại.');
        }

        return $this->render('view', [
            'model' => $task,
        ]);
    }

    public function actionUploadAttachment($taskId)
    {
        $task = $this->taskService->getByIdForUser($taskId, Yii::$app->user->id);
        if (!$task) {
            throw new NotFoundHttpException('Task not found');
        }

        $model = new TaskAttachmentForm();
        $model->user_id = Yii::$app->user->id;
        $model->task_id = $taskId;
        $model->files = UploadedFile::getInstances($model, 'files');
        if ($model->load(Yii::$app->request->post())) {
            $taskAttachment = $this->taskAttachmentService->upload($model);
            if ($taskAttachment !== null) {
                Yii::$app->session->setFlash('success', 'Task đã được tạo thành công!');
                return $this->redirect(['view', 'id' => $taskId]);
            }
            Yii::$app->session->setFlash('error', 'Có lỗi xảy ra khi tạo task.');
        }
        return $this->render('upload-attachment', [
            'model' => $model,
        ]);
    }

    public function actionDeleteAttachment($id)
    {
        $this->taskAttachmentService->deleteAttachment($id);
        return $this->redirect(['view', 'id' => $id]);
    }

    public function actionDownloadAttachment($id)
    {

    }
}