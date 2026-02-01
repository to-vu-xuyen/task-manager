<?php

namespace common\tests\unit\models\task;

use Codeception\Test\Unit;
use common\fixtures\task\TaskFixture;
use common\fixtures\user\UserFixture;
use common\models\task\Task;


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

    public function testGetTaskByFixturesKey()
    {
        $taskData = $this->tester->grabFixture('tasks', 'task_pending_1');

        $this->assertEquals('Task 1', $taskData['title']);
    }

    public function testIsOverDue()
    {
        $task = $this->tester->grabFixture('tasks', 'task_overdue_3');
        $overdueTask = Task::findOne($task['id']);
        $this->assertTrue($overdueTask->isOverdue());
    }

    public function testTask()
    {
        $task = new Task();
        $task->title = 'Test Task';
        $task->description = 'Test Description';
        $task->status = 'pending';
        $task->due_at = '2022-01-01';
        $task->user_id = 1;
        $task->assignee_id = 1;
        $this->assertTrue($task->save());
    }
}