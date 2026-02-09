<?php

namespace common\tests\unit\services;

use Yii;
use Codeception\Test\Unit;

use common\fixtures\task\TaskFixture;
use common\fixtures\UserFixture;
use common\models\task\Task;
use common\dto\task\TaskDto;
use common\services\task\TaskServiceInterface;
use common\forms\task\TaskCreateForm;


class TaskServiceTest extends Unit
{
    protected $tester;

    public function _fixtures()
    {
        return [
            'tasks' => TaskFixture::class,
            'users' => UserFixture::class,
        ];
    }

    public function testCreateTask()
    {
        $form = new TaskCreateForm();
        $form->title = 'New Task 1';
        $form->description = 'New Description 1';
        $form->content = 'New Content 1';
        // $form->status = Task::STATUS_PENDING;
        $form->due_at = '2028-01-01 00:00:00';
        $form->user_id = 1;
        $form->assignee_id = 1;

        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->create($form);

        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals($form->title, $result->title);
        $this->assertEquals($form->description, $result->description);
        $this->assertEquals($form->content, $result->content);
        $this->assertEquals(Task::STATUS_PENDING, $result->status);
        $this->assertEquals($form->due_at, $result->due_at);
        $this->assertEquals($form->user_id, $result->user_id);
        $this->assertEquals($form->assignee_id, $result->assignee_id);

    }
}