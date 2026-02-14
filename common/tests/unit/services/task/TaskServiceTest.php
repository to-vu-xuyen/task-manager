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
use common\forms\task\TaskUpdateForm;


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

    

    public function testServiceSoftDelete()
    {
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $service->delete($task->id);
        $task = $service->getById($task->id);
        $this->assertNotNull($task->deleted_at);
        $this->assertEquals(Task::STATUS_DELETED, $task->status);
    }

    public function testUpdateTask(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');

        $form = new TaskUpdateForm();
        
        $form->loadFromTask($task);
        
        // codecept_debug($form->title);
        

        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->update($task->id, $form);

        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals($form->title, $result->title);
        $this->assertEquals($form->description, $result->description);
        $this->assertEquals($form->content, $result->content);
        $this->assertEquals(Task::STATUS_PENDING, $result->status);
        $this->assertEquals($form->due_at, $result->due_at);
        $this->assertEquals($form->user_id, $result->user_id);
        $this->assertEquals($form->assignee_id, $result->assignee_id);
    }

    public function testUpdateTaskWithInvalidData(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');

        
        $form = new TaskUpdateForm();
        $form->loadFromTask($task);
        $form->title = '';
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->update($task->id, $form);
        $this->assertNull($result);
        $this->assertArrayHasKey('title', $form->getErrors());
        // $this->assertFalse($result);
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


    public function testGetById(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->getById($task->id);
        $this->assertInstanceOf(Task::class, $result);
        $this->assertEquals($task->id, $result->id);
    }

    public function testGetByUserId(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->getByUserId(1);

        foreach($result as $item){
            $this->assertInstanceOf(Task::class, $item);
            $this->assertEquals($item->user_id, 1);
        }
    }


    public function testGetByAssigneeId(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->getByAssigneeId(1);

        $this->assertIsArray($result);
        foreach($result as $item){
            $this->assertInstanceOf(Task::class, $item);
            $this->assertEquals($item->assignee_id, 1);
        }
    }

    public function testChangeStatus(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->changeStatus($task->id, Task::STATUS_IN_PROGRESS);
        $this->assertTrue($result);
        $task = $service->getById($task->id);
        $this->assertEquals(Task::STATUS_IN_PROGRESS, $task->status);
    }

    public function testChangeStatusWithInvalidData(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->changeStatus($task->id, 'invalid_status');
        $this->assertFalse($result);
    }

    public function testDelete(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->delete($task->id);
        codecept_debug($result);
        $this->assertTrue($result);
        $task = $service->getById($task->id);
        codecept_debug($task);
        $this->assertNull($task);
    }

    public function testDeleteWithInvalidData(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->delete($task->id);
        $this->assertFalse($result);
    }

    public function testDeleteNonExistentTask(){
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->delete(999999);
        $this->assertFalse($result);
    }

    public function testDeleteAlreadyDeletedTask(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $service->delete($task->id);
        $result = $service->delete($task->id);
        $this->assertFalse($result);
    }

    public function testChangeStatusNonExistentTask(){
        $service = Yii::$container->get(TaskServiceInterface::class);
        $result = $service->changeStatus(999999, Task::STATUS_IN_PROGRESS);
        $this->assertFalse($result);
    }

    public function testChangeStatusAlreadyDeletedTask(){
        $task = $this->tester->grabFixture('tasks', 'task_pending_1');
        $service = Yii::$container->get(TaskServiceInterface::class);
        $service->delete($task->id);
        
        $result = $service->changeStatus($task->id, Task::STATUS_IN_PROGRESS);
        $this->assertFalse($result);
    }
    


}