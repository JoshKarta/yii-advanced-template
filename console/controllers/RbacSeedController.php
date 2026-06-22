<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use common\models\User;

class RbacSeedController extends Controller
{
    /**
     * Seed default roles and assign admin role to the first user.
     * Usage: php yii rbac-seed/seed
     */
    public function actionSeed()
    {
        $auth = Yii::$app->authManager;

        // 1. Define roles
        $roles = [
            'admin' => 'Administrator – full system access',
            'staff' => 'Staff – can manage applications and users',
            'reviewer' => 'Reviewer – can review and sign applications',
            'viewer' => 'Viewer – read‑only access',
        ];

        $created = 0;
        foreach ($roles as $name => $description) {
            $role = $auth->getRole($name);
            if (!$role) {
                $role = $auth->createRole($name);
                $role->description = $description;
                $auth->add($role);
                $this->stdout("Created role: $name\n");
                $created++;
            } else {
                $this->stdout("Role already exists: $name\n");
            }
        }

        if ($created === 0) {
            $this->stdout("No new roles were created.\n", \yii\helpers\Console::FG_YELLOW);
        } else {
            $this->stdout("Created $created new role(s).\n", \yii\helpers\Console::FG_GREEN);
        }

        // 2. Find the first user (by ID ascending)
        $firstUser = User::find()->orderBy(['id' => SORT_ASC])->one();
        if (!$firstUser) {
            $this->stdout("No users found in the database. Skipping admin assignment.\n", \yii\helpers\Console::FG_RED);
            return ExitCode::OK;
        }

        $userId = $firstUser->id;
        $username = $firstUser->username;

        // 3. Assign admin role to the first user
        $assignment = $auth->getAssignment('admin', $userId);
        if (!$assignment) {
            $adminRole = $auth->getRole('admin');
            if ($adminRole) {
                $auth->assign($adminRole, $userId);
                $this->stdout("Assigned 'admin' role to user ID $userId ($username).\n", \yii\helpers\Console::FG_GREEN);
            } else {
                $this->stdout("Admin role not found – something went wrong.\n", \yii\helpers\Console::FG_RED);
            }
        } else {
            $this->stdout("User ID $userId ($username) already has the 'admin' role.\n");
        }

        return ExitCode::OK;
    }
}