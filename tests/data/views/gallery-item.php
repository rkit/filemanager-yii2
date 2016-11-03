<?php
use yii\helpers\Html;
?>
<li>
  <a href="<?= $model->fileUrl('gallery', $file)?>" target="_blank">
    <img src="<?= $model->thumbUrl('gallery', '200x200', $file)?>">
  </a>
  <a class="btn btn-lg"><span class="glyphicon glyphicon-remove remove-item" data-remove-item="li"></span></a>
  <?= Html::textInput(Html::getInputName($model, $attribute) . '[]', $file->id, [
      'class' => 'form-control',
  ])?>
</li>
