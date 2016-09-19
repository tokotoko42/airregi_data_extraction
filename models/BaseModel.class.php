<?php

class BaseModel extends CActiveRecord
{
    public function __construct($scenario='insert') {
        parent::__construct($scenario);
        $this->attachBehaviors($this->behaviors());
    }

    public function beforeSave()
    {
        $this->creates = date('Y-m-d');
        return parent::beforeSave();
    }

    public function output()
    {
        return $this->attributes;
    }
}
