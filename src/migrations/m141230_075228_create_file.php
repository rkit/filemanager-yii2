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
            'owner_id' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'owner_type' => "tinyint(1) NOT NULL DEFAULT 0",
            'title' => Schema::TYPE_STRING . " NOT NULL DEFAULT ''",
            'name' => Schema::TYPE_STRING . " NOT NULL DEFAULT ''",
            'size' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'mime' => Schema::TYPE_STRING . "(100) NOT NULL DEFAULT ''",
            'date_create' => Schema::TYPE_TIMESTAMP . " NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'date_update' => Schema::TYPE_TIMESTAMP . " NOT NULL DEFAULT '0000-00-00 00:00:00'",
            'ip' => Schema::TYPE_BIGINT . "(20) NOT NULL DEFAULT 0",
            'tmp' => "tinyint(1) NOT NULL DEFAULT 0",
            'position' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'protected' => "tinyint(1) NOT NULL DEFAULT 0"
        ], $tableOptions);

        $this->createIndex('owner', '{{%file}}', 'owner_id, owner_type');
    }

    public function safeDown()
    {
        $this->dropTable('{{%file}}');
    }
}
