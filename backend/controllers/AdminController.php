<?php

namespace backend\controllers;

use Yii;
use yii\web\Response;

class AdminController extends \yii\web\Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    // Admin role can do anything
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                    // For other users, we could add additional rules, 
                    // but you're using dynamic permission checks in actions,
                    // so we can just deny everything else by default.
                    // [
                    //     'allow' => false,
                    // ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionScanRoutes()
    {
        return $this->render('scan-routes');
    }

    /**
     * List all roles
     */
    public function actionRoles()
    {
        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();

        return $this->render('roles', [
            'roles' => $roles,
        ]);
    }

    /**
     * Permissions page – auto‑sync routes and show notification.
     */
    public function actionPermissions()
    {
        // 1. Sync routes to permissions
        $added = Yii::$app->routeService->syncRoutesToDb();

        // 2. Get all roles for the dropdown
        $auth = Yii::$app->authManager;
        $roles = $auth->getRoles();

        // 3. Pass sync result to view
        return $this->render('permissions', [
            'roles' => $roles,
            'syncAdded' => $added,
        ]);
    }

    /**
     * AJAX: Get available and assigned routes for a role.
     */
    public function actionGetRoleRoutes($role)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        $roleObj = $auth->getRole($role);
        if (!$roleObj) {
            return ['success' => false, 'message' => 'Role not found.'];
        }

        // All routes (permissions)
        $allRoutePermissions = $auth->getPermissions(); // all permissions
        // We only want permissions that are route strings (you can filter if you use prefix)
        // For simplicity, we assume all permissions are routes. If you have other permissions, you need to distinguish.
        // Better: store routes with a prefix like 'route:' – adjust accordingly.
        // Here we treat all permissions as routes (if that's your design).
        $allRoutes = array_keys($allRoutePermissions);

        // Get assigned permissions for the role
        $assignedPermissions = $auth->getPermissionsByRole($role);
        $assignedRoutes = array_keys($assignedPermissions);

        // Available = all routes minus assigned
        $availableRoutes = array_diff($allRoutes, $assignedRoutes);

        // Sort
        sort($allRoutes);
        sort($availableRoutes);
        sort($assignedRoutes);

        // For additional info (http methods, description) you might fetch from somewhere,
        // but we'll return the simple arrays.

        return [
            'success' => true,
            'allRoutes' => $allRoutes,
            'availableRoutes' => $availableRoutes,
            'assignedRoutes' => $assignedRoutes,
        ];
    }

    /**
     * AJAX: Save assigned routes for a role.
     */
    public function actionSaveRoleRoutes()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        $roleName = Yii::$app->request->post('role');
        $routes = Yii::$app->request->post('routes', []);

        $role = $auth->getRole($roleName);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found.'];
        }

        // Get all current permissions for the role
        $currentPermissions = $auth->getPermissionsByRole($roleName);
        $currentRouteNames = array_keys($currentPermissions);

        // Determine which to add and which to remove
        $toAdd = array_diff($routes, $currentRouteNames);
        $toRemove = array_diff($currentRouteNames, $routes);

        // Apply changes
        foreach ($toAdd as $route) {
            $permission = $auth->getPermission($route);
            if ($permission) {
                $auth->addChild($role, $permission);
            }
        }
        foreach ($toRemove as $route) {
            $permission = $auth->getPermission($route);
            if ($permission) {
                $auth->removeChild($role, $permission);
            }
        }

        // Invalidate cache
        Yii::$app->routeService->clearRoleRoutesCache($roleName);
        return ['success' => true, 'message' => 'Assignments saved successfully.'];
    }

    /**
     * AJAX: Create role via modal
     */
    public function actionAjaxCreateRole()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        $name = trim(Yii::$app->request->post('name'));
        $description = trim(Yii::$app->request->post('description'));

        if (!$name) {
            return ['success' => false, 'message' => 'Role name cannot be empty.'];
        }

        if (!preg_match('/^[a-z_]+$/', $name)) {
            return ['success' => false, 'message' => 'Role name can only contain lowercase letters and underscores.'];
        }

        if ($auth->getRole($name)) {
            return ['success' => false, 'message' => 'Role "' . $name . '" already exists.'];
        }

        $role = $auth->createRole($name);
        $role->description = $description;

        if ($auth->add($role)) {
            return [
                'success' => true,
                'message' => 'Role created successfully.',
                'role' => [
                    'name' => $role->name,
                    'description' => $role->description
                ]
            ];
        }

        return ['success' => false, 'message' => 'Failed to create role.'];
    }

    /**
     * AJAX: Delete a role.
     */
    public function actionAjaxDeleteRole()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;
        $name = Yii::$app->request->post('name');

        $role = $auth->getRole($name);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found.'];
        }

        // Invalidate cache
        Yii::$app->routeService->clearRoleRoutesCache($role);

        // Remove all children and assignments first? Yii's remove() does that automatically.
        if ($auth->remove($role)) {
            return ['success' => true, 'message' => 'Role deleted.'];
        }
        return ['success' => false, 'message' => 'Failed to delete role.'];
    }

    /**
     * AJAX: Update a role.
     */
    public function actionAjaxUpdateRole()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $auth = Yii::$app->authManager;

        $oldName = Yii::$app->request->post('oldName');
        $newName = trim(Yii::$app->request->post('name'));
        $description = trim(Yii::$app->request->post('description'));

        $role = $auth->getRole($oldName);
        if (!$role) {
            return ['success' => false, 'message' => 'Role not found.'];
        }

        if (!$newName) {
            return ['success' => false, 'message' => 'Role name cannot be empty.'];
        }
        if (!preg_match('/^[a-z_]+$/', $newName)) {
            return ['success' => false, 'message' => 'Role name can only contain lowercase letters and underscores.'];
        }

        // If name changed, we need to create a new role and migrate assignments/children
        if ($oldName !== $newName) {
            // Check if new name already exists
            if ($auth->getRole($newName)) {
                return ['success' => false, 'message' => 'Role "' . $newName . '" already exists.'];
            }
            // Create new role
            $newRole = $auth->createRole($newName);
            $newRole->description = $description;
            $auth->add($newRole);

            // Move children (permissions) from old to new
            $children = $auth->getChildren($oldName);
            foreach ($children as $child) {
                $auth->addChild($newRole, $child);
            }

            // Move assignments (users) from old to new
            $assignments = $auth->getUserIdsByRole($oldName);
            foreach ($assignments as $userId) {
                $auth->assign($newRole, $userId);
            }

            // Remove old role
            $auth->remove($role);

            // Invalidate cache
            Yii::$app->routeService->clearRoleRoutesCache($newName);

            return ['success' => true, 'message' => 'Role updated.'];
        } else {
            // Just update description
            $role->description = $description;
            if ($auth->update($oldName, $role)) {
                return ['success' => true, 'message' => 'Role updated.'];
            }
            return ['success' => false, 'message' => 'Failed to update role.'];
        }
    }

    public function actionRescanRoutes()
    {
        Yii::$app->routeService->clearCache();
        $added = Yii::$app->routeService->syncRoutesToDb();
        Yii::$app->session->setFlash('success', "Rescanned: $added new routes added.");
        return $this->redirect(['permissions']);
    }

    public function actionListRoutes()
    {
        return Yii::$app->routeService->getAllRoutes();
    }
}
