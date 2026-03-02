# URL Routing & Rules Configuration

> **Skills:** api-patterns (rest.md, versioning.md)
> **Nguyên tắc:** Tách biệt routing strategy theo loại controller

---

## URL Routing Strategies trong Yii2 REST API

Yii2 cung cấp 2 cách chính để cấu hình URL rules cho API:

### Strategy 1: `yii\rest\UrlRule` — Auto-generated REST Routes

Tự động tạo tất cả RESTful routes chuẩn cho controller.

```php
[
    'class' => 'yii\rest\UrlRule',
    'controller' => ['v1/task'],
    'pluralize' => true,
]
```

**Routes được tạo tự động:**

| HTTP Method | URL | Action | Mục đích |
|-------------|-----|--------|----------|
| `GET` | `/tasks` | `v1/task/index` | List (có phân trang) |
| `GET` | `/tasks/<id>` | `v1/task/view` | Xem chi tiết |
| `POST` | `/tasks` | `v1/task/create` | Tạo mới |
| `PUT` | `/tasks/<id>` | `v1/task/update` | Cập nhật toàn bộ |
| `PATCH` | `/tasks/<id>` | `v1/task/update` | Cập nhật 1 phần |
| `DELETE` | `/tasks/<id>` | `v1/task/delete` | Xóa |
| `OPTIONS` | `/tasks` | `v1/task/options` | CORS preflight |
| `OPTIONS` | `/tasks/<id>` | `v1/task/options` | CORS preflight |

**Khi nào dùng:** Controller tuân thủ chuẩn REST CRUD (index, view, create, update, delete).

**Options hữu ích:**

```php
[
    'class' => 'yii\rest\UrlRule',
    'controller' => ['v1/task'],
    'pluralize' => true,

    // Chỉ cho phép một số actions
    'only' => ['index', 'view', 'create', 'update'],

    // Hoặc loại bỏ actions không cần
    'except' => ['delete'],

    // Custom action patterns
    'extraPatterns' => [
        'GET search'     => 'search',      // GET /tasks/search
        'POST <id>/assign' => 'assign',    // POST /tasks/5/assign
    ],

    // Đổi tên token (mặc định là <id>)
    'tokens' => ['{id}' => '<id:\\d+>'],
]
```

---

### Strategy 2: Explicit Routes — Manual Mapping

Khai báo từng route một cách rõ ràng.

```php
'POST v1/auth/login'              => 'v1/auth/login',
'POST v1/auth/refresh'            => 'v1/auth/refresh',
'POST v1/auth/logout'             => 'v1/auth/logout',
'POST v1/auth/tokens'             => 'v1/auth/create-token',
'DELETE v1/auth/tokens/<id:\d+>'  => 'v1/auth/revoke-token',
```

**Khi nào dùng:** Controller có custom actions, KHÔNG theo chuẩn REST CRUD.

**Format:** `'METHOD url/pattern' => 'module/controller/action'`

---

## So sánh

| Tiêu chí | `rest\UrlRule` | Explicit Routes |
|----------|:-:|:-:|
| Chuẩn CRUD (index/view/create/update/delete) | ✅ Tự động tạo | ⚠️ Phải khai báo từng cái |
| Custom actions (login/logout/assign) | ⚠️ Cần `extraPatterns` | ✅ Tự nhiên, rõ ràng |
| Số dòng code | Ít (1 block cho tất cả) | Nhiều (1 dòng per route) |
| Control chi tiết | Trung bình | Cao |
| Dễ đọc | ⚠️ Ẩn, cần biết convention | ✅ Nhìn là biết route nào |
| CORS OPTIONS | ✅ Tự động tạo | ❌ Phải thêm thủ công |

---

## Đề xuất: Kết hợp cả hai (CRUD + Custom Actions)

