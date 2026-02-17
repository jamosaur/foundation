<?php

declare(strict_types=1);

namespace Jamosaur\Foundation\Middleware;

use Closure;
use Illuminate\Http\Request;

use function count;
use function is_string;

class ApiRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $namespacedAction = $request->route()->action['uses'] ??
            $request->route()->action['controller'] ??
            null;

        if (is_string($namespacedAction)) {
            $exploded = explode('\\', $namespacedAction);

            if ($exploded) {
                $controllerMethod = $exploded[count($exploded) - 1];

                [$controller, $action] = explode('@', $controllerMethod, 2);

                $request->attributes->add([
                    '_controller' => lcfirst(str_replace('Controller', '', $controller)),
                    '_action' => lcfirst(str_replace('Action', '', $action)),
                ]);
            }

            return $next($request);
        }

        return $next($request);
    }
}
