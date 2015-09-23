<?php
use yii\helpers\Html;
?>
<li>
  <a href="<?= $file->path()?>" target="_blank">
    <img src="<?= $model->thumb('image_gallery', '80x80', $file->path())?>">
  </a>
  <a class="btn btn-lg"><span class="glyphicon glyphicon-remove remove-item" data-remove-item="li"></span></a>
  <?= Html::textInput(Html::getInputName($model, $attribute) . '[files][' . $file->id .']', $file->title, [
      'class' => 'form-control',
  ])?>
</li>
