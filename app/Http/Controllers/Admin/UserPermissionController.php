<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AllPage;
use App\Models\User;
use App\Models\UserWebPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class UserPermissionController extends Controller
{
    public function index(User $user): View
    {
        $this->assertFrontendUser($user);
        $routes = AllPage::frontendRouteDefinitions();

        DB::beginTransaction();

        try {
            $nextRank = (int) AllPage::query()->max('page_rank');
            foreach ($routes as $routeDefinition) {
                if (AllPage::query()->where('page_name', $routeDefinition['page_name'])->exists()) {
                    continue;
                }

                AllPage::query()->create([
                    'model_name' => $routeDefinition['module'],
                    'page_title' => $routeDefinition['title'],
                    'page_name' => $routeDefinition['page_name'],
                    'page_rank' => ++$nextRank,
                    'status' => true,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            abort(500, $e->getMessage());
        }

        $pages = $routes->map(function (array $routeDefinition) {
            $page = AllPage::query()->where('status', 1)->where('page_name', $routeDefinition['page_name'])->first();
            if (! $page) {
                return null;
            }

            $page->route_label = $routeDefinition['page_name'];
            $page->display_module = $page->model_name ?: $routeDefinition['module'];

            return $page;
        })->filter()->unique('id')->sortBy(['display_module', 'page_rank', 'id'])->values();
        $assignedPageIds = UserWebPage::query()->where('user_id', $user->id)->where('status', 'Active')->whereIn('page_id', $pages->pluck('id'))->pluck('page_id')->all();

        return view('admin.user-permissions.index', compact('user', 'pages', 'assignedPageIds'));
    }

    public function update(Request $request, User $user)
    {
        $this->assertFrontendUser($user);
        $data = $request->validate([
            'page_ids' => ['nullable', 'array'],
            'page_ids.*' => ['integer', 'distinct'],
        ]);
        $pageIds = array_values(array_unique(array_map('intval', $data['page_ids'] ?? [])));
        $validPageIds = AllPage::query()
            ->where('status', 1)
            ->whereIn('page_name', AllPage::frontendRouteDefinitions()->pluck('page_name'))
            ->whereIn('id', $pageIds)
            ->pluck('id')
            ->all();
        if (count($validPageIds) !== count($pageIds)) {
            return back()->withInput()->withErrors(['page_ids' => 'One or more selected Frontend pages are unavailable.']);
        }

        DB::beginTransaction();

        try {
            $assignments = UserWebPage::query()->where('user_id', $user->id)->lockForUpdate()->get()->groupBy('page_id');
            foreach ($assignments as $pageId => $records) {
                $record = $records->sortByDesc('id')->first();
                if (in_array((int) $pageId, $pageIds, true)) {
                    $record->status = 'Active';
                    $record->modified = now();
                    $record->save();

                    continue;
                }

                $records->where('status', 'Active')->each(function (UserWebPage $assignment): void {
                    $assignment->status = 'Inactive';
                    $assignment->modified = now();
                    $assignment->save();
                });
            }

            foreach (array_diff($pageIds, $assignments->keys()->map(fn ($id): int => (int) $id)->all()) as $pageId) {
                UserWebPage::query()->create(['user_id' => $user->id, 'page_id' => $pageId, 'created' => now(), 'modified' => now(), 'status' => 'Active']);
            }

            DB::commit();

            return redirect()->route('admin.users.permissions.edit', $user)->with('message', 'Frontend page permissions updated.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['page_ids' => $e->getMessage()]);
        }
    }

    private function assertFrontendUser(User $user): void
    {
        abort_unless($user->user_type === 'User' && $user->status === 'Active', 404);
    }
}
