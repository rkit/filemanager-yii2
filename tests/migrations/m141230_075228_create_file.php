<?php

use yii\db\Schema;

class m141230_075228_create_file extends \yii\db\Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%file}}', [
            'id' => Schema::TYPE_PK,
            'user_id' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'title' => Schema::TYPE_STRING . " NOT NULL DEFAULT ''",
            'name' => Schema::TYPE_STRING . " NOT NULL DEFAULT ''",
            'date_create' => Schema::TYPE_TIMESTAMP . " NULL DEFAULT NULL",
            'date_update' => Schema::TYPE_TIMESTAMP . " NULL DEFAULT NULL",
            'ip' => Schema::TYPE_BIGINT . "(20) NOT NULL DEFAULT 0",
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('{{%file}}');
    }
}
