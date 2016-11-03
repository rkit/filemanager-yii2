<?php

use yii\db\Schema;

class m141230_075229_create_news extends \yii\db\Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%news}}', [
            'id' => Schema::TYPE_PK,
            'title' => Schema::TYPE_STRING . " NOT NULL DEFAULT ''",
            'image' => Schema::TYPE_STRING . " NOT NULL DEFAULT ''",
        ], $tableOptions);
    }

    public function safeDown()
    {
        $this->dropTable('{{%news}}');
    }
}
