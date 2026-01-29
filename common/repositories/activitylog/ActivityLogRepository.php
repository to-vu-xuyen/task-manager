<?php

namespace common\repositories\activitylog;

use Yii;
use common\dto\activitylog\ActivityLogDto;
use common\repositories\activitylog\interface\ActivityLogRepositoryInterface;
use common\models\activitylog\ActivityLog;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;

/**
 * 
 */
class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function getNewest(int $limit = 50): array
    {
        return ActivityLog::find()->orderBy(['created_at' => SORT_DESC])->limit($limit)->all();
    }

	public function findById(int $id): ?ActivityLogDto{
		$model = ActivityLog::find()->where(['id' => $id])->one();
        return $model ? new ActivityLogDto($model) : null;
	}

	public function findByUserId(int $user_id, int $limit = 50): array{
		$models = ActivityLog::find()->where(['user_id' => $user_id])->limit($limit)->all();
		return $models;
	}

	public function findByTarget(string $targetType, int $targetId): array{
		$models = ActivityLog::find()
			->where(['target_type' => $targetType, 'target_id' => $targetId])
			// ->limit($limit)
			->all();
		return $models;
	}

    public function findByAction(string $action, int $limit = 100): array{
		$models = ActivityLog::find()
			->where(['action' => $action])
			->limit($limit)
			->all();
		return $models;
	}

	public function search(array $filter = [], int $pageSize = 20): DataProviderInterface
    {
        $query = ActivityLog::find();
        
        $query->andFilterWhere(['id' => $filter['id'] ?? null]);
        $query->andFilterWhere(['user_id' => $filter['user_id'] ?? null]);
        $query->andFilterWhere(['target_id' => $filter['target_id'] ?? null]);
        $query->andFilterWhere(['like', 'target_type', $filter['target_type'] ?? null]);
        $query->andFilterWhere(['like', 'meta', $filter['meta'] ?? null]);
        $query->andFilterWhere(['like', 'action', $filter['action'] ?? null]);
        $query->andFilterWhere(['like', 'ip_address', $filter['ip_address'] ?? null]);
        $query->andFilterWhere(['like', 'user_agent', $filter['user_agent'] ?? null]);
        $query->andFilterWhere(['like', 'error_message', $filter['error_message'] ?? null]);

        // Check ngày tạo trong khoảng
        if (!empty($filter['created_from'])) {
            $query->andWhere(['>=', 'created_at', $filter['created_from'] . ' 00:00:00']);
        }
        
        if (!empty($filter['created_to'])) {
            $query->andWhere(['<=', 'created_at', $filter['created_to'] . ' 23:59:59']);
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes' => ['id', 'user_id', 'target_id', 'target_type', 'meta', 'action'],
            ]
        ]);
    }


	public function save(ActivityLog $log): void{
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$log->validate()) {
                throw new \DomainException('ActivityLog validation failed: ' . json_encode($log->errors));
            }
            
            if (!$log->save(false)) {
                throw new \RuntimeException('Cannot save ActivityLog');
            }
            
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
	}
}
