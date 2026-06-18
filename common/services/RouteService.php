<?php

namespace common\services;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;

class RouteService extends Component
{
    /**
     * Cache key for storing routes
     */
    const CACHE_KEY = 'all-application-routes';

    /**
     * Cache duration in seconds (1 hour)
     */
    const CACHE_DURATION = 3600;

    /**
     * Get all application routes with caching
     * @return array
     */
    public function getAllRoutes()
    {
        $cache = Yii::$app->cache;
        $routes = $cache->get(self::CACHE_KEY);

        if ($routes === false) {
            $routes = $this->scanAllRoutes();
            $cache->set(self::CACHE_KEY, $routes, self::CACHE_DURATION);
        }

        return $routes;
    }

    /**
     * Get filtered routes for Select2 format
     * @param string $search
     * @return array
     */
    public function getRoutesForSelect2($search = '')
    {
        $allRoutes = $this->getAllRoutes();

        // Filter routes based on search term
        $filteredRoutes = array_filter($allRoutes, function ($route) use ($search) {
            return empty($search) || stripos($route, $search) !== false;
        });

        // Format for Select2
        return array_map(function ($route) {
            return ['id' => $route, 'text' => $route];
        }, array_values($filteredRoutes));
    }

    /**
     * Add a custom route to cache
     * @param string $route
     * @return bool
     */
    public function addCustomRoute($route)
    {
        $routes = $this->getAllRoutes();

        if (in_array($route, $routes)) {
            return false; // Route already exists
        }

        $routes[] = $route;
        $routes = array_unique($routes);
        sort($routes);

        Yii::$app->cache->set(self::CACHE_KEY, $routes, self::CACHE_DURATION);
        return true;
    }

    /**
     * Clear routes cache
     * @return bool
     */
    public function clearCache()
    {
        return Yii::$app->cache->delete(self::CACHE_KEY);
    }

    /**
     * Scan all application routes
     * @return array
     */
    protected function scanAllRoutes()
    {
        $routes = [];

        // Scan backend controllers
        $routes = array_merge($routes, $this->scanControllers('@backend/controllers', 'backend\controllers\\'));

        // Scan frontend controllers  
        $routes = array_merge($routes, $this->scanControllers('@frontend/controllers', 'frontend\controllers\\'));

        // Remove duplicates and sort
        $routes = array_unique($routes);
        sort($routes);

        return $routes;
    }

    /**
     * Scan controllers in a directory
     * @param string $alias
     * @param string $namespace
     * @return array
     */
    protected function scanControllers($alias, $namespace)
    {
        $routes = [];
        $controllerDir = Yii::getAlias($alias);

        if (!is_dir($controllerDir)) {
            return $routes;
        }

        $files = FileHelper::findFiles($controllerDir, ['only' => ['*Controller.php']]);

        foreach ($files as $file) {
            $className = $namespace . basename($file, '.php');

            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

                $controllerRaw = str_replace('Controller', '', basename($file, '.php'));
                $controllerKebab = $this->camelToKebab($controllerRaw);


                foreach ($methods as $method) {
                    if (strpos($method->name, 'action') === 0 && $method->name !== 'actions') {
                        $actionName = substr($method->name, 6); // e.g., actionView -> View
                        $actionKebab = $this->camelToKebab($actionName);

                        $routes[] = "/$controllerKebab/$actionKebab";
                    }
                }
            }
        }

        return $routes;
    }

    /**
     * Convert camelCase to kebab-case
     * @param string $input
     * @return string
     */
    private function camelToKebab($input)
    {
        // Insert hyphens before uppercase letters (except the first character)
        $result = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $input);
        return strtolower($result);
    }
}
