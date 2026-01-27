<?php

namespace common\forms\activitylog;

use yii\base\Model;


class ActivityLogCreateForm extends Model{

	public $user_id;
    public $action;
    public $target_type;
    public $target_id;
    public $meta;


    public function rules() {
    	return [
    		[['user_id', 'action', 'target_type', 'target_id'], 'required'],
    		[['user_id', 'target_id'], 'integer'],
    		[['meta'], 'safe'],
    		[['meta'], 'validateMeta'],
    	];
    }

    public function validateMeta($attribute) {
        if (!is_array($this->$attribute) && !is_null($this->$attribute)) {
            $this->addError($attribute, 'Meta must be an array or null');
        }
    }

    
}