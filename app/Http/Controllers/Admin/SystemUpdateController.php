<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminPermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\SystemUpdate;
use App\Services\SystemUpdater;
use App\Traits\ChecksPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SystemUpdateController extends Controller
{
    use ChecksPermissions;

    public function index(SystemUpdater $updater): View
    {
        $columns = [
            ['data' => 'id', 'name' => 'id', 'title' => __('labels.id'), 'label' => __('labels.id')],
            ['data' => 'version', 'name' => 'version', 'title' => __('labels.version'), 'label' => __('labels.version')],
            ['data' => 'package_name', 'name' => 'package_name', 'title' => __('labels.package'), 'label' => __('labels.package')],
            ['data' => 'status', 'name' => 'status', 'title' => __('labels.status'), 'label' => __('labels.status')],
            ['data' => 'applied_by', 'name' => 'applied_by', 'title' => __('labels.applied_by'), 'label' => __('labels.applied_by')],
            ['data' => 'applied_at', 'name' => 'applied_at', 'title' => __('labels.applied_at'), 'label' => __('labels.applied_at')],
            ['data' => 'action', 'name' => 'action', 'title' => __('labels.action'), 'label' => __('labels.action'), 'orderable' => false, 'searchable' => false],
        ];
        $canUpdate = $this->hasPermission(AdminPermissionEnum::SETTING_SYSTEM_EDIT());
        $currentVersion = $updater->getCurrentVersion();
        return view('admin.system-updates.index', compact('columns', 'canUpdate', 'currentVersion'));
    }

    public function store(Request $request, SystemUpdater $updater): RedirectResponse
    {
        if (! $this->hasPermission(AdminPermissionEnum::SETTING_SYSTEM_EDIT())) {
            return back()->withErrors(['error' => 'Unauthorized']);
        }

        $request->validate([
            'package' => ['required', 'file', 'mimetypes:application/zip,application/x-zip-compressed,application/octet-stream', 'max:51200'],
        ]);

        try {
            $userId = Auth::id() ?? 0;
            $update = $updater->apply($request->file('package'), $userId);
            return back()
                ->with('success', __('labels.system_updated_successfully'))
                ->with('update_log', $update->log)
                ->with('update_version', $update->version)
                ->with('update_id', $update->id);
        } catch (\Throwable $e) {
            Log::error('System update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            // Try to surface the latest failed update log to the UI
            try {
                $userId = Auth::id() ?? 0;
                $last = SystemUpdate::orderByDesc('id')->first();
                if ($last && $last->status === 'failed') {
                    return back()
                        ->withErrors(['error' => 'Update failed: ' . $e->getMessage()])
                        ->with('update_log', $last->log)
                        ->with('update_version', $last->version)
                        ->with('update_id', $last->id);
                }
            } catch (\Throwable) { /* ignore */ }
            return back()->withErrors(['error' => 'Update failed: ' . $e->getMessage()]);
        }
    }

    // Live log endpoints (AJAX)
    public function latest(Request $request)
    {
        if (! $this->hasPermission(AdminPermissionEnum::SETTING_SYSTEM_EDIT())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $last = SystemUpdate::orderByDesc('id')->first();
        if (! $last) return response()->json(null);
        return response()->json([
            'id' => $last->id,
            'version' => $last->version,
            'status' => $last->status,
            'log' => $last->log,
            'applied_at' => optional($last->applied_at)->toDateTimeString(),
        ]);
    }

    public function showLog(Request $request, SystemUpdate $update)
    {
        if (! $this->hasPermission(AdminPermissionEnum::SETTING_SYSTEM_EDIT())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'id' => $update->id,
            'version' => $update->version,
            'status' => $update->status,
            'log' => $update->log,
            'applied_at' => optional($update->applied_at)->toDateTimeString(),
        ]);
    }
    /**
     * DataTable endpoint for system updates
     */
    public function datatable(Request $request)
    {
        $query = SystemUpdate::query()->with('appliedBy');

        $totalRecords = SystemUpdate::count();
        $filteredRecords = $totalRecords;

        // Search
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                  ->orWhere('package_name', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
            $filteredRecords = (clone $query)->count();
        }

        // Order
        if ($request->has('order')) {
            $orderColumn = $request->columns[$request->order[0]['column']]['data'] ?? 'id';
            $orderDirection = $request->order[0]['dir'] ?? 'desc';
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            $query->orderByDesc('id');
        }

        // Pagination
        if ($request->has('start') && $request->has('length')) {
            $query->skip(intval($request->start))->take(intval($request->length));
        }

        $rows = $query->get();

        $data = $rows->map(function (SystemUpdate $item) {
            $statusClass = $item->status === 'applied' ? 'bg-success-lt' : ($item->status === 'failed' ? 'bg-danger-lt' : 'bg-secondary-lt');
            $statusHtml = "<span class='badge {$statusClass}'>" . ucfirst($item->status) . "</span>";
            $appliedBy = optional($item->appliedBy)->name ?? __('labels.system');
            $appliedAt = optional($item->applied_at)?->format('Y-m-d H:i');
            $action = '<a class="btn btn-sm btn-outline-primary" href="' . route('admin.system-updates.log', ['update' => $item->id]) . '" target="_blank">' . e(__('labels.view_log')) . '</a>';
            return [
                'id' => $item->id,
                'version' => $item->version,
                'package_name' => $item->package_name,
                'status' => $statusHtml,
                'applied_by' => e($appliedBy),
                'applied_at' => $appliedAt,
                'action' => $action,
            ];
        });

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }
}
