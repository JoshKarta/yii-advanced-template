<?php

use yii\helpers\Html;
use yii\helpers\Url;
use common\models\User;

/** @var User $user */
$this->title = 'Profile of ' . Html::encode($user->username);
$this->params['breadcrumbs'][] = $this->title;

$avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($user->username) . '&size=100&background=random';
$hashedId = Yii::$app->hashId->encode($user->id);
?>

<div class="row">
    <!-- Left Column: Avatar & Quick Info -->
    <div class="col-md-4">
        <div class="card card-primary card-outline rounded-4">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid img-circle" src="<?= $avatarUrl ?>"
                        alt="User profile picture">
                </div>

                <h3 class="profile-username text-center">
                    <?= Html::encode($user->username) ?>
                </h3>

                <p class="text-muted text-center">
                    <?= $user->getRoleName() ?: 'No role assigned' ?>
                </p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Email</b> <a class="float-right">
                            <?= Html::encode($user->email) ?>
                        </a>
                    </li>
                    <li class="list-group-item">
                        <b>Status</b> <span class="float-right">
                            <?= $user->getStatusLabel() ?>
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Member since</b> <span class="float-right">
                            <?= Yii::$app->formatter->asDate($user->created_at) ?>
                        </span>
                    </li>
                </ul>

                <?php if ($user->id == Yii::$app->user->id): ?>
                    <a href="<?= Url::to(['/user/update', 'id' => $hashedId]) ?>"
                        class="btn btn-primary btn-block rounded-3">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Detailed Info -->
</div>