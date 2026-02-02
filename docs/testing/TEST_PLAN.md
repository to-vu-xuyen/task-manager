# 📋 Test Plan - Task Manager

## Tổng Quan

Document này mô tả các test cases cần viết cho module Task trong project Task Manager (Yii2).

---

## 1. Unit Tests

### 1.1 Model Tests (`TaskTest.php`)

Location: `common/tests/unit/models/task/TaskTest.php`

| # | Test Case | Method | Mô tả | Priority |
|---|-----------|--------|-------|----------|
| 1 | ✅ Get by fixture key | `testGetTaskByFixturesKey` | Lấy task từ fixture | Done |
| 2 | ✅ Is overdue | `testIsOverDue` | Kiểm tra task quá hạn | Done |
| 3 | ⏳ Validation required | `testValidationRequired` | title, user_id bắt buộc | High |
| 4 | ⏳ Validation status | `testValidationStatusInvalid` | Status phải hợp lệ | High |
| 5 | ⏳ Soft delete | `testSoftDelete` | delete() chỉ set deleted_at | High |
| 6 | ⏳ Relation user | `testRelationUser` | $task->user trả về User | Medium |
| 7 | ⏳ Relation assignee | `testRelationAssignee` | $task->assignee trả về User | Medium |
| 8 | ⏳ Scope active | `testScopeActive` | Query chỉ lấy task active | Medium |

---

### 1.2 Service Tests (`TaskServiceTest.php`)

Location: `common/tests/unit/services/task/TaskServiceTest.php`

| # | Test Case | Method | Mô tả | Priority |
|---|-----------|--------|-------|----------|
| 1 | ⏳ Create task | `testCreateTask` | Tạo task từ DTO | High |
| 2 | ⏳ Create task invalid | `testCreateTaskInvalidData` | Validation fail | High |
| 3 | ⏳ Update task | `testUpdateTask` | Cập nhật task | High |
| 4 | ⏳ Delete task | `testDeleteTask` | Soft delete | High |
| 5 | ⏳ Assign task | `testAssignTask` | Gán assignee | Medium |
| 6 | ⏳ Change status | `testChangeStatus` | Đổi status | Medium |
| 7 | ⏳ Get by id | `testGetById` | Lấy task theo id | Medium |

---

### 1.3 Repository Tests (`TaskRepositoryTest.php`)

Location: `common/tests/unit/repositories/task/TaskRepositoryTest.php`

| # | Test Case | Method | Mô tả | Priority |
|---|-----------|--------|-------|----------|
| 1 | ⏳ Find by id | `testFindById` | Tìm task theo id | High |
| 2 | ⏳ Find by user | `testFindByUserId` | Tất cả task của user | High |
| 3 | ⏳ Save new | `testSaveNew` | Lưu task mới | High |
| 4 | ⏳ Save update | `testSaveUpdate` | Cập nhật task | High |
| 5 | ⏳ Delete | `testDelete` | Soft delete | High |
| 6 | ⏳ Find with pagination | `testFindWithPagination` | Phân trang | Medium |

---

## 2. Functional Tests

### 2.1 Controller Tests (`TaskControllerTest.php`)

Location: `frontend/tests/functional/TaskControllerCest.php`

| # | Test Case | Method | Mô tả | Priority |
|---|-----------|--------|-------|----------|
| 1 | ⏳ View task as owner | `testViewOwnTask` | Xem task của mình | High |
| 2 | ⏳ View task as admin | `testViewAnyTaskAsAdmin` | Admin xem mọi task | High |
| 3 | ⏳ View task unauthorized | `testViewTaskUnauthorized` | Không có quyền | High |
| 4 | ⏳ Create task | `testCreateTask` | Tạo task mới | High |
| 5 | ⏳ Create without login | `testCreateTaskRedirectLogin` | Redirect về login | High |
| 6 | ⏳ Update task | `testUpdateTask` | Cập nhật task | Medium |
| 7 | ⏳ Delete task | `testDeleteTask` | Xóa task | Medium |
| 8 | ⏳ List tasks pagination | `testListTasksPagination` | Danh sách + phân trang | Medium |

---

## 3. Code Examples

### 3.1 Validation Test Example

```php
public function testValidationRequired()
{
    $task = new Task();
    
    // Không set title và user_id
    $this->assertFalse($task->validate());
    $this->assertArrayHasKey('title', $task->getErrors());
    $this->assertArrayHasKey('user_id', $task->getErrors());
}

public function testValidationStatusInvalid()
{
    $task = new Task();
    $task->title = 'Test';
    $task->user_id = 1;
    $task->status = 'invalid_status';
    
    $this->assertFalse($task->validate());
    $this->assertArrayHasKey('status', $task->getErrors());
}
```

### 3.2 Soft Delete Test Example

```php
public function testSoftDelete()
{
    $task = $this->tester->grabFixture('tasks', 'task_pending_1');
    $taskModel = Task::findOne($task['id']);
    
    // Soft delete
    $taskModel->delete();
    
    // Vẫn tồn tại trong DB nhưng có deleted_at
    $deletedTask = Task::find()->where(['id' => $task['id']])->one();
    $this->assertNotNull($deletedTask);
    $this->assertNotNull($deletedTask->deleted_at);
}
```

### 3.3 Service Test Example (với Mock)

```php
public function testCreateTask()
{
    $service = Yii::$container->get(TaskServiceInterface::class);
    
    $dto = new TaskCreateDto([
        'title' => 'New Task',
        'description' => 'Description',
        'user_id' => 1,
    ]);
    
    $result = $service->create($dto);
    
    $this->assertInstanceOf(TaskDto::class, $result);
    $this->assertEquals('New Task', $result->title);
}
```

---

## 4. Fixtures Cần Thêm

### 4.1 Task Fixtures (`task.php`)

Thêm các scenarios:

```php
'task_completed_1' => [
    'id' => 6,
    'title' => 'Completed Task',
    'status' => 'completed',
    // ...
],
'task_with_assignee' => [
    'id' => 7,
    'title' => 'Assigned Task',
    'user_id' => 1,
    'assignee_id' => 2,  // Cần thêm user_2
    // ...
],
```

### 4.2 User Fixtures (`user.php`)

Thêm thêm users:

```php
'user_2' => [
    'id' => 2,
    'username' => 'jane.doe',
    // ...
],
'admin_user' => [
    'id' => 3,
    'username' => 'admin',
    // ... với role admin
],
```

---

## 5. Commands Chạy Test

```bash
# Chạy tất cả unit tests trong common
vendor/bin/codecept run unit -c common

# Chạy với debug
vendor/bin/codecept run unit -c common --debug

# Chạy file cụ thể
vendor/bin/codecept run unit -c common tests/unit/models/task/TaskTest.php

# Chạy method cụ thể
vendor/bin/codecept run unit -c common tests/unit/models/task/TaskTest.php:testIsOverDue

# Chạy với coverage
vendor/bin/codecept run unit -c common --coverage --coverage-html
```

---

## 6. Checklist Setup Test Environment

- [x] Database test đã tạo (`task_manager_test`)
- [x] Config `test-local.php` đầy đủ (common + console)
- [x] RBAC migrations đã apply
- [x] UserFixture có dataFile
- [x] TaskFixture có dataFile
- [ ] Code coverage setup (optional)

---

## Changelog

| Date | Author | Changes |
|------|--------|---------|
| 2026-02-02 | AI Assistant | Initial test plan created |
