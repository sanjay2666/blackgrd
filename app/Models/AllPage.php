<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;

class AllPage extends Model
{
    use HasFactory;

    protected $table = 'all_pages';

    public $timestamps = false;

    protected $guarded = [];

    public function userWebPages()
    {
        return $this->hasMany(UserWebPage::class, 'page_id', 'id');
    }

    /** @return Collection<int, array{page_name:string,candidates:list<string>,module:string,title:string}> */
    public static function frontendRouteDefinitions(): Collection
    {
        return collect(app('router')->getRoutes()->getRoutes())
            ->filter(function (Route $route): bool {
                $middleware = collect($route->gatherMiddleware());

                return ! str_starts_with($route->uri(), 'admin')
                    && ($middleware->contains('auth:web') || $middleware->contains('auth:web,admin'));
            })
            ->flatMap(function (Route $route): Collection {
                $uri = trim($route->uri(), '/');
                $methods = collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD');
                $action = class_basename(strtok((string) $route->getActionName(), '@'));
                $module = trim(str_replace('Controller', '', $action)) ?: 'Frontend';
                $label = (string) ($route->getName() ?: ($uri ?: 'Home'));

                return $methods->map(function (string $method) use ($uri, $module, $label, $route): array {
                    $pageName = strtoupper($method).' /'.($uri ?: '/');

                    return [
                        'page_name' => $pageName,
                        'candidates' => array_values(array_unique(array_filter([$pageName, $uri, $route->getName()]))),
                        'module' => $module,
                        'title' => ucfirst(str_replace(['.', '-', '_', '/'], ' ', $label)).' ('.strtoupper($method).')',
                    ];
                });
            })
            ->values();
    }

    public static function findForFrontendRoute(Route $route, string $method): ?self
    {
        $uri = trim($route->uri(), '/');
        $pageName = strtoupper($method).' /'.($uri ?: '/');
        $candidates = array_values(array_unique(array_filter([$pageName, $uri, $route->getName()])));
        $pages = self::query()->where('status', true)->whereIn('page_name', $candidates)->get();

        foreach ($candidates as $candidate) {
            if ($page = $pages->firstWhere('page_name', $candidate)) {
                return $page;
            }
        }

        return null;
    }
}
