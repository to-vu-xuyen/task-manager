# ActivityLog Module - Implementation Plan (Complete Code)

## Giải Đáp Thắc Mắc

### 1. `Yii::$app->get('activityLogService')` là gì?

Đây là cách lấy Service từ **Yii Service Container** (Dependency Injection Container).

**Cách cấu hình trong `config/main.php`:**
```php
'container' => [
    'definitions' => [
        'common\services\activitylog\ActivityLogServiceInterface' 
            => 'common\services\activitylog\ActivityLogService',
    ],
    'singletons' => [
        'activityLogService' => [
            'class' => 'common\services\activitylog\ActivityLogService',
        ],
    ],
],
```

**Khi gọi:** `Yii::$app->get('activityLogService')` sẽ trả về instance của `ActivityLogService`.

---

## Kiến Trúc

```
Controller/CLI
      │
      ▼
ActivityLogger (Helper) ──► ActivityLogService (Service) ──► ActivityLogRepository ──► ActivityLog (Model)
```

---

## Complete Code

### 1. Model

**File:** `common/models/activitylog/ActivityLog.php`

```php
<?php

namespace common\models\activitylog;

use Yii;
use common\models\User;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * ActivityLog Model
 *
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property string $target_type
 * @property int $target_id
 * @property string|null $meta
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $error_message
 * @property string $created_at
 */
class ActivityLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%activity_log}}';
    }

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'action', 'target_type', 'target_id'], 'required'],
            [['user_id', 'target_id'], 'integer'],
            [['action', 'target_type'], 'string', 'max' => 50],
            [['ip_address', 'user_agent'], 'string', 'max' => 255],
            [['meta', 'error_message'], 'string'],
            [['meta'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'action' => 'Action',
            'target_type' => 'Target Type',
            'target_id' => 'Target ID',
            'meta' => 'Meta',
            'ip_address' => 'IP Address',
            'user_agent' => 'User Agent',
            'error_message' => 'Error Message',
            'created_at' => 'Created At',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
```

---

### 2. Repository Interface

**File:** `common/repositories/activitylog/ActivityLogRepositoryInterface.php`

```php
<?php

namespace common\repositories\activitylog;

use common\models\activitylog\ActivityLog;
use yii\data\DataProviderInterface;

interface ActivityLogRepositoryInterface
{
    /**
     * Lưu ActivityLog (insert hoặc update)
     */
    public function save(ActivityLog $log): void;

    /**
     * Xóa ActivityLog
     */
    public function delete(ActivityLog $log): void;

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?ActivityLog;

    /**
     * Lấy danh sách mới nhất
     */
    public function findAll(int $limit = 50): array;

    /**
     * Tìm theo User ID
     */
    public function findByUserId(int $userId, int $limit = 50): array;

    /**
     * Tìm theo Target (polymorphic)
     */
    public function findByTarget(string $targetType, int $targetId): array;

    /**
     * Tìm kiếm với filter
     */
    public function search(array $filter = [], int $pageSize = 20): DataProviderInterface;
}
```

---

### 3. Repository

**File:** `common/repositories/activitylog/ActivityLogRepository.php`

```php
<?php

namespace common\repositories\activitylog;

use Yii;
use common\models\activitylog\ActivityLog;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;

class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    /**
     * Lưu ActivityLog
     */
    public function save(ActivityLog $log): void
    {
        if (!$log->validate()) {
            throw new \DomainException('Validation failed: ' . json_encode($log->errors));
        }
        
        if (!$log->save(false)) {
            throw new \RuntimeException('Cannot save ActivityLog');
        }
    }

    /**
     * Xóa ActivityLog
     */
    public function delete(ActivityLog $log): void
    {
        if (!$log->delete()) {
            throw new \RuntimeException('Cannot delete ActivityLog');
        }
    }

    /**
     * Tìm theo ID
     */
    public function findById(int $id): ?ActivityLog
    {
        return ActivityLog::findOne($id);
    }

    /**
     * Lấy danh sách mới nhất
     */
    public function findAll(int $limit = 50): array
    {
        return ActivityLog::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Tìm theo User ID
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        return ActivityLog::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Tìm theo Target
     */
    public function findByTarget(string $targetType, int $targetId): array
    {
        return ActivityLog::find()
            ->where([
                'target_type' => $targetType,
                'target_id' => $targetId,
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * Tìm kiếm với filter
     */
    public function search(array $filter = [], int $pageSize = 20): DataProviderInterface
    {
        $query = ActivityLog::find();

        // Filter theo các trường
        $query->andFilterWhere(['user_id' => $filter['user_id'] ?? null]);
        $query->andFilterWhere(['action' => $filter['action'] ?? null]);
        $query->andFilterWhere(['target_type' => $filter['target_type'] ?? null]);
        $query->andFilterWhere(['target_id' => $filter['target_id'] ?? null]);

        // Filter theo khoảng thời gian
        if (!empty($filter['created_from'])) {
            $query->andWhere(['>=', 'created_at', $filter['created_from'] . ' 00:00:00']);
        }
        if (!empty($filter['created_to'])) {
            $query->andWhere(['<=', 'created_at', $filter['created_to'] . ' 23:59:59']);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $pageSize],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);
    }
}
```

---

### 4. Service Interface

**File:** `common/services/activitylog/ActivityLogServiceInterface.php`

