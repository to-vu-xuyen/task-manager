<?php

namespace common\tests\unit\forms\task;

use Codeception\Test\Unit;
use common\forms\task\TaskUpdateForm;
use common\models\task\Task;
use common\fixtures\task\TaskFixture;
use common\fixtures\user\UserFixture;

class TaskUpdateFormTest extends Unit
{
    protected $tester;

    public function _fixtures()
    {
        return [
            'tasks' => TaskFixture::class,
            'users' => UserFixture::class,
        ];
    }

    public function testUpdateFormValidationRequired()
    {
        $form = new TaskUpdateForm();

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('title', $form->getErrors());
        $this->assertArrayHasKey('user_id', $form->getErrors());
    }


    public function testLoadFormTask(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $form = new TaskUpdateForm();
        $form->loadFromTask($task);
        $this->assertEquals($task->title, $form->title);
        $this->assertEquals($task->description, $form->description);
        $this->assertEquals($task->content, $form->content);
        $this->assertEquals($task->due_at, $form->due_at);
        $this->assertEquals($task->user_id, $form->user_id);
        $this->assertEquals($task->assignee_id, $form->assignee_id);
    }
    

    public function testUpdateFormStatusInRange(){
        $form = new TaskUpdateForm();
        $form->status = Task::STATUS_PENDING;
        $this->assertTrue($form->validate(['status']));
    }
}