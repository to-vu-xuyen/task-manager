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

    public function testValidationTitleMaxLength()
    {
        $task = new Task();
        $task->title = str_repeat('a', 256);
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('title', $task->getErrors());
    }

    public function testValidationDescriptionMaxLength()
    {
        $task = new Task();
        $task->description = str_repeat('a', 256);
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('description', $task->getErrors());
    }

    public function testValidationContentMaxLength()
    {
        $task = new Task();
        $task->content = str_repeat('a', 256);
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('content', $task->getErrors());
    }

    public function testStatusDefault()
    {
        $task = new Task();
        $this->assertEquals(Task::STATUS_PENDING, $task->status);
    }

    public function testValidationStatusInRange()
    {
        $task = new Task();
        $task->status = 'invalid_status';
        $this->assertFalse($task->validate());
        $this->assertArrayHasKey('status', $task->getErrors());
    }

    public function testStatusValid(){
        $task = new Task();
        $task->title = 'Test Task';
        $task->user_id = 1;
        $task->status = Task::STATUS_ACTIVE;
        $this->assertTrue($task->validate(['status']));
    }

    public function testAssigneeRelation()
    {
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $this->assertEquals('bayer.hudson', $task->assignee->username);
        $this->assertTrue($task->assignee == null || $task->assignee instanceof User );
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

}