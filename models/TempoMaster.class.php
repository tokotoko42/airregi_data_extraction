<?php

class TempoMaster extends BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function tableName()
    {
        return 'tempo_master';
    }
    
    public function save($runValidation=true, $attributes=null)
    {
        parent::save($runValidation, $attributes);
    }
    
}