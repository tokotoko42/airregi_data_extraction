<?php

class ReceiptDb extends BaseModel
{
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    
    public function tableName()
    {
        return 'receipt_db';
    }
    
    public function save($runValidation=true, $attributes=null)
    {
        parent::save($runValidation, $attributes);
    }
    
}