<?php

namespace api\modules\v1\controllers;

use Yii;
use common\services\task\TaskServiceInterface;
use common\forms\task\TaskCreateForm;
use common\forms\task\TaskUpdateForm;
use common\dto\task\TaskDto;
use common\models\task\Task;

class TaskController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        TaskServiceInterface $taskService,
        $config = []
    ) {
        $this->taskService = $taskService;
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): array
    {
        $params = Yii::$app->request->queryParams;
        $dataProvider = $this->taskService->search($params);

        $models = $dataProvider->getModels();
        // $items = array_map([$this, 'formatTask'], $models);
        // $items = array_map([TaskDto::class, 'fromModels'], $models);
        $items = array_map(fn(Task $task) => TaskDto::fromModel($task)->toArray(), $models);

        $pagination = $dataProvider->getPagination();
        return $this->paginated(
            $items,
            $pagination->totalCount,
            $pagination->getPage() + 1,
            $pagination->getPageSize()
        );
    }


    public function actionCreate()
    {
        $form = new TaskCreateForm();
        $form->load(Yii::$app->request->getBodyParams(), '');
        $form->user_id = Yii::$app->user->id;

        $task = $this->taskService->create($form);

        if ($task === null) {
            return $this->error('Validation failed', 422, $form->getErrors());
        }

        return $this->success(TaskDto::fromModel($task)->toArray(), 201);
    }

    public function actionUpdate(int $id): array
    {
        $existing = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($existing === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $form = new TaskUpdateForm();
        $form->load(Yii::$app->request->getBodyParams(), '');

        $task = $this->taskService->update($id, $form);

        if ($task === null) {
            return $this->error('Validation failed', 422, $form->getErrors());
        }

        // return $this->success($this->formatTask($task));
        return $this->success(TaskDto::fromModel($task)->toArray());
    }


    public function actionDelete(int $id): array
    {
        $existing = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($existing === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $result = $this->taskService->delete($id);

        if (!$result) {
            return $this->error('Không thể xóa task', 500);
        }

        return $this->success(['message' => 'Task đã bị xóa']);
    }


    private function formatTask($task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'content' => $task->content,
            'status' => $task->status,
            'user_id' => $task->user_id,
            'assignee_id' => $task->assignee_id,
            'due_at' => $task->due_at,
            'is_overdue' => $task->isOverdue(),
            'created_at' => $task->created_at,
            'updated_at' => $task->updated_at,
        ];
    }
}