### Config urlManager

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'enableStrictParsing' => true,
    'showScriptName' => false,
    'rules' => [

        // ════════════════════════════════════════════
        // Auth — Explicit routes (custom actions)
        // ════════════════════════════════════════════

        // Public endpoints (không cần token)
        'POST v1/auth/login'              => 'v1/auth/login',
        'POST v1/auth/refresh'            => 'v1/auth/refresh',

        // Protected endpoints (cần token)
        'POST v1/auth/logout'             => 'v1/auth/logout',
        'POST v1/auth/tokens'             => 'v1/auth/create-token',
        'DELETE v1/auth/tokens/<id:\d+>'  => 'v1/auth/revoke-token',

        // ════════════════════════════════════════════
        // Tasks — CRUD + Custom Actions
        // ════════════════════════════════════════════

        [
            'class' => 'yii\rest\UrlRule',
            'controller' => ['v1/task'],
            'pluralize' => true,
            'extraPatterns' => [
                'POST <id:\d+>/assign'         => 'assign',         // POST /tasks/5/assign
                'PUT <id:\d+>/change-status'   => 'change-status',  // PUT  /tasks/5/change-status
                'GET search'                   => 'search',         // GET  /tasks/search?q=...
                'GET <id:\d+>/attachments'     => 'attachments',    // GET  /tasks/5/attachments
            ],
        ],

        // ════════════════════════════════════════════
        // Projects — CRUD + Members management
        // ════════════════════════════════════════════

        [
            'class' => 'yii\rest\UrlRule',
            'controller' => ['v1/project'],
            'pluralize' => true,
            'extraPatterns' => [
                'GET <id:\d+>/members'          => 'members',       // GET    /projects/5/members
                'POST <id:\d+>/members'         => 'add-member',    // POST   /projects/5/members
                'DELETE <id:\d+>/members/<uid:\d+>' => 'remove-member', // DELETE /projects/5/members/3
                'GET <id:\d+>/tasks'            => 'tasks',         // GET    /projects/5/tasks
            ],
        ],
    ],
],
```

### Routes được tạo ra (Task)

| HTTP Method | URL | Action | Loại |
|-------------|-----|--------|------|
| `GET` | `/tasks` | `index` | Auto CRUD |
| `GET` | `/tasks/5` | `view` | Auto CRUD |
| `POST` | `/tasks` | `create` | Auto CRUD |
| `PUT/PATCH` | `/tasks/5` | `update` | Auto CRUD |
| `DELETE` | `/tasks/5` | `delete` | Auto CRUD |
| `POST` | `/tasks/5/assign` | `assign` | **Custom** |
| `PUT` | `/tasks/5/change-status` | `change-status` | **Custom** |
| `GET` | `/tasks/search?q=keyword` | `search` | **Custom** |
| `GET` | `/tasks/5/attachments` | `attachments` | **Custom** |

---

## Code mẫu: TaskController với Custom Actions

```php
<?php

namespace api\modules\v1\controllers;

use Yii;
use common\services\task\TaskServiceInterface;

/**
 * TaskController — CRUD (auto) + Custom Actions (extra)
 *
 * CRUD (auto by rest\UrlRule):
 *   GET    /v1/tasks         → index
 *   GET    /v1/tasks/:id     → view
 *   POST   /v1/tasks         → create
 *   PUT    /v1/tasks/:id     → update
 *   DELETE /v1/tasks/:id     → delete
 *
 * Custom (via extraPatterns):
 *   POST   /v1/tasks/:id/assign         → assign
 *   PUT    /v1/tasks/:id/change-status  → change-status
 *   GET    /v1/tasks/search             → search
 *   GET    /v1/tasks/:id/attachments    → attachments
 */
class TaskController extends BaseApiController
{
    private TaskServiceInterface $taskService;

    public function __construct(
        $id,
        $module,
        TaskServiceInterface $taskService,
        $config = []
    ) {
        $this->taskService = $taskService;
        parent::__construct($id, $module, $config);
    }

    // ── CRUD Actions (khớp với auto-generated routes) ──

    public function actionIndex(): array { /* ... */ }
    public function actionView(int $id): array { /* ... */ }
    public function actionCreate(): array { /* ... */ }
    public function actionUpdate(int $id): array { /* ... */ }
    public function actionDelete(int $id): array { /* ... */ }

    // ── Custom Actions (khớp với extraPatterns) ──

