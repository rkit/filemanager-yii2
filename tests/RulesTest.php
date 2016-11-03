<?php

/**
 * @link https://github.com/rkit/filemanager-yii2
 * @copyright Copyright (c) 2015 Igor Romanov
 * @license [MIT](http://opensource.org/licenses/MIT)
 */

namespace tests;

use tests\data\models\News;

class RulesTest extends BaseTest
{
    private $modelClass = 'tests\data\models\News';

    public function testRules()
    {
        $model = $this->createObject($this->modelClass);
        $rules = $model->fileRules('image');

        $this->assertArrayHasKey('imageSize', $rules);
        $this->assertArrayHasKey('mimeTypes', $rules);
        $this->assertArrayHasKey('extensions', $rules);
        $this->assertArrayHasKey('maxSize', $rules);
        $this->assertArrayHasKey('tooBig', $rules);
    }

    public function testRulesWithOnlyCoreValidators()
    {
        $model = $this->createObject($this->modelClass);
        $rules = $model->fileRules('image', true);

        $this->assertArrayNotHasKey('imageSize', $rules);
        $this->assertArrayHasKey('mimeTypes', $rules);
        $this->assertArrayHasKey('extensions', $rules);
        $this->assertArrayHasKey('maxSize', $rules);
        $this->assertArrayHasKey('tooBig', $rules);
    }
}
