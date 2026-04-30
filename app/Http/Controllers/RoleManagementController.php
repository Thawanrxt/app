<?php

namespace App\Http\Controllers;

use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    private array $tableExistsCache = [];

    private array $columnExistsCache = [];

    public function index(): View
    {
        abort_unless(AdminAccess::isSuperAdmin(auth()->user()), 403);

        $settings = $this->pageContext();
        $roles = collect();

        if ($settings['rolesAvailable']) {
            $this->syncPermissionRows();
            $this->syncActionPermissionRows();

            $menuPermissionCounts = $settings['permissionsAvailable']
                ? DB::table('role_menu_permissions')
                    ->select('role_code', DB::raw('count(*) as total_count'), DB::raw('sum(case when can_view then 1 else 0 end) as visible_count'))
                    ->groupBy('role_code')
                    ->get()
                    ->keyBy('role_code')
                : collect();

            $actionPermissionCounts = $settings['actionPermissionsAvailable']
                ? DB::table('role_action_permissions')
                    ->select('role_code', DB::raw('count(*) as total_count'), DB::raw('sum(case when is_allowed then 1 else 0 end) as allowed_count'))
                    ->groupBy('role_code')
                    ->get()
                    ->keyBy('role_code')
                : collect();

            $roles = DB::table('roles')
                ->select('id', 'code', 'name_th', 'description', 'is_active', 'sort_order')
                ->orderBy('sort_order')
                ->orderBy('code')
                ->get()
                ->map(function ($role) use ($menuPermissionCounts, $actionPermissionCounts) {
                    $menuCounts = $menuPermissionCounts->get($role->code);
                    $actionCounts = $actionPermissionCounts->get($role->code);

                    $role->visible_menu_count = (int) ($menuCounts->visible_count ?? 0);
                    $role->total_menu_count = (int) ($menuCounts->total_count ?? 0);
                    $role->allowed_action_count = (int) ($actionCounts->allowed_count ?? 0);
                    $role->total_action_count = (int) ($actionCounts->total_count ?? 0);

                    return $role;
                });
        }

        return view('admin.roles', [
            'roles' => $roles,
            'rolesAvailable' => $settings['rolesAvailable'],
            'permissionsAvailable' => $settings['permissionsAvailable'],
            'actionPermissionsAvailable' => $settings['actionPermissionsAvailable'],
        ]);
    }

    public function create(): View
    {
        abort_unless(AdminAccess::isSuperAdmin(auth()->user()), 403);

        $settings = $this->pageContext();

        return view('admin.roles-create', [
            'rolesAvailable' => $settings['rolesAvailable'],
            'permissionsAvailable' => $settings['permissionsAvailable'],
            'actionPermissionsAvailable' => $settings['actionPermissionsAvailable'],
            'menuGroups' => $this->permissionGroups(),
            'actionGroups' => $this->actionPermissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(AdminAccess::isSuperAdmin(auth()->user()), 403);

        if (! $this->hasTable('roles')) {
            return redirect('/admin/roles')->with('error', 'ยังไม่มีตารางทะเบียนบทบาทในฐานข้อมูล');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:roles,code'],
            'name_th' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $roleCode = strtoupper(trim((string) $validated['code']));

        DB::transaction(function () use ($request, $validated, $roleCode): void {
            DB::table('roles')->insert([
                'code' => $roleCode,
                'name_th' => $validated['name_th'],
                'description' => $validated['description'] ?: null,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncPermissionRows($roleCode);
            $this->syncActionPermissionRows($roleCode);
            $this->saveMenuPermissions($roleCode, $request->input('permissions', []));
            $this->saveActionPermissions($roleCode, $request->input('action_permissions', []));
        });

        return redirect('/admin/roles')->with('success', 'บันทึกบทบาทเรียบร้อยแล้ว');
    }

    public function edit(string $role): View
    {
        abort_unless(AdminAccess::isSuperAdmin(auth()->user()), 403);

        $settings = $this->pageContext();
        $roleRecord = $this->findRole($role);

        abort_if($roleRecord === null, 404);

        $this->syncPermissionRows($roleRecord->code);
        $this->syncActionPermissionRows($roleRecord->code);

        return view('admin.roles-edit', [
            'roleRecord' => $roleRecord,
            'rolesAvailable' => $settings['rolesAvailable'],
            'permissionsAvailable' => $settings['permissionsAvailable'],
            'actionPermissionsAvailable' => $settings['actionPermissionsAvailable'],
            'menuGroups' => $this->permissionGroups(),
            'permissionMap' => $this->permissionMap($roleRecord->code),
            'actionGroups' => $this->actionPermissionGroups(),
            'actionPermissionMap' => $this->actionPermissionMap($roleRecord->code),
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        abort_unless(AdminAccess::isSuperAdmin(auth()->user()), 403);

        $roleRecord = $this->findRole($role);

        abort_if($roleRecord === null, 404);

        $validated = $request->validate([
            'name_th' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $validated, $roleRecord): void {
            DB::table('roles')
                ->where('code', $roleRecord->code)
                ->update([
                    'name_th' => $validated['name_th'],
                    'description' => $validated['description'] ?: null,
                    'is_active' => $request->boolean('is_active', true),
                    'sort_order' => (int) ($validated['sort_order'] ?? 0),
                    'updated_at' => now(),
                ]);

            $this->syncPermissionRows($roleRecord->code);
            $this->syncActionPermissionRows($roleRecord->code);
            $this->saveMenuPermissions($roleRecord->code, $request->input('permissions', []));
            $this->saveActionPermissions($roleRecord->code, $request->input('action_permissions', []));
        });

        return redirect('/admin/roles')->with('success', 'อัปเดตบทบาทเรียบร้อยแล้ว');
    }

    public function destroy(string $role): RedirectResponse
    {
        abort_unless(AdminAccess::isSuperAdmin(auth()->user()), 403);

        $roleRecord = $this->findRole($role);

        abort_if($roleRecord === null, 404);

        if ($roleRecord->code === 'ADMIN') {
            return redirect('/admin/roles')->with('error', 'ไม่สามารถลบบทบาท ADMIN ได้');
        }

        $isInUse = DB::table('users')
            ->where('role', $roleRecord->code)
            ->exists();

        if ($isInUse) {
            return redirect('/admin/roles')->with('error', 'ไม่สามารถลบบทบาทที่ยังมีผู้ใช้งานอยู่ได้');
        }

        DB::transaction(function () use ($roleRecord): void {
            if ($this->hasTable('role_menu_permissions')) {
                DB::table('role_menu_permissions')
                    ->where('role_code', $roleRecord->code)
                    ->delete();
            }

            if ($this->hasTable('role_action_permissions')) {
                DB::table('role_action_permissions')
                    ->where('role_code', $roleRecord->code)
                    ->delete();
            }

            DB::table('roles')
                ->where('code', $roleRecord->code)
                ->delete();
        });

        return redirect('/admin/roles')->with('success', 'ลบบทบาทเรียบร้อยแล้ว');
    }

    private function findRole(string $roleCode): ?object
    {
        if (! $this->hasTable('roles')) {
            return null;
        }

        return DB::table('roles')
            ->select('id', 'code', 'name_th', 'description', 'is_active', 'sort_order')
            ->where('code', $roleCode)
            ->first();
    }

    private function pageContext(): array
    {
        return [
            'rolesAvailable' => $this->hasTable('roles'),
            'permissionsAvailable' => $this->hasTable('role_menu_permissions'),
            'actionPermissionsAvailable' => $this->hasTable('role_action_permissions'),
        ];
    }

    private function saveMenuPermissions(string $roleCode, array $selectedKeys): void
    {
        if (! $this->hasTable('role_menu_permissions')) {
            return;
        }

        $selectedKeys = collect($selectedKeys)
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($this->menuDefinitions() as $menu) {
            DB::table('role_menu_permissions')
                ->where('role_code', $roleCode)
                ->where('menu_key', $menu['menu_key'])
                ->update([
                    'menu_label' => $menu['menu_label'],
                    'menu_group' => $menu['menu_group'],
                    'can_view' => in_array($menu['menu_key'], $selectedKeys, true),
                    'sort_order' => $menu['sort_order'],
                    'updated_at' => now(),
                ]);
        }
    }

    private function saveActionPermissions(string $roleCode, array $selectedKeys): void
    {
        if (! $this->hasTable('role_action_permissions')) {
            return;
        }

        $selectedKeys = collect($selectedKeys)
            ->map(fn ($value) => (string) $value)
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($this->actionDefinitions() as $action) {
            $compositeKey = $this->actionPermissionKey($action['resource_key'], $action['action_key']);

            DB::table('role_action_permissions')
                ->where('role_code', $roleCode)
                ->where('resource_key', $action['resource_key'])
                ->where('action_key', $action['action_key'])
                ->update([
                    'resource_label' => $action['resource_label'],
                    'action_label' => $action['action_label'],
                    'action_group' => $action['action_group'],
                    'is_allowed' => in_array($compositeKey, $selectedKeys, true),
                    'sort_order' => $action['sort_order'],
                    'updated_at' => now(),
                ]);
        }
    }

    private function syncPermissionRows(?string $roleCode = null): void
    {
        if (! $this->hasTable('roles') || ! $this->hasTable('role_menu_permissions')) {
            return;
        }

        $roleCodes = DB::table('roles')
            ->when($this->hasColumn('roles', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->when($roleCode !== null, fn ($query) => $query->where('code', $roleCode))
            ->pluck('code')
            ->all();

        if ($roleCodes === []) {
            return;
        }

        $menuDefinitions = $this->menuDefinitions();
        $menuKeys = collect($menuDefinitions)->pluck('menu_key')->all();
        $now = now();

        DB::table('role_menu_permissions')
            ->whereIn('role_code', $roleCodes)
            ->whereNotIn('menu_key', $menuKeys)
            ->delete();

        $existingRows = DB::table('role_menu_permissions')
            ->whereIn('role_code', $roleCodes)
            ->get(['role_code', 'menu_key', 'can_view'])
            ->keyBy(fn ($row) => $row->role_code . '|' . $row->menu_key);

        $rows = [];

        foreach ($roleCodes as $code) {
            foreach ($menuDefinitions as $menu) {
                $existing = $existingRows->get($code . '|' . $menu['menu_key']);

                $rows[] = [
                    'role_code' => $code,
                    'menu_key' => $menu['menu_key'],
                    'menu_label' => $menu['menu_label'],
                    'menu_group' => $menu['menu_group'],
                    'can_view' => $code === 'FARMER'
                        ? false
                        : (bool) ($existing->can_view ?? $this->defaultCanViewForRole($code, $menu['menu_key'])),
                    'sort_order' => $menu['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('role_menu_permissions')->upsert(
            $rows,
            ['role_code', 'menu_key'],
            ['menu_label', 'menu_group', 'can_view', 'sort_order', 'updated_at']
        );
    }

    private function syncActionPermissionRows(?string $roleCode = null): void
    {
        if (! $this->hasTable('roles') || ! $this->hasTable('role_action_permissions')) {
            return;
        }

        $roleCodes = DB::table('roles')
            ->when($this->hasColumn('roles', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->when($roleCode !== null, fn ($query) => $query->where('code', $roleCode))
            ->pluck('code')
            ->all();

        if ($roleCodes === []) {
            return;
        }

        $actionDefinitions = $this->actionDefinitions();
        $now = now();
        $validActionPairs = collect($actionDefinitions)
            ->map(fn (array $action): string => $action['resource_key'] . '|' . $action['action_key'])
            ->all();

        DB::table('role_action_permissions')
            ->whereIn('role_code', $roleCodes)
            ->get(['role_code', 'resource_key', 'action_key'])
            ->filter(fn ($row): bool => ! in_array($row->resource_key . '|' . $row->action_key, $validActionPairs, true))
            ->each(function ($row): void {
                DB::table('role_action_permissions')
                    ->where('role_code', $row->role_code)
                    ->where('resource_key', $row->resource_key)
                    ->where('action_key', $row->action_key)
                    ->delete();
            });

        $existingRows = DB::table('role_action_permissions')
            ->whereIn('role_code', $roleCodes)
            ->get(['role_code', 'resource_key', 'action_key', 'is_allowed'])
            ->keyBy(fn ($row) => $row->role_code . '|' . $row->resource_key . '|' . $row->action_key);

        $rows = [];

        foreach ($roleCodes as $code) {
            foreach ($actionDefinitions as $action) {
                $existing = $existingRows->get($code . '|' . $action['resource_key'] . '|' . $action['action_key']);

                $rows[] = [
                    'role_code' => $code,
                    'resource_key' => $action['resource_key'],
                    'resource_label' => $action['resource_label'],
                    'action_key' => $action['action_key'],
                    'action_label' => $action['action_label'],
                    'action_group' => $action['action_group'],
                    'is_allowed' => (bool) ($existing->is_allowed ?? $this->defaultActionAllowedForRole($code, $action['resource_key'], $action['action_key'])),
                    'sort_order' => $action['sort_order'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('role_action_permissions')->upsert(
            $rows,
            ['role_code', 'resource_key', 'action_key'],
            ['resource_label', 'action_label', 'action_group', 'is_allowed', 'sort_order', 'updated_at']
        );
    }

    private function permissionGroups(): array
    {
        $labels = [
            'general' => 'เมนูหลัก',
            'tracking' => 'เมนูข้อมูลการติดตาม',
            'srp' => 'เมนู SRP',
            'report' => 'เมนูรายงาน',
        ];

        $grouped = [];

        foreach ($this->menuDefinitions() as $menu) {
            $groupKey = $menu['menu_group'] ?: 'general';

            $grouped[$groupKey] ??= [
                'label' => $labels[$groupKey] ?? 'เมนูอื่น ๆ',
                'items' => [],
            ];

            $grouped[$groupKey]['items'][] = $menu;
        }

        return array_values($grouped);
    }

    private function actionPermissionGroups(): array
    {
        $labels = [
            'general' => 'สิทธิ์ทั่วไป',
            'tracking' => 'สิทธิ์งานติดตาม',
            'report' => 'สิทธิ์รายงาน',
            'admin' => 'สิทธิ์ผู้ดูแลระบบ',
        ];

        $grouped = [];

        foreach ($this->actionDefinitions() as $definition) {
            $groupKey = $definition['action_group'] ?: 'general';
            $resourceKey = $definition['resource_key'];

            $grouped[$groupKey] ??= [
                'label' => $labels[$groupKey] ?? 'สิทธิ์อื่น ๆ',
                'resources' => [],
            ];

            $grouped[$groupKey]['resources'][$resourceKey] ??= [
                'resource_key' => $resourceKey,
                'resource_label' => $definition['resource_label'],
                'items' => [],
            ];

            $grouped[$groupKey]['resources'][$resourceKey]['items'][] = $definition;
        }

        return array_map(function (array $group) {
            $group['resources'] = array_values($group['resources']);

            return $group;
        }, array_values($grouped));
    }

    private function permissionMap(string $roleCode): array
    {
        if (! $this->hasTable('role_menu_permissions')) {
            return [];
        }

        return DB::table('role_menu_permissions')
            ->where('role_code', $roleCode)
            ->pluck('can_view', 'menu_key')
            ->map(fn ($value) => (bool) $value)
            ->all();
    }

    private function actionPermissionMap(string $roleCode): array
    {
        if (! $this->hasTable('role_action_permissions')) {
            return [];
        }

        return DB::table('role_action_permissions')
            ->select('resource_key', 'action_key', 'is_allowed')
            ->where('role_code', $roleCode)
            ->get()
            ->mapWithKeys(fn ($row) => [
                $this->actionPermissionKey($row->resource_key, $row->action_key) => (bool) $row->is_allowed,
            ])
            ->all();
    }

    private function menuDefinitions(): array
    {
        return [
            ['menu_key' => 'dashboard', 'menu_label' => 'แดชบอร์ด', 'menu_group' => null, 'sort_order' => 10],
            ['menu_key' => 'admin_users', 'menu_label' => 'ผู้ดูแลระบบ', 'menu_group' => null, 'sort_order' => 20],
            ['menu_key' => 'farmer_users', 'menu_label' => 'ผู้ใช้งาน', 'menu_group' => null, 'sort_order' => 30],
            ['menu_key' => 'roles', 'menu_label' => 'ทะเบียนบทบาท', 'menu_group' => null, 'sort_order' => 40],
            ['menu_key' => 'tracking_prep', 'menu_label' => 'การเตรียมดิน', 'menu_group' => 'tracking', 'sort_order' => 50],
            ['menu_key' => 'tracking_water', 'menu_label' => 'การจัดการน้ำ', 'menu_group' => 'tracking', 'sort_order' => 60],
            ['menu_key' => 'tracking_fertilizer', 'menu_label' => 'หว่านปุ๋ย', 'menu_group' => 'tracking', 'sort_order' => 70],
            ['menu_key' => 'tracking_pest', 'menu_label' => 'การจัดการศัตรูพืช', 'menu_group' => 'tracking', 'sort_order' => 80],
            ['menu_key' => 'tracking_disease', 'menu_label' => 'การจัดการโรคพืช', 'menu_group' => 'tracking', 'sort_order' => 90],
            ['menu_key' => 'tracking_harvest', 'menu_label' => 'การเก็บเกี่ยว', 'menu_group' => 'tracking', 'sort_order' => 100],
            ['menu_key' => 'tracking_mill', 'menu_label' => 'ขายข้าวเข้าโรงสี', 'menu_group' => 'tracking', 'sort_order' => 110],
            ['menu_key' => 'srp_manual', 'menu_label' => 'คู่มือมาตรฐาน SRP', 'menu_group' => 'srp', 'sort_order' => 120],
            ['menu_key' => 'srp_farmers', 'menu_label' => 'ข้อมูลเกษตรกร', 'menu_group' => 'srp', 'sort_order' => 130],
            ['menu_key' => 'rice', 'menu_label' => 'พันธุ์ข้าว', 'menu_group' => null, 'sort_order' => 140],
            ['menu_key' => 'report_rice', 'menu_label' => 'รายงานปัญหาการปลูกข้าว', 'menu_group' => 'report', 'sort_order' => 150],
            ['menu_key' => 'report_system', 'menu_label' => 'รายงานปัญหาการใช้งานระบบ', 'menu_group' => 'report', 'sort_order' => 160],
            ['menu_key' => 'settings', 'menu_label' => 'ตั้งค่า', 'menu_group' => null, 'sort_order' => 170],
        ];
    }

    private function actionDefinitions(): array
    {
        return [
            ['resource_key' => 'dashboard', 'resource_label' => 'แดชบอร์ด', 'action_key' => 'view', 'action_label' => 'ดู', 'action_group' => 'general', 'sort_order' => 10],
            ['resource_key' => 'dashboard', 'resource_label' => 'แดชบอร์ด', 'action_key' => 'manage', 'action_label' => 'จัดการการแจ้งเตือน', 'action_group' => 'general', 'sort_order' => 20],

            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'view', 'action_label' => 'ดูรายการ/รายละเอียด', 'action_group' => 'general', 'sort_order' => 30],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'create', 'action_label' => 'เพิ่มผู้ใช้งาน', 'action_group' => 'general', 'sort_order' => 40],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'edit', 'action_label' => 'แก้ไขข้อมูล', 'action_group' => 'general', 'sort_order' => 50],
            ['resource_key' => 'farmer_users', 'resource_label' => 'ผู้ใช้งานเกษตรกร', 'action_key' => 'delete', 'action_label' => 'ลบข้อมูล', 'action_group' => 'general', 'sort_order' => 60],

            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'view', 'action_label' => 'ดูรายการ/รายละเอียด', 'action_group' => 'admin', 'sort_order' => 70],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'create', 'action_label' => 'เพิ่มผู้ดูแล', 'action_group' => 'admin', 'sort_order' => 80],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'edit', 'action_label' => 'แก้ไขผู้ดูแล', 'action_group' => 'admin', 'sort_order' => 90],
            ['resource_key' => 'admin_users', 'resource_label' => 'ผู้ดูแลระบบ', 'action_key' => 'delete', 'action_label' => 'ลบผู้ดูแล', 'action_group' => 'admin', 'sort_order' => 100],

            ['resource_key' => 'roles', 'resource_label' => 'ทะเบียนบทบาท', 'action_key' => 'view', 'action_label' => 'ดูรายการบทบาท', 'action_group' => 'admin', 'sort_order' => 110],
            ['resource_key' => 'roles', 'resource_label' => 'ทะเบียนบทบาท', 'action_key' => 'manage', 'action_label' => 'จัดการสิทธิ์บทบาท', 'action_group' => 'admin', 'sort_order' => 120],

            ['resource_key' => 'srp_farmers', 'resource_label' => 'ข้อมูลเกษตรกร', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'general', 'sort_order' => 130],
            ['resource_key' => 'srp_manual', 'resource_label' => 'คู่มือมาตรฐาน SRP', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'general', 'sort_order' => 140],

            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'view', 'action_label' => 'ดูรายการ', 'action_group' => 'general', 'sort_order' => 150],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'create', 'action_label' => 'เพิ่มข้อมูล', 'action_group' => 'general', 'sort_order' => 160],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'edit', 'action_label' => 'แก้ไขข้อมูล', 'action_group' => 'general', 'sort_order' => 170],
            ['resource_key' => 'rice', 'resource_label' => 'พันธุ์ข้าว', 'action_key' => 'delete', 'action_label' => 'ลบ/กู้คืนข้อมูล', 'action_group' => 'general', 'sort_order' => 180],

            ['resource_key' => 'settings', 'resource_label' => 'ตั้งค่า', 'action_key' => 'view', 'action_label' => 'ดูหน้าตั้งค่า', 'action_group' => 'admin', 'sort_order' => 190],
            ['resource_key' => 'settings', 'resource_label' => 'ตั้งค่า', 'action_key' => 'edit', 'action_label' => 'แก้ไขค่าในระบบ', 'action_group' => 'admin', 'sort_order' => 200],

            ['resource_key' => 'report_rice', 'resource_label' => 'รายงานปัญหาการปลูกข้าว', 'action_key' => 'view', 'action_label' => 'ดูรายงาน', 'action_group' => 'report', 'sort_order' => 210],
            ['resource_key' => 'report_rice', 'resource_label' => 'รายงานปัญหาการปลูกข้าว', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'report', 'sort_order' => 220],
            ['resource_key' => 'report_system', 'resource_label' => 'รายงานปัญหาระบบ', 'action_key' => 'view', 'action_label' => 'ดูรายงาน', 'action_group' => 'report', 'sort_order' => 230],
            ['resource_key' => 'report_system', 'resource_label' => 'รายงานปัญหาระบบ', 'action_key' => 'delete', 'action_label' => 'ลบรายการ', 'action_group' => 'report', 'sort_order' => 240],

            ['resource_key' => 'tracking_prep', 'resource_label' => 'ติดตามการเตรียมดิน', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 250],
            ['resource_key' => 'tracking_prep', 'resource_label' => 'ติดตามการเตรียมดิน', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 260],
            ['resource_key' => 'tracking_prep', 'resource_label' => 'ติดตามการเตรียมดิน', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 270],

            ['resource_key' => 'tracking_water', 'resource_label' => 'ติดตามการจัดการน้ำ', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 280],
            ['resource_key' => 'tracking_water', 'resource_label' => 'ติดตามการจัดการน้ำ', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 290],
            ['resource_key' => 'tracking_water', 'resource_label' => 'ติดตามการจัดการน้ำ', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 300],

            ['resource_key' => 'tracking_fertilizer', 'resource_label' => 'ติดตามการหว่านปุ๋ย', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 310],
            ['resource_key' => 'tracking_fertilizer', 'resource_label' => 'ติดตามการหว่านปุ๋ย', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 320],
            ['resource_key' => 'tracking_fertilizer', 'resource_label' => 'ติดตามการหว่านปุ๋ย', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 330],

            ['resource_key' => 'tracking_pest', 'resource_label' => 'ติดตามศัตรูพืช', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 340],
            ['resource_key' => 'tracking_pest', 'resource_label' => 'ติดตามศัตรูพืช', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 350],
            ['resource_key' => 'tracking_pest', 'resource_label' => 'ติดตามศัตรูพืช', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 360],

            ['resource_key' => 'tracking_disease', 'resource_label' => 'ติดตามโรคพืช', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 370],
            ['resource_key' => 'tracking_disease', 'resource_label' => 'ติดตามโรคพืช', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 380],
            ['resource_key' => 'tracking_disease', 'resource_label' => 'ติดตามโรคพืช', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 390],

            ['resource_key' => 'tracking_harvest', 'resource_label' => 'ติดตามการเก็บเกี่ยว', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 400],
            ['resource_key' => 'tracking_harvest', 'resource_label' => 'ติดตามการเก็บเกี่ยว', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 410],
            ['resource_key' => 'tracking_harvest', 'resource_label' => 'ติดตามการเก็บเกี่ยว', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 420],

            ['resource_key' => 'tracking_mill', 'resource_label' => 'ติดตามการขายเข้าโรงสี', 'action_key' => 'view', 'action_label' => 'ดูข้อมูล', 'action_group' => 'tracking', 'sort_order' => 430],
            ['resource_key' => 'tracking_mill', 'resource_label' => 'ติดตามการขายเข้าโรงสี', 'action_key' => 'manage', 'action_label' => 'อัปเดตสถานะ/ลบ', 'action_group' => 'tracking', 'sort_order' => 440],
            ['resource_key' => 'tracking_mill', 'resource_label' => 'ติดตามการขายเข้าโรงสี', 'action_key' => 'export', 'action_label' => 'พิมพ์/ส่งออก', 'action_group' => 'tracking', 'sort_order' => 450],
        ];
    }

    private function defaultCanViewForRole(string $roleCode, string $menuKey): bool
    {
        $roleCode = strtoupper(trim($roleCode));

        if ($roleCode === 'SUPERADMIN') {
            return true;
        }

        if ($roleCode === 'ADMIN') {
            return ! in_array($menuKey, ['admin_users', 'roles'], true);
        }

        return false;
    }

    private function defaultActionAllowedForRole(string $roleCode, string $resourceKey, string $actionKey): bool
    {
        $roleCode = strtoupper(trim($roleCode));

        if ($roleCode === 'SUPERADMIN') {
            return true;
        }

        if ($roleCode !== 'ADMIN') {
            return false;
        }

        if (in_array($resourceKey, ['admin_users', 'roles'], true)) {
            return false;
        }

        return true;
    }

    private function actionPermissionKey(string $resourceKey, string $actionKey): string
    {
        return "{$resourceKey}.{$actionKey}";
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            return $this->tableExistsCache[$table] = Schema::hasTable($table);
        } catch (\Throwable) {
            return $this->tableExistsCache[$table] = false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = "{$table}.{$column}";

        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        try {
            return $this->columnExistsCache[$cacheKey] = Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return $this->columnExistsCache[$cacheKey] = false;
        }
    }
}
