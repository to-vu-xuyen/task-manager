<?php

namespace common\tests\unit\forms\task;

use Codeception\Test\Unit;
use common\forms\task\TaskCreateForm;
use common\fixtures\task\TaskFixture;
use common\fixtures\UserFixture;

class TaskCreateFormTest extends Unit
{
    
    protected $tester;

    public function _fixtures()
    {
        return [
            'tasks' => TaskFixture::class,
            'users' => UserFixture::class,
        ];
    }

    public function testCreateFormValidationRequired()
    {
        $form = new TaskCreateForm();

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('title', $form->getErrors());
        $this->assertArrayHasKey('user_id', $form->getErrors());
    }
     
    public function testValidationSuccess()
    {
        $form = new TaskCreateForm();
        $form->title = 'Test Task';
        $form->description = 'Test Description';
        $form->content = 'Test Content';
        $form->due_at = date('Y-m-d H:i:s');
        $form->user_id = 1;
        $form->assignee_id = 1;

        $this->assertTrue($form->validate());
    }

    public function testValidationFailure()
    {
        $form = new TaskCreateForm();
        $form->title = '';
        $form->description = 'Test Description';
        $form->content = 'Test Content';
        $form->due_at = date('Y-m-d H:i:s');
        $form->user_id = 1;
        $form->assignee_id = 1;

        $this->assertFalse($form->validate());
    }

    
}