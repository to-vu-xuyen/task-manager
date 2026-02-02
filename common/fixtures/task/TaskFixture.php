<?php

namespace common\fixtures\task;

use common\fixtures\UserFixture;
use yii\test\ActiveFixture;

class TaskFixture extends ActiveFixture
{
    public $modelClass = 'common\models\task\Task';
    public $dataFile = '@common/tests/_data/task/task.php';

    public $depends = [
        UserFixture::class,
    ];
}