<?php

/** @var yii\web\View $this */

use app\models\Favorites;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Книга';
?>
<div class="glav-books-container">
    <div class="books-wrap-display-content">
        <div class="block-books-raspol">
            <div class="books-img-1-block">
                <img src="../image/books/<?= $books['image'] ?>" alt="" class="books-img-1">
            </div>
            <div class="books-wrap-content-text">
                <div class="flex-books-izb" style="display: flex">
                    <h2 class="title"><?= $books['name'] ?></h2>
                        <a href="<?= $removeFromFavoriteUrl ?>"><img src="../image/min.png" alt="Минус" class="favorite-icon" style="margin-left: 20px; width: 55px;"></a>

                        <a href="<?= $addToFavoriteUrl ?>"><img src="../image/plus1.png" alt="Плюс" class="favorite-icon" style="margin-left: 20px; width: 55px;"></a>

                </div>
                <p class="author"><?= $books->author->nsp ?></p>
                <p class="books-podrob">О книге</p>
                <p class="books-opisanie"><?= $books['opisanie'] ?></p>
            </div>
        </div>
    </div>
    <div class="books-reding-content-wrap">
        <div class="books-reding-content">
            <div class="books-reding-content-container-p">
                <p class="books-reding"><?= nl2br($books['file']) ?>
            </div>
        </div>
    </div>

    <!--  Комментарий  -->
    <br>
    <center style="    margin-left: -109px;"><h3 style="color: #F25900">Комментарии</h3></center>
    <br>
    <?php foreach ($comments as $item):?>
        <div class="card" style="margin-top: 15px">
            <div class="card-body">
                <div class="d-flex flex-start align-items-center">
                    <img class="rounded-circle shadow-1-strong me-3"
                         src="../image/avatar.jpg" alt="avatar" width="60"
                         height="60" />
                    <div>
                        <h6 style="color: black"><?= $item->user->username ?></h6>
                        <p class="text-muted small mb-0">
                            <?= $item->created_at ?>
                        </p>
                    </div>
                </div>

                <p class="mt-3 mb-4 pb-2" style="color: black">
                    <?= $item->body ?>
                </p>

                <div class="small d-flex justify-content-start">
                    <a href="#!" class="d-flex align-items-center me-3">
                        <i class="far fa-thumbs-up me-2"></i>
                        <p class="mb-0">Like</p>
                    </a>
                    <a href="#!" class="d-flex align-items-center me-3">
                        <i class="far fa-comment-dots me-2"></i>
                        <p class="mb-0">Comment</p>
                    </a>
                    <a href="#!" class="d-flex align-items-center me-3">
                        <i class="fas fa-share me-2"></i>
                        <p class="mb-0">Share</p>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach;?>


    <?php if (!Yii::$app->user->isGuest): ?>
        <div class="" style="padding: 20px">
            <!--Форма отправки комментариев-->
            <?php $form = ActiveForm::begin(['id'=> 'contact-form']); ?>
            <div class="form-group">
                <div class="col-md-12">
                    <?php $model = new \app\models\Comment();
                    echo $form->field($model, 'body', ['template' => '<label class="col-lg-1 col-form-label mr-lg-3" style="color: white;">{label}</label>{input}{hint}{error}',
                    ])->textArea([]) ?>
                    <button class="redeng">Отправить</button>
                </div>
            </div>
            <?php ActiveForm::end() ?>
            <br>
        </div>
    <?php endif; ?>

</div>
