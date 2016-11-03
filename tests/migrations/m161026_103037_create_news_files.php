<?php

use yii\db\Schema;

class m161026_103037_create_news_files extends \yii\db\Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%news_files}}', [
            'id' => Schema::TYPE_PK,
            'news_id' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'file_id' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'type' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
            'position' => Schema::TYPE_INTEGER . " NOT NULL DEFAULT 0",
        ], $tableOptions);

        $this->createIndex('link', '{{%news_files}}', 'news_id, file_id');
        $this->createIndex('type_news', '{{%news_files}}', 'type, news_id');
    }

    public function safeDown()
    {
        $this->dropTable('{{%news_files}}');
    }
}