```php
<?php

namespace common\services\activitylog;

use common\models\activitylog\ActivityLog;

interface ActivityLogServiceInterface
{
    /**
     * Ghi log một action (fire-and-forget, không return)
     */
    public function log(
        string $action,
        string $targetType,
        int $targetId,
        array $meta = []
    ): void;

    /**
     * Lấy tất cả logs
     */
    public function getAll(int $limit = 50): array;

    /**
     * Lấy log theo ID
     */
    public function getById(int $id): ?ActivityLog;

    /**
     * Lấy logs theo User
     */
    public function getByUserId(int $userId, int $limit = 50): array;

    /**
     * Lấy logs theo Target
     */
    public function getByTarget(string $targetType, int $targetId): array;
}
```

---

### 5. Service

**File:** `common/services/activitylog/ActivityLogService.php`

```php
<?php

namespace common\services\activitylog;

use Yii;
use common\models\activitylog\ActivityLog;
use common\repositories\activitylog\ActivityLogRepositoryInterface;

class ActivityLogService implements ActivityLogServiceInterface
{
    private ActivityLogRepositoryInterface $repository;

    public function __construct(ActivityLogRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Ghi log một action
     */
    public function log(
        string $action,
        string $targetType,
        int $targetId,
        array $meta = []
    ): void {
        $log = new ActivityLog();
        
        // Lấy user_id từ session (nếu có)
        $log->user_id = Yii::$app->user->isGuest ? 0 : Yii::$app->user->id;
        
        // Gán các giá trị
        $log->action = $action;
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        
        // Encode meta thành JSON
        if (!empty($meta)) {
            $log->meta = json_encode($meta);
        }
        
        // Lấy thông tin request (nếu có)
        if (!Yii::$app->request->isConsoleRequest) {
            $log->ip_address = Yii::$app->request->userIP;
            $log->user_agent = Yii::$app->request->userAgent;
        }
        
        // Lưu qua Repository
        $this->repository->save($log);
    }

    /**
     * Lấy tất cả logs
     */
    public function getAll(int $limit = 50): array
    {
        return $this->repository->findAll($limit);
    }

    /**
     * Lấy log theo ID
     */
    public function getById(int $id): ?ActivityLog
    {
        return $this->repository->findById($id);
    }

    /**
     * Lấy logs theo User
     */
    public function getByUserId(int $userId, int $limit = 50): array
    {
        return $this->repository->findByUserId($userId, $limit);
    }

    /**
     * Lấy logs theo Target
     */
    public function getByTarget(string $targetType, int $targetId): array
    {
        return $this->repository->findByTarget($targetType, $targetId);
    }
}
```

---

### 6. Form

**File:** `common/forms/activitylog/ActivityLogCreateForm.php`

```php
<?php

namespace common\forms\activitylog;

use yii\base\Model;

class ActivityLogCreateForm extends Model
{
    public $user_id;
    public $action;
    public $target_type;
    public $target_id;
    public $meta;

    public function rules(): array
    {
        return [
            [['user_id', 'action', 'target_type', 'target_id'], 'required'],
            [['user_id', 'target_id'], 'integer'],
            [['action', 'target_type'], 'string', 'max' => 50],
            [['meta'], 'safe'],
            [['meta'], 'validateMeta'],
        ];
    }

    public function validateMeta($attribute): void
    {
        if (!is_array($this->$attribute) && !is_null($this->$attribute)) {
            $this->addError($attribute, 'Meta must be an array or null');
        }
    }
}
```

---

### 7. Helper

**File:** `common/helpers/ActivityLogger.php`

```php
<?php

namespace common\helpers;

use Yii;
use common\services\activitylog\ActivityLogServiceInterface;

class ActivityLogger
{
    /**
     * Lấy Service instance từ DI Container
     */
    private static function getService(): ActivityLogServiceInterface
    {
        return Yii::$app->get('activityLogService');
    }

    /**
     * Log một action
     */
    public static function log(
        string $action,
        string $targetType,
        int $targetId,
        array $meta = []
    ): void {
        self::getService()->log($action, $targetType, $targetId, $meta);
    }

    // ============ SHORTCUT METHODS ============

    public static function logTaskCreated(int $taskId, array $meta = []): void
    {
        self::log('create', 'task', $taskId, $meta);
    }

    public static function logTaskUpdated(int $taskId, array $changes = []): void
    {
        self::log('update', 'task', $taskId, ['changes' => $changes]);
    }

    public static function logTaskDeleted(int $taskId): void
    {
        self::log('delete', 'task', $taskId);
    }

    public static function logUserLogin(int $userId): void
    {
        self::log('login', 'user', $userId);
    }

    public static function logUserLogout(int $userId): void
    {
        self::log('logout', 'user', $userId);
    }
}
```

---

### 8. Config (DI Container)

**File:** `common/config/main.php` (thêm vào phần `components`)

```php
'container' => [
    'definitions' => [
        // Interface => Implementation
        'common\repositories\activitylog\ActivityLogRepositoryInterface' 
            => 'common\repositories\activitylog\ActivityLogRepository',
        'common\services\activitylog\ActivityLogServiceInterface' 
            => 'common\services\activitylog\ActivityLogService',
    ],
    'singletons' => [
        // Đăng ký service để dùng với Yii::$app->get()
        'activityLogService' => function () {
            $repository = new \common\repositories\activitylog\ActivityLogRepository();
            return new \common\services\activitylog\ActivityLogService($repository);
        },
    ],
],
```

---

## Cách Sử Dụng

```php
// Trong Controller hoặc Service khác
use common\helpers\ActivityLogger;

// Cách 1: Dùng shortcut methods
ActivityLogger::logTaskCreated(123, ['title' => 'New Task']);

// Cách 2: Dùng method log() trực tiếp
ActivityLogger::log('create', 'project', 456, ['name' => 'New Project']);
```
