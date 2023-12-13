<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Proposal $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

$this->title = 'Заявка';
?>
<div class="site-login" style="margin-top: 21px;">
    <div class="content-container">
        <center><h1><?= Html::encode($this->title) ?></h1></center>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'layout' => 'horizontal',
            'fieldConfig' => [
                'template' => "{label}\n{input}\n{error}",
                'labelOptions' => ['class' => 'col-lg-1 col-form-label mr-lg-3'],
                'inputOptions' => ['class' => 'col-lg-3 form-control'],
                'errorOptions' => ['class' => 'col-lg-7 invalid-feedback'],
            ],
        ]); ?>

        <p>Обращение от пользователя: <?= Yii::$app->user->identity->username ?></p>

        <?= $form->field($model, 'body', ['template' => '<label class="col-lg-1 col-form-label mr-lg-3" style="color: white;">{label}</label>{input}{hint}{error}',
            ])->textarea(['rows' => 6]) ?>

        <?= $form->field($model, 'image', ['template' => '<label class="col-lg-1 col-form-label mr-lg-3" style="color: white;">{label}</label>{input}{hint}{error}',
        ])->fileInput() ?>

        <div class="form-group">
            <?= Html::submitButton('Отправить', ['class' => 'redeng', 'name' => 'login-button', 'style'=>'margin-left: 8;']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