    /**
     * POST /v1/tasks/:id/assign
     * Body: { "assignee_id": 5 }
     */
    public function actionAssign(int $id): array
    {
        $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($task === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $assigneeId = Yii::$app->request->getBodyParam('assignee_id');
        if (empty($assigneeId)) {
            return $this->error('assignee_id là bắt buộc', 400);
        }

        // Gọi service để assign
        $result = $this->taskService->assign($id, (int) $assigneeId);
        if (!$result) {
            return $this->error('Không thể assign task', 422);
        }

        return $this->success(['message' => 'Task đã được assign']);
    }

    /**
     * PUT /v1/tasks/:id/change-status
     * Body: { "status": "completed" }
     */
    public function actionChangeStatus(int $id): array
    {
        $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($task === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $newStatus = Yii::$app->request->getBodyParam('status', '');
        if (empty($newStatus)) {
            return $this->error('status là bắt buộc', 400);
        }

        $result = $this->taskService->changeStatus($id, $newStatus);
        if (!$result) {
            return $this->error('Không thể đổi status', 422);
        }

        return $this->success(['message' => "Status đã đổi thành '{$newStatus}'"]);
    }

    /**
     * GET /v1/tasks/search?q=keyword&status=pending
     */
    public function actionSearch(): array
    {
        $params = Yii::$app->request->queryParams;
        $dataProvider = $this->taskService->search($params);

        $models = $dataProvider->getModels();
        $items = array_map([$this, 'formatTask'], $models);
        $pagination = $dataProvider->getPagination();

        return $this->paginated(
            $items,
            $pagination->totalCount,
            $pagination->getPage() + 1,
            $pagination->getPageSize()
        );
    }

    /**
     * GET /v1/tasks/:id/attachments
     */
    public function actionAttachments(int $id): array
    {
        $task = $this->taskService->getByIdForUser($id, Yii::$app->user->id);
        if ($task === null) {
            return $this->error('Task không tồn tại', 404);
        }

        $attachments = $task->taskAttachments;

        return $this->success(array_map(function ($att) {
            return [
                'id'         => $att->id,
                'filename'   => $att->original_name,
                'size'       => $att->file_size,
                'created_at' => $att->created_at,
            ];
        }, $attachments));
    }
}
```

---

## Code mẫu: ProjectController (Ví dụ khác)

```php
<?php

namespace api\modules\v1\controllers;

use Yii;

/**
 * ProjectController — CRUD + Members sub-resource
 *
 * CRUD (auto):
 *   GET    /v1/projects           → index
 *   GET    /v1/projects/:id       → view
 *   POST   /v1/projects           → create
 *   PUT    /v1/projects/:id       → update
 *   DELETE /v1/projects/:id       → delete
 *
 * Custom (extraPatterns):
 *   GET    /v1/projects/:id/members            → members
 *   POST   /v1/projects/:id/members            → add-member
 *   DELETE /v1/projects/:id/members/:uid       → remove-member
 *   GET    /v1/projects/:id/tasks              → tasks
 */
class ProjectController extends BaseApiController
{
    // ── CRUD Actions ──

    public function actionIndex(): array
    {
        // List projects của user hiện tại
        $projects = Project::find()
            ->where(['created_by' => Yii::$app->user->id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->success(array_map([$this, 'formatProject'], $projects));
    }

    public function actionView(int $id): array
    {
        $project = $this->findProject($id);
        if (!$project) {
            return $this->error('Project không tồn tại', 404);
        }
        return $this->success($this->formatProject($project));
    }

    public function actionCreate(): array
    {
        $model = new Project();
        $model->load(Yii::$app->request->getBodyParams(), '');
        $model->created_by = Yii::$app->user->id;

        if (!$model->save()) {
            return $this->error('Validation failed', 422, $model->getErrors());
        }

        return $this->success($this->formatProject($model), 201);
    }

    // ── Sub-resource: Members ──

    /**
     * GET /v1/projects/:id/members
     */
    public function actionMembers(int $id): array
    {
        $project = $this->findProject($id);
        if (!$project) {
            return $this->error('Project không tồn tại', 404);
        }

        $members = $project->getMembers()->all();

        return $this->success(array_map(function ($member) {
            return [
                'id'       => $member->id,
                'username' => $member->username,
                'email'    => $member->email,
                'role'     => $member->pivot->role ?? 'member',
            ];
        }, $members));
    }

    /**
     * POST /v1/projects/:id/members
     * Body: { "user_id": 5, "role": "editor" }
     */
    public function actionAddMember(int $id): array
    {
        $project = $this->findProject($id);
        if (!$project) {
            return $this->error('Project không tồn tại', 404);
        }

        $userId = Yii::$app->request->getBodyParam('user_id');
        $role   = Yii::$app->request->getBodyParam('role', 'member');

        if (empty($userId)) {
            return $this->error('user_id là bắt buộc', 400);
        }

        // Kiểm tra user đã là member chưa
        $existing = ProjectMember::findOne([
            'project_id' => $id,
            'user_id'    => $userId,
        ]);

        if ($existing) {
            return $this->error('User đã là thành viên', 409); // Conflict
        }

        $member = new ProjectMember([
            'project_id' => $id,
            'user_id'    => (int) $userId,
            'role'       => $role,
        ]);

        if (!$member->save()) {
            return $this->error('Không thể thêm member', 422, $member->getErrors());
        }

        return $this->success(['message' => 'Đã thêm member'], 201);
    }

    /**
     * DELETE /v1/projects/:id/members/:uid
     */
    public function actionRemoveMember(int $id, int $uid): array
    {
        $member = ProjectMember::findOne([
            'project_id' => $id,
            'user_id'    => $uid,
        ]);

        if (!$member) {
            return $this->error('Member không tồn tại', 404);
        }

        $member->delete();
        return $this->success(['message' => 'Đã xóa member']);
    }

    /**
     * GET /v1/projects/:id/tasks
     */
    public function actionTasks(int $id): array
    {
        $project = $this->findProject($id);
        if (!$project) {
            return $this->error('Project không tồn tại', 404);
        }

        $tasks = $project->getTasks()->orderBy(['created_at' => SORT_DESC])->all();

        return $this->success(array_map(function ($task) {
            return [
                'id'     => $task->id,
                'title'  => $task->title,
                'status' => $task->status,
            ];
        }, $tasks));
    }

    // ── Private ──

    private function findProject(int $id): ?Project
    {
        return Project::find()
            ->where(['id' => $id, 'created_by' => Yii::$app->user->id])
            ->one();
    }

    private function formatProject($project): array
    {
        return [
            'id'           => $project->id,
            'name'         => $project->name,
            'description'  => $project->description,
            'members_count' => $project->getMembersCount(),
            'created_at'   => $project->created_at,
        ];
    }
}
```

---

## `extraPatterns` Format Cheat Sheet

```php
'extraPatterns' => [
    // Format: 'HTTP_METHOD pattern' => 'action-id'
    // action-id sẽ map tới actionCamelCase trong controller

    // ── Collection-level (không có <id>) ──
    'GET search'                    => 'search',         // actionSearch()
    'GET statistics'                => 'statistics',     // actionStatistics()
    'POST bulk-create'              => 'bulk-create',    // actionBulkCreate()
    'DELETE bulk-delete'            => 'bulk-delete',    // actionBulkDelete()

    // ── Item-level (có <id>) ──
    'POST <id:\d+>/assign'          => 'assign',         // actionAssign($id)
    'PUT <id:\d+>/change-status'    => 'change-status',  // actionChangeStatus($id)
    'POST <id:\d+>/duplicate'       => 'duplicate',      // actionDuplicate($id)

    // ── Nested sub-resources ──
    'GET <id:\d+>/comments'         => 'comments',       // actionComments($id)
    'POST <id:\d+>/comments'        => 'add-comment',    // actionAddComment($id)
    'GET <id:\d+>/attachments'      => 'attachments',    // actionAttachments($id)

    // ── Nested sub-resource item (2 params) ──
    'DELETE <id:\d+>/members/<uid:\d+>' => 'remove-member', // actionRemoveMember($id, $uid)
],
```

> **Quy tắc action-id → method name:**
> `change-status` → `actionChangeStatus()`
> `add-member` → `actionAddMember()`
> `bulk-create` → `actionBulkCreate()`

---

## Lưu ý quan trọng

### 1. Module prefix bắt buộc

Khi controller nằm trong module v1, **phải có prefix `v1/`**:

```php
// ✅ Đúng
'controller' => ['v1/task'],
'POST v1/auth/login' => 'v1/auth/login',

// ❌ Sai — thiếu module prefix
'controller' => ['task'],
'POST auth/login' => 'auth/login',
```

### 2. `enableStrictParsing` = true

Khi bật, CHỈ những URL khớp với rules đã khai báo mới được chấp nhận. Request không khớp → 404. Đây là best practice cho API:
- Ngăn truy cập vào routes không mong muốn
- Rõ ràng về surface area của API

### 3. Thứ tự rules

Yii2 match rules **từ trên xuống dưới**. Explicit routes nên ĐẶT TRƯỚC `rest\UrlRule` để ưu tiên:

```php
'rules' => [
    // 1. Explicit routes TRƯỚC (ưu tiên cao)
    'POST v1/auth/login' => 'v1/auth/login',

    // 2. rest\UrlRule SAU (catch-all cho CRUD)
    ['class' => 'yii\rest\UrlRule', ...],
],
```

### 4. Mở rộng tương lai (v2)

Khi cần API v2, thêm cùng pattern:

```php
'rules' => [
    // v1
    'POST v1/auth/login' => 'v1/auth/login',
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v1/task']],

    // v2 — song song, không ảnh hưởng v1
    'POST v2/auth/login' => 'v2/auth/login',
    ['class' => 'yii\rest\UrlRule', 'controller' => ['v2/task']],
],
```
