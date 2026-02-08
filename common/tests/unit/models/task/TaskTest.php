<?php

namespace common\tests\unit\models\task;

use Yii;
use Codeception\Test\Unit;

use common\fixtures\task\TaskFixture;
use common\fixtures\UserFixture;
use common\models\task\Task;
use common\dto\task\TaskDto;
use common\services\task\TaskServiceInterface;
use common\forms\task\TaskCreateForm;


class TaskTest extends Unit
{
    protected $tester;

    public function _fixtures()
    {
        return [
            'tasks' => TaskFixture::class,
            'users' => UserFixture::class,
        ];
    }

    public function testIsOverDue()
    {
        $taskOverDue = $this->tester->grabFixture('tasks', 'task_overdue_1');
        // $overdueTask = Task::findOne($task['id']);
        $this->assertTrue($taskOverDue->isOverdue());
    }
    public function testIsOverdueWithNullDueDate()
    {
        $taskPending = $this->tester->grabFixture('tasks', 'task_pending_1');
        $taskPending->due_at = null;
        $this->assertFalse($taskPending->isOverdue());
    }

    public function testIsNotOverDue()
    {
        $taskPending = $this->tester->grabFixture('tasks', 'task_pending_1');
        $this->assertFalse($taskPending->isOverdue());
    }

    public function testIsOverdueWhenCompleted()
    {
        $taskCompleted = $this->tester->grabFixture('tasks', 'task_completed_1');
        $this->assertFalse($taskCompleted->isOverdue());
    }

    public function testValidationStatusInRange()
    {
        // Work in progress
        $task = new Task();
        $task->status = 4;
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('status', $task->getErrors());
    }

    public function testIsCompleted()
    {
        $taskCompleted = $this->tester->grabFixture('tasks', 'task_completed_1');
        $this->assertTrue($taskCompleted->isCompleted());
    }

    public function testIsNotCompleted()
    {
        $taskPending = $this->tester->grabFixture('tasks', 'task_pending_1');
        $this->assertFalse($taskPending->isCompleted());
    }

    public function testValidationRequired()
    {
        $task = new Task();
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('title', $task->getErrors());
        $this->assertArrayHasKey('user_id', $task->getErrors());
    }

    public function testSoftDelete()
    {
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $task->softDelete();
        $this->assertNotNull($task->deleted_at);
        $this->assertEquals(Task::STATUS_DELETED, $task->status);
    }

    public function testServiceSoftDelete()
    {
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $service->delete($task->id);
        $task = $service->getById($task->id);
        $this->assertNotNull($task->deleted_at);
        $this->assertEquals(Task::STATUS_DELETED, $task->status);
    }

    public function testIsSoftDelete()
    {
        $task = $this->tester->grabFixture('tasks', 'task_deleted_1');
        $this->assertNotNull($task->deleted_at);
        $this->assertEquals(Task::STATUS_DELETED, $task->status);
    }

    public function testTaskUserRelation()
    {
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $this->assertEquals('bayer.hudson', $task->user->username);
    }


    public function testSaveTask()
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