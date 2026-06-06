<?php

namespace App\Http\Controllers;

use App\Models\DashboardWorkItem;
use App\Models\User;
use App\Support\AdminAccess;
use App\Support\SearchTextMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    private array $tableExistsCache = [];

    private array $columnExistsCache = [];

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $currentAdmin = Auth::user();
        $activeScopes = AdminAccess::activeScopes($currentAdmin);
        $restrictByScope = AdminAccess::shouldRestrictByScope($currentAdmin);
        $joinProvinces = $this->hasTable('provinces');
        $joinDistricts = $this->hasTable('districts');

        $usersQuery = DB::table('users')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('farmer_registrations as registrations', 'registrations.profile_id', '=', 'profiles.id');

        if ($joinProvinces) {
            $usersQuery->leftJoin('provinces as provinces', 'provinces.id', '=', 'profiles.province_id');
        }

        if ($joinDistricts) {
            $usersQuery->leftJoin('districts as districts', 'districts.id', '=', 'profiles.district_id');
        }

        $users = $usersQuery
            ->select([
                'users.id',
                'users.username',
                $this->optionalUserSelect('email', 'email'),
                'users.phone',
                'users.role',
                $this->optionalUserSelect('member_registered_at', 'member_registered_at'),
                $this->optionalUserSelect('province', 'user_province'),
                $this->optionalUserSelect('district', 'user_district'),
                $this->optionalUserSelect('subdistrict', 'user_subdistrict'),
                'profiles.id as profile_id',
                'profiles.full_name as full_name',
                'profiles.id_card_number',
                'profiles.province_id as profile_province_id',
                'profiles.district_id as profile_district_id',
                $this->optionalTableSelect('profiles', 'subdistrict', 'profile_subdistrict'),
                'registrations.id as registration_id',
                'registrations.reg_number as farmer_code',
                'registrations.reg_date as farmer_registered_at',
                $joinProvinces ? 'provinces.name_th as province_name' : DB::raw('NULL as province_name'),
                $joinDistricts ? 'districts.name_th as district_name' : DB::raw('NULL as district_name'),
            ])
            ->orderBy('profiles.full_name')
            ->orderBy('users.username')
            ->get()
            ->map(function ($user) {
                $user->district_name = $user->district_name ?: $user->user_district ?: $this->fallbackDistrictName($user->profile_district_id ?? null);
                $user->subdistrict_name = $user->profile_subdistrict ?: $user->user_subdistrict;
                $user->province_name = $user->province_name
                    ?: $user->user_province
                    ?: $this->fallbackProvinceName($user->profile_province_id ?? null)
                    ?: $this->fallbackProvinceNameFromDistrict($user->district_name ?? null);

                return $user;
            });

        $users = $users
            ->filter(function ($user) use ($restrictByScope, $activeScopes) {
                if (strtoupper((string) ($user->role ?? '')) !== 'FARMER') {
                    return false;
                }

                if (! $restrictByScope) {
                    return true;
                }

                return AdminAccess::locationMatches(
                    $activeScopes,
                    $user->province_name ?? null,
                    $user->district_name ?? null,
                    $user->subdistrict_name ?? null
                );
            })
            ->values();

        $users = SearchTextMatcher::filterByPriority($users, [
            fn ($user) => $user->full_name,
            fn ($user) => $user->username,
            fn ($user) => $user->farmer_code,
            fn ($user) => $user->phone,
            fn ($user) => $user->province_name,
            fn ($user) => $user->district_name,
            fn ($user) => $user->subdistrict_name,
            fn ($user) => $user->id_card_number,
            fn ($user) => $user->role,
        ], $query);

        return view('admin.farmer-users', [
            'users' => $users,
            'query' => $query,
        ]);
    }

    public function create(): View
    {
        return view('admin.farmer-users-create', [
            'assignableAdminOptions' => $this->assignableAdminOptions(),
            'createdPlotFarmId' => session('created_plot_farm_id'),
            'createdUserId' => session('created_user_id'),
        ]);
    }

    public function edit(string $user): View
    {
        $userRecord = $this->findUserRecord($user);

        abort_if($userRecord === null, 404);
        abort_unless($this->canManageUserRecord($userRecord), 403);

        return view('admin.farmer-users-edit', [
            'userRecord' => $userRecord,
            'roleOptions' => $this->roleOptions(),
            'canManageAdminRoles' => AdminAccess::isSuperAdmin(Auth::user()),
            'assignableAdminOptions' => $this->assignableAdminOptions(),
        ]);
    }

    public function show(string $user): View
    {
        $userRecord = $this->findUserRecord($user);

        abort_if($userRecord === null, 404);
        abort_unless($this->canManageUserRecord($userRecord), 403);

        $plotsQuery = DB::table('plots')
            ->where('user_id', $userRecord->id)
            ->where('status', 'ACTIVE')
            ->select(['id', 'farm_id', 'plot_name', 'area_rai', 'area_ngan', 'area_sq_wa', 'crop_type', 'status']);

        if (Schema::hasColumn('plots', 'is_primary')) {
            $plotsQuery->orderByRaw('COALESCE(is_primary, 0) DESC');
        }

        $plots = $plotsQuery->orderBy('farm_id')->get();

        return view('admin.farmer-users-show', [
            'userRecord' => $userRecord,
            'canManageAdminRoles' => AdminAccess::isSuperAdmin(Auth::user()),
            'assignableAdminOptions' => $this->assignableAdminOptions(),
            'plots' => $plots,
        ]);
    }

    public function storeDraft(Request $request): RedirectResponse
    {
        $request->merge([
            'citizen_id' => preg_replace('/\D+/', '', (string) $request->input('citizen_id')),
            'phone' => preg_replace('/\D+/', '', (string) $request->input('phone')),
            'farmer_code' => preg_replace('/\D+/', '', (string) $request->input('farmer_code')),
        ]);

        $request->merge([
            'role' => 'FARMER',
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => $this->roleValidationRules(false),
            'citizen_id' => ['nullable', 'digits:13'],
            'phone' => ['nullable', 'digits:10'],
            'birth_date' => ['nullable', 'date'],
            'address_line' => ['nullable', 'string', 'max:1000'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:255'],
            'farmer_code' => ['required', 'digits:12', 'unique:farmer_registrations,reg_number'],
            'registered_at' => ['required', 'date'],
            'registered_province' => ['required', 'string', 'max:255'],
            'farm_province' => ['required', 'string', 'max:255'],
            'farm_area_rai' => ['nullable', 'integer', 'min:0'],
            'farm_area_ngan' => ['nullable', 'integer', 'min:0'],
            'farm_area_square_wa' => ['nullable', 'integer', 'min:0'],
            'crop_type' => ['required', 'string', 'max:255'],
            'assigned_admin_user_id' => ['nullable', 'string', Rule::in($this->assignableAdminIds())],
            'secondary_admin_user_ids' => ['nullable', 'array'],
            'secondary_admin_user_ids.*' => ['nullable', 'string', Rule::in($this->assignableAdminIds())],
            'assignment_type' => ['nullable', 'string', Rule::in(['AREA', 'INDIVIDUAL'])],
            'assignment_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! AdminAccess::isSuperAdmin(Auth::user()) && strtoupper((string) ($validated['role'] ?? 'FARMER')) !== 'FARMER') {
            return back()->withErrors([
                'role' => 'ไม่สามารถกำหนดบทบาทอื่นนอกจากเกษตรกรได้',
            ])->withInput();
        }

        $createdPlotFarmId = null;
        $createdUserId = null;

        DB::transaction(function () use ($validated, &$createdPlotFarmId, &$createdUserId): void {
            $userId = (string) Str::uuid();
            $profileId = (string) Str::uuid();
            $createdUserId = $userId;

            $provinceId = $this->resolveProvinceId($validated['province'] ?? null);
            $districtId = $this->resolveDistrictId($validated['district'] ?? null, $provinceId);
            $registeredProvinceId = $this->resolveProvinceId($validated['registered_province'] ?? null);
            $farmProvinceId = $this->resolveProvinceId($validated['farm_province'] ?? null);

            $roleCode = 'FARMER';

            User::query()->create(array_merge(
                [
                    'id' => $userId,
                    'username' => $validated['username'],
                    'phone' => $validated['phone'] ?? null,
                    'role' => $roleCode,
                ],
                $this->memberRegisteredAtPayload(),
                $this->passwordPayload($validated['password']),
                $this->userMirrorPayload(array_merge($validated, [
                    'role' => $roleCode,
                ]))
            ));

            DB::table('farmer_profiles')->insert(array_merge([
                'id' => $profileId,
                'user_id' => $userId,
                'full_name' => $validated['name'],
                'id_card_number' => $validated['citizen_id'] ?? null,
                'birthdate' => $validated['birth_date'] ?? null,
                'address' => $validated['address_line'] ?? null,
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'farmer_type_id' => null,
                'lat_gps_idx' => null,
            ], $this->profilePayload($validated)));

            DB::table('farmer_registrations')->insert([
                'id' => (string) Str::uuid(),
                'reg_number' => $validated['farmer_code'],
                'reg_date' => $validated['registered_at'],
                'reg_province_id' => $registeredProvinceId,
                'profile_id' => $profileId,
            ]);

            $createdPlotFarmId = $this->generateUniqueFarmId();

            DB::table('plots')->insert(array_merge([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'farm_id' => $createdPlotFarmId,
                'plot_name' => 'แปลงหลัก',
                'area_rai' => $validated['farm_area_rai'] ?? null,
                'area_sq_wa' => $validated['farm_area_square_wa'] ?? null,
                'crop_type' => $validated['crop_type'],
                'address' => $validated['address_line'] ?? null,
                'province_id' => $farmProvinceId,
                'district_id' => null,
                'lat' => null,
                'lon' => null,
                'latitude' => null,
                'longitude' => null,
                'status' => 'ACTIVE',
            ], $this->plotPayload($validated, $districtId)));

            $this->syncAdminProfileAndScope($userId, $roleCode, $validated);
            $this->syncFarmerAdminAssignment($userId, $roleCode, $validated);
        });
        return redirect('/admin/farmer-users/create')
            ->with('success', 'บันทึกข้อมูลผู้ใช้งานเรียบร้อยแล้ว')
            ->with('created_plot_farm_id', $createdPlotFarmId)
            ->with('created_user_id', $createdUserId)
            ->withInput(collect($validated)->except(['password'])->all());

        return redirect('/admin/farmer-users')->with('success', 'บันทึกข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function account(Request $request): View|RedirectResponse
    {
        return redirect('/admin/farmer-users/create');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect('/admin/farmer-users/create');

        $draft = $request->session()->get('user_create_draft');

        if (!$draft) {
            return redirect('/admin/farmer-users/create');
        }

        $request->merge([
            'role' => 'FARMER',
        ]);

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => $this->roleValidationRules(false),
            'assigned_admin_user_id' => ['nullable', 'string', Rule::in($this->assignableAdminIds())],
            'secondary_admin_user_ids' => ['nullable', 'array'],
            'secondary_admin_user_ids.*' => ['nullable', 'string', Rule::in($this->assignableAdminIds())],
            'assignment_type' => ['nullable', 'string', Rule::in(['AREA', 'INDIVIDUAL'])],
            'assignment_note' => ['nullable', 'string', 'max:1000'],
        ]);

            if (! AdminAccess::isSuperAdmin(Auth::user()) && strtoupper((string) ($validated['role'] ?? 'FARMER')) !== 'FARMER') {
                return back()->withErrors([
                    'role' => 'ไม่สามารถสร้างผู้ใช้งานนอกเหนือจากเกษตรกรได้',
                ]);
            }

        DB::transaction(function () use ($draft, $validated): void {
            $userId = (string) Str::uuid();
            $profileId = (string) Str::uuid();

            $provinceId = $this->resolveProvinceId($draft['province'] ?? null);
            $districtId = $this->resolveDistrictId($draft['district'] ?? null, $provinceId);
            $registeredProvinceId = $this->resolveProvinceId($draft['registered_province'] ?? null);
            $farmProvinceId = $this->resolveProvinceId($draft['farm_province'] ?? null);

            $roleCode = 'FARMER';

            User::query()->create(array_merge(
                [
                    'id' => $userId,
                    'username' => $validated['username'],
                    'phone' => $draft['phone'] ?? null,
                    'role' => $roleCode,
                ],
                $this->memberRegisteredAtPayload(),
                $this->passwordPayload($validated['password']),
                $this->userMirrorPayload(array_merge($draft, [
                    'username' => $validated['username'],
                    'role' => $roleCode,
                ]))
            ));

            DB::table('farmer_profiles')->insert(array_merge([
                'id' => $profileId,
                'user_id' => $userId,
                'full_name' => $draft['name'],
                'id_card_number' => $draft['citizen_id'] ?? null,
                'birthdate' => $draft['birth_date'] ?? null,
                'address' => $draft['address_line'] ?? null,
                'province_id' => $provinceId,
                'district_id' => $districtId,
                'farmer_type_id' => null,
                'lat_gps_idx' => null,
            ], $this->profilePayload($draft)));

            DB::table('farmer_registrations')->insert([
                'id' => (string) Str::uuid(),
                'reg_number' => $draft['farmer_code'],
                'reg_date' => $draft['registered_at'],
                'reg_province_id' => $registeredProvinceId,
                'profile_id' => $profileId,
            ]);

            DB::table('plots')->insert(array_merge([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'farm_id' => $this->generateUniqueFarmId(),
                'plot_name' => 'แปลงหลัก',
                'area_rai' => $draft['farm_area_rai'] ?? null,
                'area_sq_wa' => $draft['farm_area_square_wa'] ?? null,
                'crop_type' => $draft['crop_type'],
                'address' => $draft['address_line'] ?? null,
                'province_id' => $farmProvinceId,
                'district_id' => null,
                'lat' => null,
                'lon' => null,
                'latitude' => null,
                'longitude' => null,
                'status' => 'ACTIVE',
            ], $this->plotPayload($draft, $districtId)));

            $attributes = array_merge($draft, $validated);

            $this->syncAdminProfileAndScope($userId, $roleCode, $attributes);
            $this->syncFarmerAdminAssignment($userId, $roleCode, $attributes);
        });

        $request->session()->forget('user_create_draft');

        return redirect('/admin/farmer-users')->with('success', 'บันทึกผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function update(Request $request, string $user): RedirectResponse
    {
        $userRecord = $this->findUserRecord($user);

        abort_if($userRecord === null, 404);
        abort_unless($this->canManageUserRecord($userRecord), 403);

        $request->merge([
            'citizen_id' => preg_replace('/\D+/', '', (string) $request->input('citizen_id')),
            'phone' => preg_replace('/\D+/', '', (string) $request->input('phone')),
            'farmer_code' => preg_replace('/\D+/', '', (string) $request->input('farmer_code')),
        ]);

        $registrationId = $userRecord->registration_id ?: '00000000-0000-0000-0000-000000000000';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $userRecord->id . ',id'],
            'phone' => ['nullable', 'digits:10'],
            'role' => $this->roleValidationRules(true),
            'citizen_id' => ['nullable', 'digits:13'],
            'birth_date' => ['nullable', 'date'],
            'address_line' => ['nullable', 'string', 'max:1000'],
            'province' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:255'],
            'farmer_code' => ['nullable', 'digits:12', 'unique:farmer_registrations,reg_number,' . $registrationId . ',id'],
            'registered_at' => ['nullable', 'date'],
            'registered_province' => ['nullable', 'string', 'max:255'],
            'farm_province' => ['nullable', 'string', 'max:255'],
            'farm_area_rai' => ['nullable', 'integer', 'min:0'],
            'farm_area_ngan' => ['nullable', 'integer', 'min:0'],
            'farm_area_square_wa' => ['nullable', 'integer', 'min:0'],
            'crop_type' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'admin_title' => ['nullable', 'string', 'max:255'],
            'scope_province' => ['nullable', 'string', 'max:255'],
            'scope_district' => ['nullable', 'string', 'max:255'],
            'scope_subdistrict' => ['nullable', 'string', 'max:255'],
            'assigned_admin_user_id' => ['nullable', 'string', Rule::in($this->assignableAdminIds())],
            'secondary_admin_user_ids' => ['nullable', 'array'],
            'secondary_admin_user_ids.*' => ['nullable', 'string', Rule::in($this->assignableAdminIds())],
            'assignment_type' => ['nullable', 'string', Rule::in(['AREA', 'INDIVIDUAL'])],
            'assignment_note' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ([
            'province' => $userRecord->province ?? null,
            'district' => $userRecord->district ?? null,
            'subdistrict' => $userRecord->subdistrict ?? null,
            'registered_province' => $userRecord->registered_province ?? null,
            'farm_province' => $userRecord->farm_province ?? null,
            'assigned_admin_user_id' => $userRecord->assigned_admin_user_id ?? null,
            'assignment_type' => $userRecord->assignment_type ?? null,
            'assignment_note' => $userRecord->assignment_note ?? null,
        ] as $field => $fallbackValue) {
            if (! filled($validated[$field] ?? null) && filled($fallbackValue)) {
                $validated[$field] = $fallbackValue;
            }
        }

        if (! array_key_exists('secondary_admin_user_ids', $validated)) {
            $validated['secondary_admin_user_ids'] = $userRecord->secondary_admin_user_ids ?? [];
        }

        if (! AdminAccess::isSuperAdmin(Auth::user()) && strtoupper((string) ($validated['role'] ?? $userRecord->role ?? 'FARMER')) !== 'FARMER') {
            return back()->withErrors([
                'role' => 'ไม่สามารถกำหนดบทบาทอื่นนอกเหนือจากเกษตรกรได้',
            ]);
        }

        DB::transaction(function () use ($userRecord, $validated): void {
            $userPayload = [
                'username' => $validated['username'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
            ];

            if (filled($validated['password'] ?? null)) {
                $userPayload = array_merge($userPayload, $this->passwordPayload($validated['password']));
            }

             $userPayload = array_merge($userPayload, $this->userMirrorPayload($validated));

            DB::table('users')
                ->where('id', $userRecord->id)
                ->update($userPayload);

            $provinceId = $this->resolveProvinceId($validated['province'] ?? null);
            $districtId = $this->resolveDistrictId($validated['district'] ?? null, $provinceId);
            $registeredProvinceId = $this->resolveProvinceId($validated['registered_province'] ?? null);
            $farmProvinceId = $this->resolveProvinceId($validated['farm_province'] ?? null);

            if ($userRecord->profile_id) {
                DB::table('farmer_profiles')
                    ->where('id', $userRecord->profile_id)
                    ->update(array_merge([
                        'full_name' => $validated['name'],
                        'id_card_number' => $validated['citizen_id'] ?? null,
                        'birthdate' => $validated['birth_date'] ?? null,
                        'address' => $validated['address_line'] ?? null,
                        'province_id' => $provinceId,
                        'district_id' => $districtId,
                    ], $this->profilePayload($validated)));
            } else {
                $profileId = (string) Str::uuid();

                DB::table('farmer_profiles')->insert(array_merge([
                    'id' => $profileId,
                    'user_id' => $userRecord->id,
                    'full_name' => $validated['name'],
                    'id_card_number' => $validated['citizen_id'] ?? null,
                    'birthdate' => $validated['birth_date'] ?? null,
                    'province_id' => $provinceId,
                    'district_id' => $districtId,
                    'farmer_type_id' => null,
                    'address' => $validated['address_line'] ?? null,
                    'lat_gps_idx' => null,
                ], $this->profilePayload($validated)));

                $userRecord->profile_id = $profileId;
            }

            if (filled($validated['farmer_code'] ?? null) || filled($validated['registered_at'] ?? null)) {
                if ($userRecord->registration_id) {
                    DB::table('farmer_registrations')
                        ->where('id', $userRecord->registration_id)
                        ->update([
                            'reg_number' => $validated['farmer_code'] ?? null,
                            'reg_date' => $validated['registered_at'] ?? null,
                            'reg_province_id' => $registeredProvinceId,
                        ]);
                } elseif ($userRecord->profile_id) {
                    DB::table('farmer_registrations')->insert([
                        'id' => (string) Str::uuid(),
                        'profile_id' => $userRecord->profile_id,
                        'reg_number' => $validated['farmer_code'] ?? null,
                        'reg_date' => $validated['registered_at'] ?? null,
                        'reg_province_id' => $registeredProvinceId,
                    ]);
                }
            }

            if ($this->shouldSyncPlot($validated)) {
                $plotPayload = array_merge([
                    'farm_id' => $this->resolvePrimaryPlotFarmId(
                        $userRecord->plot_farm_id ?? null,
                        $validated['farmer_code'] ?? $userRecord->farmer_code ?? null
                    ),
                    'area_rai' => $validated['farm_area_rai'] ?? null,
                    'area_sq_wa' => $validated['farm_area_square_wa'] ?? null,
                    'crop_type' => $validated['crop_type'] ?? null,
                    'address' => $validated['address_line'] ?? null,
                    'province_id' => $farmProvinceId,
                ], $this->plotPayload($validated, $districtId));

                if ($userRecord->plot_id) {
                    DB::table('plots')
                        ->where('id', $userRecord->plot_id)
                        ->update($plotPayload);
                } else {
                    DB::table('plots')->insert(array_merge([
                        'id' => (string) Str::uuid(),
                        'user_id' => $userRecord->id,
                        'plot_name' => 'แปลงหลัก',
                        'lat' => null,
                        'lon' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'status' => 'ACTIVE',
                    ], $plotPayload));
                }
            }

            $this->syncAdminProfileAndScope($userRecord->id, $validated['role'], $validated);
            $this->syncFarmerAdminAssignment($userRecord->id, $validated['role'], $validated);
        });
        $this->syncFarmerProfileUpdateAlert($userRecord, $validated);

        return redirect('/admin/farmer-users')->with('success', 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy(string $user): RedirectResponse
    {
        $userRecord = $this->findUserRecord($user);

        abort_if($userRecord === null, 404);
        abort_unless($this->canManageUserRecord($userRecord), 403);

        DB::transaction(function () use ($userRecord): void {
            $plotIds = DB::table('plots')
                ->where('user_id', $userRecord->id)
                ->pluck('id');

            $planIds = collect();
            $activityIds = collect();

            if ($plotIds->isNotEmpty() && Schema::hasTable('planting_plans')) {
                $planIds = DB::table('planting_plans')
                    ->whereIn('plot_id', $plotIds)
                    ->pluck('id');
            }

            if ($planIds->isNotEmpty() && Schema::hasTable('activity_events')) {
                $activityIds = DB::table('activity_events')
                    ->whereIn('plan_id', $planIds)
                    ->pluck('id');
            }

            if ($activityIds->isNotEmpty()) {
                foreach ([
                    'soil_prep_details',
                    'water_control_details',
                    'fertilization_details',
                    'pest_control_details',
                    'disease_control_details',
                    'harvest_details',
                    'sale_details',
                ] as $detailTable) {
                    if (Schema::hasTable($detailTable)) {
                        DB::table($detailTable)
                            ->whereIn('activity_id', $activityIds)
                            ->delete();
                    }
                }
            }

            if (Schema::hasTable('api_access_tokens')) {
                DB::table('api_access_tokens')->where('user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('app_settings')) {
                DB::table('app_settings')->where('user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('support_tickets')) {
                DB::table('support_tickets')->where('user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('dashboard_work_items')) {
                $dashboardWorkItems = DB::table('dashboard_work_items')
                    ->where('user_id', $userRecord->id);

                if ($plotIds->isNotEmpty() && Schema::hasColumn('dashboard_work_items', 'plot_id')) {
                    $dashboardWorkItems->orWhereIn('plot_id', $plotIds);
                }

                if ($activityIds->isNotEmpty() && Schema::hasColumn('dashboard_work_items', 'activity_event_id')) {
                    $dashboardWorkItems->orWhereIn('activity_event_id', $activityIds);
                }

                $dashboardWorkItems->delete();
            }

            if (Schema::hasTable('tracking_advices')) {
                $trackingAdvices = DB::table('tracking_advices');
                $hasTrackingAdviceConstraint = false;

                if (Schema::hasColumn('tracking_advices', 'user_id')) {
                    $trackingAdvices->where('user_id', $userRecord->id);
                    $hasTrackingAdviceConstraint = true;
                }

                if ($plotIds->isNotEmpty() && Schema::hasColumn('tracking_advices', 'plot_id')) {
                    $trackingAdvices->orWhereIn('plot_id', $plotIds);
                    $hasTrackingAdviceConstraint = true;
                }

                if ($activityIds->isNotEmpty() && Schema::hasColumn('tracking_advices', 'activity_event_id')) {
                    $trackingAdvices->orWhereIn('activity_event_id', $activityIds);
                    $hasTrackingAdviceConstraint = true;
                }

                if ($activityIds->isNotEmpty() && Schema::hasColumn('tracking_advices', 'detail_url')) {
                    foreach ($activityIds as $activityId) {
                        $trackingAdvices->orWhere('detail_url', 'like', '%' . $activityId);
                        $hasTrackingAdviceConstraint = true;
                    }
                }

                if ($hasTrackingAdviceConstraint) {
                    $trackingAdvices->delete();
                }
            }

            if ($activityIds->isNotEmpty() && Schema::hasTable('activity_events')) {
                DB::table('activity_events')
                    ->whereIn('id', $activityIds)
                    ->delete();
            }

            if ($planIds->isNotEmpty() && Schema::hasTable('planting_plans')) {
                DB::table('planting_plans')
                    ->whereIn('id', $planIds)
                    ->delete();
            }

            DB::table('plots')->where('user_id', $userRecord->id)->delete();

            if ($userRecord->profile_id) {
                DB::table('farmer_registrations')->where('profile_id', $userRecord->profile_id)->delete();
                DB::table('farmer_profiles')->where('id', $userRecord->profile_id)->delete();
            }

            DB::table('users')->where('id', $userRecord->id)->delete();
        });

        return redirect('/admin/farmer-users')->with('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');

        $plotIds = DB::table('plots')
            ->where('user_id', $userRecord->id)
            ->pluck('id');

        if ($plotIds->isNotEmpty()) {
            $hasPlans = DB::table('planting_plans')
                ->whereIn('plot_id', $plotIds)
                ->exists();

            if ($hasPlans) {
                return redirect('/admin/farmer-users')->with('error', 'ไม่สามารถลบผู้ใช้งานที่มีข้อมูลแปลงและประวัติติดตามแล้วได้');
            }
        }

        DB::transaction(function () use ($userRecord): void {
            if (Schema::hasTable('api_access_tokens')) {
                DB::table('api_access_tokens')->where('user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('app_settings')) {
                DB::table('app_settings')->where('user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('support_tickets')) {
                DB::table('support_tickets')->where('user_id', $userRecord->id)->delete();
            }

            DB::table('plots')->where('user_id', $userRecord->id)->delete();

            if ($userRecord->profile_id) {
                DB::table('farmer_registrations')->where('profile_id', $userRecord->profile_id)->delete();
                DB::table('farmer_profiles')->where('id', $userRecord->profile_id)->delete();
            }

            DB::table('users')->where('id', $userRecord->id)->delete();
        });

        return redirect('/admin/farmer-users')->with('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function createPlot(string $user): View
    {
        $userRecord = $this->findUserRecord($user);

        abort_if($userRecord === null, 404);
        abort_unless($this->canManageUserRecord($userRecord), 403);

        $riceVarieties = DB::table('rice_varieties')
            ->where('is_active', 1)
            ->orderBy('name')
            ->select(['id', 'name', 'grow_duration_days', 'recommended_season'])
            ->get();

        return view('admin.farmer-plot-create', [
            'userRecord' => $userRecord,
            'riceVarieties' => $riceVarieties,
        ]);
    }

    public function storePlot(Request $request, string $user): RedirectResponse
    {
        $userRecord = $this->findUserRecord($user);

        abort_if($userRecord === null, 404);
        abort_unless($this->canManageUserRecord($userRecord), 403);

        $validated = $request->validate([
            'plot_name'     => ['required', 'string', 'max:255'],
            'area_rai'      => ['nullable', 'integer', 'min:0'],
            'area_ngan'     => ['nullable', 'integer', 'min:0'],
            'area_sq_wa'    => ['nullable', 'integer', 'min:0'],
            'rice_id'       => ['nullable', 'string'],
            'planting_type' => ['nullable', 'string', 'max:100'],
            'season_type'   => ['nullable', 'string', 'max:100'],
            'start_date'    => ['nullable', 'date'],
            'province'      => ['nullable', 'string', 'max:255'],
            'district'      => ['nullable', 'string', 'max:255'],
            'subdistrict'   => ['nullable', 'string', 'max:255'],
            'postcode'      => ['nullable', 'string', 'max:10'],
            'latitude'      => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'     => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $provinceId = $this->resolveProvinceId($validated['province'] ?? null);
        $districtId = $this->resolveDistrictId($validated['district'] ?? null, $provinceId);

        $rai     = (int) ($validated['area_rai'] ?? 0);
        $ngan    = (int) ($validated['area_ngan'] ?? 0);
        $sqWa    = (int) ($validated['area_sq_wa'] ?? 0);
        $sqMeter = ($rai * 1600) + ($ngan * 400) + ($sqWa * 4);

        DB::transaction(function () use ($validated, $userRecord, $provinceId, $districtId, $rai, $ngan, $sqWa, $sqMeter): void {
            $plotId = (string) Str::uuid();

            $plotData = [
                'id'          => $plotId,
                'user_id'     => $userRecord->id,
                'farm_id'     => $this->generateUniqueFarmId(),
                'plot_name'   => $validated['plot_name'],
                'area_rai'    => $rai ?: null,
                'area_sq_wa'  => $sqWa ?: null,
                'crop_type'   => null,
                'address'     => null,
                'province_id' => $provinceId,
                'lat'         => $validated['latitude'] ?? null,
                'lon'         => $validated['longitude'] ?? null,
                'latitude'    => $validated['latitude'] ?? null,
                'longitude'   => $validated['longitude'] ?? null,
                'status'      => 'ACTIVE',
            ];

            if ($this->hasColumn('plots', 'district_id')) {
                $plotData['district_id'] = $districtId;
            }
            if ($this->hasColumn('plots', 'area_ngan')) {
                $plotData['area_ngan'] = $ngan ?: null;
            }
            if ($this->hasColumn('plots', 'area_sq_meter')) {
                $plotData['area_sq_meter'] = $sqMeter ?: null;
            }
            if ($this->hasColumn('plots', 'subdistrict')) {
                $plotData['subdistrict'] = $validated['subdistrict'] ?? null;
            }
            if ($this->hasColumn('plots', 'postcode')) {
                $plotData['postcode'] = $validated['postcode'] ?? null;
            }

            DB::table('plots')->insert($plotData);

            $plantingType = $validated['planting_type'] ?? 'ข้าว';

            if (empty($validated['rice_id']) && filled($plantingType)) {
                DB::table('plots')->where('id', $plotId)->update(['crop_type' => $plantingType]);
            }

            if (filled($validated['start_date'] ?? null)) {
                $startDate   = \Carbon\Carbon::parse($validated['start_date']);
                $rice        = filled($validated['rice_id'] ?? null)
                    ? DB::table('rice_varieties')->where('id', $validated['rice_id'])->first()
                    : null;
                $harvestDate = ($rice && filled($rice->grow_duration_days))
                    ? $startDate->copy()->addDays((int) $rice->grow_duration_days)
                    : null;

                if ($rice) {
                    DB::table('plots')->where('id', $plotId)->update(['crop_type' => $rice->name]);
                }

                if (Schema::hasTable('planting_plans')) {
                    DB::table('planting_plans')->insert([
                        'id'                    => (string) Str::uuid(),
                        'plot_id'               => $plotId,
                        'rice_id'               => $validated['rice_id'] ?? null,
                        'season_type'           => $validated['season_type'] ?? 'นาปี',
                        'planting_type'         => $plantingType,
                        'start_date'            => $startDate->toDateString(),
                        'expected_harvest_date' => $harvestDate?->toDateString(),
                        'status'                => 'ACTIVE',
                    ]);
                }
            }
        });

        return redirect('/admin/farmer-users/' . $userRecord->id)
            ->with('success', 'เพิ่มแปลง "' . $validated['plot_name'] . '" เรียบร้อยแล้ว');
    }

    public function adminIndex(Request $request): View
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        $query = trim((string) $request->query('q', ''));

        $users = DB::table('users')
            ->leftJoin('admin_profiles', 'admin_profiles.user_id', '=', 'users.id')
            ->leftJoin('admin_area_scopes', function ($join): void {
                $join->on('admin_area_scopes.admin_user_id', '=', 'users.id');

                if ($this->hasColumn('admin_area_scopes', 'is_active')) {
                    $join->where('admin_area_scopes.is_active', '=', true);
                }
            })
            ->whereIn('users.role', ['ADMIN', 'SUPERADMIN'])
            ->select([
                'users.id',
                'users.username',
                $this->optionalUserSelect('email', 'email'),
                'users.phone',
                'users.role',
                $this->optionalUserSelect('member_registered_at', 'member_registered_at'),
                $this->optionalTableSelect('admin_profiles', 'display_name', 'display_name'),
                $this->optionalTableSelect('admin_profiles', 'title', 'admin_title'),
                $this->optionalTableSelect('admin_area_scopes', 'province_name', 'scope_province'),
                $this->optionalTableSelect('admin_area_scopes', 'district_name', 'scope_district'),
                $this->optionalTableSelect('admin_area_scopes', 'subdistrict_name', 'scope_subdistrict'),
                $this->optionalTableSelect('admin_area_scopes', 'scope_label', 'scope_label'),
            ])
            ->orderByRaw("CASE WHEN users.role = 'SUPERADMIN' THEN 0 ELSE 1 END")
            ->orderBy('users.username')
            ->get()
            ->values();

        $users = SearchTextMatcher::filterByPriority($users, [
            fn ($user) => $user->username,
            fn ($user) => $user->display_name,
            fn ($user) => $user->phone,
            fn ($user) => $user->admin_title,
            fn ($user) => $user->scope_label,
            fn ($user) => $user->scope_province,
            fn ($user) => $user->scope_district,
            fn ($user) => $user->scope_subdistrict,
            fn ($user) => $user->role,
        ], $query);

        return view('admin.admin-users', [
            'users' => $users,
            'query' => $query,
        ]);
    }

    public function createAdmin(): View
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        return view('admin.admin-users-create', [
            'roleOptions' => $this->adminRoleOptions(),
            'provinceOptions' => $this->adminScopeProvinceOptions(),
            'provinceDistrictMap' => $this->adminScopeProvinceDistrictMap(),
        ]);
    }

    public function storeAdmin(Request $request): RedirectResponse
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        $hasEmailColumn = Schema::hasColumn('users', 'email');

        $rules = [
            'display_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_column($this->adminRoleOptions(), 'code'))],
            'admin_title' => ['nullable', 'string', 'max:255'],
            'scope_province' => ['nullable', 'string', 'max:255'],
            'scope_district' => ['nullable', 'string', 'max:255'],
            'scope_subdistrict' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($hasEmailColumn) {
            $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email'];
        } else {
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($validated, $hasEmailColumn): void {
            $userId = (string) Str::uuid();

            $userData = [
                'id' => $userId,
                'username' => $validated['username'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
            ];

            if ($hasEmailColumn && filled($validated['email'] ?? null)) {
                $userData['email'] = $validated['email'];
            }

            User::query()->create(array_merge(
                $userData,
                $this->memberRegisteredAtPayload(),
                $this->passwordPayload($validated['password'])
            ));

            $this->syncAdminProfileAndScope($userId, $validated['role'], $validated);
        });

        return redirect('/admin/admin-users')->with('success', 'บันทึกผู้ดูแลระบบเรียบร้อยแล้ว');
    }

    public function showAdmin(string $user): View
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        $userRecord = $this->findAdminUserRecord($user);
        abort_if($userRecord === null, 404);

        return view('admin.admin-users-show', [
            'userRecord' => $userRecord,
        ]);
    }

    public function editAdmin(string $user): View
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        $userRecord = $this->findAdminUserRecord($user);
        abort_if($userRecord === null, 404);

        return view('admin.admin-users-edit', [
            'userRecord' => $userRecord,
            'roleOptions' => $this->adminRoleOptions(),
            'provinceOptions' => $this->adminScopeProvinceOptions(),
            'provinceDistrictMap' => $this->adminScopeProvinceDistrictMap(),
        ]);
    }

    public function updateAdmin(Request $request, string $user): RedirectResponse
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        $userRecord = $this->findAdminUserRecord($user);
        abort_if($userRecord === null, 404);

        $hasEmailColumn = Schema::hasColumn('users', 'email');

        $rules = [
            'display_name' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $userRecord->id . ',id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_column($this->adminRoleOptions(), 'code'))],
            'admin_title' => ['nullable', 'string', 'max:255'],
            'scope_province' => ['nullable', 'string', 'max:255'],
            'scope_district' => ['nullable', 'string', 'max:255'],
            'scope_subdistrict' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($hasEmailColumn) {
            $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email,' . $userRecord->id . ',id'];
        } else {
            $rules['email'] = ['nullable', 'email', 'max:255'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($userRecord, $validated, $hasEmailColumn): void {
            $payload = [
                'username' => $validated['username'],
                'phone' => $validated['phone'] ?? null,
                'role' => $validated['role'],
            ];

            if ($hasEmailColumn && filled($validated['email'] ?? null)) {
                $payload['email'] = $validated['email'];
            }

            if (filled($validated['password'] ?? null)) {
                $payload = array_merge($payload, $this->passwordPayload($validated['password']));
            }

            DB::table('users')->where('id', $userRecord->id)->update($payload);

            $this->syncAdminProfileAndScope($userRecord->id, $validated['role'], $validated);
        });

        return redirect('/admin/admin-users')->with('success', 'อัปเดตผู้ดูแลระบบเรียบร้อยแล้ว');
    }

    public function destroyAdmin(string $user): RedirectResponse
    {
        abort_unless(AdminAccess::isSuperAdmin(Auth::user()), 403);

        $userRecord = $this->findAdminUserRecord($user);
        abort_if($userRecord === null, 404);

        if ($userRecord->role === 'SUPERADMIN' && strcasecmp((string) $userRecord->username, 'superadmin') === 0) {
            return redirect('/admin/admin-users')->with('error', 'ไม่สามารถลบบัญชี superadmin หลักได้');
        }

        DB::transaction(function () use ($userRecord): void {
            if (Schema::hasTable('admin_area_scopes')) {
                DB::table('admin_area_scopes')->where('admin_user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('admin_profiles')) {
                DB::table('admin_profiles')->where('user_id', $userRecord->id)->delete();
            }

            if (Schema::hasTable('api_access_tokens')) {
                DB::table('api_access_tokens')->where('user_id', $userRecord->id)->delete();
            }

            DB::table('users')->where('id', $userRecord->id)->delete();
        });

        return redirect('/admin/admin-users')->with('success', 'ลบผู้ดูแลระบบเรียบร้อยแล้ว');
    }

    private function resolveProvinceId(?string $provinceName): ?int
    {
        if (!filled($provinceName)) {
            return null;
        }

        if (!$this->hasTable('provinces')) {
            return null;
        }

        $provinceId = DB::table('provinces')
            ->whereRaw('LOWER(name_th) = ?', [mb_strtolower(trim($provinceName))])
            ->value('id');

        return $provinceId ? (int) $provinceId : null;
    }

    private function resolveDistrictId(?string $districtName, ?int $provinceId = null): ?int
    {
        if (!filled($districtName)) {
            return null;
        }

        if (!$this->hasTable('districts')) {
            return null;
        }

        $query = DB::table('districts')
            ->whereRaw('LOWER(name_th) = ?', [mb_strtolower(trim($districtName))]);

        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }

        $districtId = $query->value('id');

        return $districtId ? (int) $districtId : null;
    }

    private function adminScopeProvinceOptions(): array
    {
        if (! $this->hasTable('provinces')) {
            return [];
        }

        return DB::table('provinces')
            ->orderBy('name_th')
            ->pluck('name_th')
            ->filter()
            ->values()
            ->all();
    }

    private function adminScopeProvinceDistrictMap(): array
    {
        if (! $this->hasTable('provinces') || ! $this->hasTable('districts')) {
            return [];
        }

        $rows = DB::table('districts')
            ->join('provinces', 'provinces.id', '=', 'districts.province_id')
            ->select('provinces.name_th as province_name', 'districts.name_th as district_name')
            ->orderBy('provinces.name_th')
            ->orderBy('districts.name_th')
            ->get();

        $map = [];

        foreach ($rows as $row) {
            if (! filled($row->province_name) || ! filled($row->district_name)) {
                continue;
            }

            $map[$row->province_name] ??= [];
            $map[$row->province_name][] = $row->district_name;
        }

        return $map;
    }

    private function findUserRecord(string $userId): ?object
    {
        $joinProvinces = $this->hasTable('provinces');
        $joinDistricts = $this->hasTable('districts');

        $selects = [
            'users.id',
            'users.username',
            'users.phone',
            'users.role',
            $this->optionalUserSelect('citizen_id', 'user_citizen_id'),
            $this->optionalUserSelect('birth_date', 'user_birth_date'),
            $this->optionalUserSelect('address_line', 'user_address_line'),
            $this->optionalUserSelect('province', 'user_province'),
            $this->optionalUserSelect('district', 'user_district'),
            $this->optionalUserSelect('subdistrict', 'user_subdistrict'),
            $this->optionalUserSelect('postcode', 'user_postcode'),
            $this->optionalUserSelect('member_registered_at', 'user_member_registered_at'),
            $this->optionalUserSelect('farmer_code', 'user_farmer_code'),
            $this->optionalUserSelect('registered_at', 'user_registered_at'),
            $this->optionalUserSelect('registered_province', 'user_registered_province'),
            $this->optionalUserSelect('farm_province', 'user_farm_province'),
            $this->optionalUserSelect('farm_area_rai', 'user_farm_area_rai'),
            $this->optionalUserSelect('farm_area_ngan', 'user_farm_area_ngan'),
            $this->optionalUserSelect('farm_area_square_wa', 'user_farm_area_square_wa'),
            $this->optionalUserSelect('crop_type', 'user_crop_type'),
            'profiles.id as profile_id',
            'profiles.full_name as full_name',
            'profiles.id_card_number',
            'profiles.birthdate',
            'profiles.address',
            'profiles.province_id as profile_province_id',
            'profiles.district_id as profile_district_id',
            $this->optionalTableSelect('profiles', 'subdistrict', 'profile_subdistrict'),
            $this->optionalTableSelect('profiles', 'postcode', 'profile_postcode'),
            'registrations.id as registration_id',
            'registrations.reg_number as farmer_code',
            'registrations.reg_date as registered_at',
            'registrations.reg_province_id as registration_province_id',
            $joinProvinces ? 'provinces.name_th as province_name' : DB::raw('NULL as province_name'),
            $joinDistricts ? 'districts.name_th as district_name' : DB::raw('NULL as district_name'),
            $joinProvinces ? 'register_provinces.name_th as registered_province_name' : DB::raw('NULL as registered_province_name'),
            'plots.id as plot_id',
            'plots.farm_id as plot_farm_id',
            'plots.area_rai as farm_area_rai',
            $this->optionalTableSelect('plots', 'area_ngan', 'farm_area_ngan'),
            'plots.area_sq_wa as farm_area_square_wa',
            'plots.crop_type as crop_type',
            'plots.province_id as plot_province_id',
            $this->optionalTableSelect('plots', 'district_id', 'plot_district_id'),
            $this->optionalTableSelect('plots', 'subdistrict', 'plot_subdistrict'),
            $this->optionalTableSelect('plots', 'postcode', 'plot_postcode'),
            $joinProvinces ? 'farm_provinces.name_th as farm_province_name' : DB::raw('NULL as farm_province_name'),
        ];

        $recordQuery = DB::table('users')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('farmer_registrations as registrations', 'registrations.profile_id', '=', 'profiles.id')
            ->leftJoin('plots as plots', 'plots.user_id', '=', 'users.id');

        if ($joinProvinces) {
            $recordQuery
                ->leftJoin('provinces as provinces', 'provinces.id', '=', 'profiles.province_id')
                ->leftJoin('provinces as register_provinces', 'register_provinces.id', '=', 'registrations.reg_province_id')
                ->leftJoin('provinces as farm_provinces', 'farm_provinces.id', '=', 'plots.province_id');
        }

        if ($joinDistricts) {
            $recordQuery->leftJoin('districts as districts', 'districts.id', '=', 'profiles.district_id');
        }

        $record = $recordQuery
            ->select($selects)
            ->where('users.id', $userId)
            ->first();

        if (!$record) {
            return null;
        }

        $record->citizen_id = $record->id_card_number ?: $record->user_citizen_id;
        $record->birth_date = $record->birthdate ?: $record->user_birth_date;
        $record->address_line = $record->address ?: $record->user_address_line;
        $record->subdistrict = $record->profile_subdistrict ?: $record->user_subdistrict ?: $record->plot_subdistrict;
        $record->postcode = $record->profile_postcode ?: $record->user_postcode ?: $record->plot_postcode;
        $record->district = $record->district_name
            ?: $record->user_district
            ?: $this->fallbackDistrictName($record->profile_district_id ?? null)
            ?: $this->fallbackDistrictName($record->plot_district_id ?? null)
            ?: $this->fallbackDistrictFromSubdistrict($record->subdistrict ?? null)
            ?: $this->fallbackDistrictFromPostcode($record->postcode ?? null);
        $record->province = $record->province_name
            ?: $record->user_province
            ?: $this->fallbackProvinceName($record->profile_province_id ?? null)
            ?: $this->fallbackProvinceName($record->plot_province_id ?? null)
            ?: $this->fallbackProvinceNameFromDistrict($record->district)
            ?: $this->fallbackProvinceNameFromPostcode($record->postcode ?? null);
        $record->member_registered_at = $record->user_member_registered_at;
        $record->farmer_code = $record->farmer_code ?: $record->user_farmer_code;
        $record->registered_at = $record->registered_at ?: $record->user_registered_at;
        $record->registered_province = $record->registered_province_name
            ?: $record->user_registered_province
            ?: $this->fallbackProvinceName($record->registration_province_id ?? null)
            ?: $record->province;
        $record->farm_province = $record->farm_province_name
            ?: $record->user_farm_province
            ?: $this->fallbackProvinceName($record->plot_province_id ?? null)
            ?: $record->province;
        $record->farm_area_rai = $record->farm_area_rai ?: $record->user_farm_area_rai;
        $record->farm_area_ngan = $record->farm_area_ngan ?: $record->user_farm_area_ngan;
        $record->farm_area_square_wa = $record->farm_area_square_wa ?: $record->user_farm_area_square_wa;
        $record->crop_type = $record->crop_type ?: $record->user_crop_type;
        $record->admin_title = null;
        $record->scope_province = null;
        $record->scope_district = null;
        $record->scope_subdistrict = null;
        $record->assigned_admin_user_id = null;
        $record->assigned_admin_username = null;
        $record->assigned_admin_display_name = null;
        $record->secondary_admin_user_ids = [];
        $record->assignment_type = null;
        $record->assignment_note = null;

        if ($this->hasTable('admin_profiles')) {
            $adminProfile = DB::table('admin_profiles')
                ->select('title')
                ->where('user_id', $record->id)
                ->first();

            $record->admin_title = $adminProfile->title ?? null;
        }

        if ($this->hasTable('admin_area_scopes')) {
            $adminScope = DB::table('admin_area_scopes')
                ->select('province_name', 'district_name', 'subdistrict_name')
                ->where('admin_user_id', $record->id)
                ->when($this->hasColumn('admin_area_scopes', 'is_active'), fn ($query) => $query->where('is_active', true))
                ->orderBy('id')
                ->first();

            $record->scope_province = $adminScope->province_name ?? null;
            $record->scope_district = $adminScope->district_name ?? null;
            $record->scope_subdistrict = $adminScope->subdistrict_name ?? null;
        }

        if ($this->hasTable('admin_farmer_assignments')) {
            $assignmentsQuery = DB::table('admin_farmer_assignments as assignments')
                ->leftJoin('users as admins', 'admins.id', '=', 'assignments.admin_user_id')
                ->leftJoin('admin_profiles as admin_profiles', 'admin_profiles.user_id', '=', 'admins.id')
                ->select([
                    'assignments.admin_user_id',
                    $this->hasColumn('admin_farmer_assignments', 'note')
                        ? 'assignments.note as note'
                        : DB::raw('NULL as note'),
                    'admins.username as admin_username',
                    $this->optionalTableSelect('admin_profiles', 'display_name', 'admin_display_name'),
                ])
                ->where('assignments.farmer_user_id', $record->id)
                ->orderBy($this->hasColumn('admin_farmer_assignments', 'created_at') ? 'assignments.created_at' : 'assignments.id');

            if ($this->hasColumn('admin_farmer_assignments', 'is_primary')) {
                $assignmentsQuery
                    ->addSelect('assignments.is_primary')
                    ->orderByDesc('assignments.is_primary');
            }

            $assignments = $assignmentsQuery->get();
            $primaryAssignment = $assignments->firstWhere('is_primary', true) ?? $assignments->first();

            $record->assigned_admin_user_id = $primaryAssignment->admin_user_id ?? null;
            $record->assigned_admin_username = $primaryAssignment->admin_username ?? null;
            $record->assigned_admin_display_name = $primaryAssignment->admin_display_name ?? null;
            $record->secondary_admin_user_ids = $assignments
                ->filter(fn ($assignment) => filled($assignment->admin_user_id) && $assignment->admin_user_id !== $record->assigned_admin_user_id)
                ->pluck('admin_user_id')
                ->values()
                ->all();

            $assignmentMeta = $this->parseAssignmentMetadataDisplay($primaryAssignment->note ?? null);
            $record->assignment_type = $assignmentMeta['assignment_type'];
            $record->assignment_note = $assignmentMeta['assignment_note'];
        }

        return $record;
    }

    private function parseAssignmentMetadata(?string $note): array
    {
        $assignmentType = null;
        $assignmentNoteLines = [];

        foreach (preg_split("/\r\n|\n|\r/", trim((string) $note)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (Str::startsWith($line, 'ประเภทการดูแล:')) {
                $typeValue = trim(Str::after($line, ':'));

                if ($typeValue === 'ดูแลตามพื้นที่') {
                    $assignmentType = 'AREA';
                } elseif ($typeValue === 'ดูแลเฉพาะราย') {
                    $assignmentType = 'INDIVIDUAL';
                }

                continue;
            }

            if (Str::startsWith($line, 'หมายเหตุ:')) {
                $line = trim(Str::after($line, ':'));
            }

            if ($this->containsMojibakeMarkers($line) && ! preg_match('/[ก-๙]/u', $line)) {
                continue;
            }

            if ($line !== '') {
                $assignmentNoteLines[] = $line;
            }
        }

        return [
            'assignment_type' => $assignmentType,
            'assignment_note' => empty($assignmentNoteLines) ? null : implode("\n", $assignmentNoteLines),
        ];
    }

    private function parseAssignmentMetadataClean(?string $note): array
    {
        $assignmentType = null;
        $assignmentNoteLines = [];

        foreach (preg_split("/\r\n|\n|\r/", trim((string) $note)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (Str::startsWith($line, 'ประเภทการดูแล:')) {
                $typeValue = trim(Str::after($line, ':'));

                if ($typeValue === 'ดูแลตามพื้นที่') {
                    $assignmentType = 'AREA';
                } elseif ($typeValue === 'ดูแลเฉพาะราย') {
                    $assignmentType = 'INDIVIDUAL';
                }

                continue;
            }

            if (Str::startsWith($line, 'หมายเหตุ:')) {
                $line = trim(Str::after($line, ':'));
            }

            if ($this->containsMojibakeMarkers($line) && ! preg_match('/[ก-๙]/u', $line)) {
                continue;
            }

            if ($line !== '') {
                $assignmentNoteLines[] = $line;
            }
        }

        return [
            'assignment_type' => $assignmentType,
            'assignment_note' => empty($assignmentNoteLines) ? null : implode("\n", $assignmentNoteLines),
        ];
    }

    private function parseAssignmentMetadataDisplay(?string $note): array
    {
        $assignmentType = null;
        $assignmentNoteLines = [];

        foreach (preg_split("/\r\n|\n|\r/", trim((string) $note)) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (Str::startsWith($line, 'ประเภทการดูแล:')) {
                $typeValue = trim(Str::after($line, ':'));

                if ($typeValue === 'ดูแลตามพื้นที่') {
                    $assignmentType = 'AREA';
                } elseif ($typeValue === 'ดูแลเฉพาะราย') {
                    $assignmentType = 'INDIVIDUAL';
                }

                continue;
            }

            if (Str::startsWith($line, 'หมายเหตุ:')) {
                $line = trim(Str::after($line, ':'));
            }

            if ($this->containsMojibakeMarkers($line) && ! preg_match('/[\x{0E00}-\x{0E7F}]/u', $line)) {
                continue;
            }

            if ($line !== '') {
                $assignmentNoteLines[] = $line;
            }
        }

        return [
            'assignment_type' => $assignmentType,
            'assignment_note' => empty($assignmentNoteLines) ? null : implode("\n", $assignmentNoteLines),
        ];
    }

    private function containsMojibakeMarkers(string $line): bool
    {
        if (Str::contains($line, ["\u{00C3}", "\u{00C2}"])) {
            return true;
        }

        return preg_match('/\x{00E0}\x{00B8}|\x{00E0}\x{00B9}/u', $line) === 1;
    }

    private function userMirrorPayload(array $attributes): array
    {
        $payload = [];
        $mirrorable = [
            'name',
            'username',
            'role',
            'citizen_id',
            'phone',
            'birth_date',
            'address_line',
            'province',
            'district',
            'subdistrict',
            'farmer_code',
            'registered_at',
            'registered_province',
            'farm_province',
            'farm_area_rai',
            'farm_area_ngan',
            'farm_area_square_wa',
            'crop_type',
        ];

        foreach ($mirrorable as $column) {
            if ($this->hasUserColumn($column) && array_key_exists($column, $attributes)) {
                $payload[$column] = $attributes[$column];
            }
        }

        if ($this->hasUserColumn('postcode')) {
            $payload['postcode'] = null;
        }

        return $payload;
    }

    private function profilePayload(array $attributes): array
    {
        $payload = [];

        if ($this->hasColumn('farmer_profiles', 'subdistrict') && array_key_exists('subdistrict', $attributes)) {
            $payload['subdistrict'] = $attributes['subdistrict'];
        }

        if ($this->hasColumn('farmer_profiles', 'postcode')) {
            $payload['postcode'] = null;
        }

        return $payload;
    }

    private function plotPayload(array $attributes, ?int $districtId): array
    {
        $payload = [];

        if ($this->hasColumn('plots', 'district_id')) {
            $payload['district_id'] = $districtId;
        }

        if ($this->hasColumn('plots', 'subdistrict') && array_key_exists('subdistrict', $attributes)) {
            $payload['subdistrict'] = $attributes['subdistrict'];
        }

        if ($this->hasColumn('plots', 'area_ngan') && array_key_exists('farm_area_ngan', $attributes)) {
            $payload['area_ngan'] = $attributes['farm_area_ngan'];
        }

        if ($this->hasColumn('plots', 'postcode')) {
            $payload['postcode'] = null;
        }

        return $payload;
    }

    private function resolvePrimaryPlotFarmId(?string $currentFarmId, ?string $farmerCode): string
    {
        $currentFarmId = trim((string) $currentFarmId);
        $farmerCode = trim((string) $farmerCode);

        if ($currentFarmId !== '' && $currentFarmId !== $farmerCode) {
            return $currentFarmId;
        }

        return $this->generateUniqueFarmId();
    }

    private function generateUniqueFarmId(): string
    {
        do {
            $farmId = 'FARM-' . strtoupper(Str::random(6));
        } while (DB::table('plots')->where('farm_id', $farmId)->exists());

        return $farmId;
    }

    private function shouldSyncPlot(array $attributes): bool
    {
        return filled($attributes['farmer_code'] ?? null)
            || filled($attributes['farm_province'] ?? null)
            || filled($attributes['farm_area_rai'] ?? null)
            || filled($attributes['farm_area_ngan'] ?? null)
            || filled($attributes['farm_area_square_wa'] ?? null)
            || filled($attributes['crop_type'] ?? null);
    }

    private function syncAdminProfileAndScope(string $userId, string $roleCode, array $attributes): void
    {
        $roleCode = strtoupper(trim($roleCode));

        if (! in_array($roleCode, ['ADMIN', 'SUPERADMIN'], true)) {
            if ($this->hasTable('admin_area_scopes')) {
                DB::table('admin_area_scopes')->where('admin_user_id', $userId)->delete();
            }

            if ($this->hasTable('admin_profiles')) {
                DB::table('admin_profiles')->where('user_id', $userId)->delete();
            }

            return;
        }

        if ($this->hasTable('admin_profiles')) {
            $profilePayload = [
                'display_name' => $attributes['display_name'] ?? $attributes['name'] ?? $attributes['username'] ?? null,
                'title' => $attributes['admin_title'] ?? null,
                'is_active' => true,
                'notes' => null,
                'updated_at' => now(),
            ];

            $exists = DB::table('admin_profiles')->where('user_id', $userId)->exists();

            if ($exists) {
                DB::table('admin_profiles')
                    ->where('user_id', $userId)
                    ->update($profilePayload);
            } else {
                DB::table('admin_profiles')->insert($profilePayload + [
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'created_at' => now(),
                ]);
            }
        }

        if (! $this->hasTable('admin_area_scopes')) {
            return;
        }

        DB::table('admin_area_scopes')->where('admin_user_id', $userId)->delete();

        if (
            ! filled($attributes['scope_province'] ?? null)
            && ! filled($attributes['scope_district'] ?? null)
            && ! filled($attributes['scope_subdistrict'] ?? null)
        ) {
            return;
        }

        DB::table('admin_area_scopes')->insert([
            'admin_user_id' => $userId,
            'province_name' => $attributes['scope_province'] ?? null,
            'district_name' => $attributes['scope_district'] ?? null,
            'subdistrict_name' => $attributes['scope_subdistrict'] ?? null,
            'scope_label' => collect([
                $attributes['scope_province'] ?? null,
                $attributes['scope_district'] ?? null,
                $attributes['scope_subdistrict'] ?? null,
            ])->filter()->implode(' / '),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function syncFarmerAdminAssignment(string $farmerUserId, string $roleCode, array $attributes): void
    {
        if (! $this->hasTable('admin_farmer_assignments')) {
            return;
        }

        DB::table('admin_farmer_assignments')->where('farmer_user_id', $farmerUserId)->delete();

        if (strtoupper(trim($roleCode)) !== 'FARMER') {
            return;
        }

        $assignedAdminUserId = $attributes['assigned_admin_user_id'] ?? null;
        $secondaryAdminUserIds = collect($attributes['secondary_admin_user_ids'] ?? [])
            ->filter(fn ($adminUserId) => filled($adminUserId))
            ->map(fn ($adminUserId) => (string) $adminUserId)
            ->reject(fn ($adminUserId) => $adminUserId === $assignedAdminUserId)
            ->unique()
            ->values();

        $assignmentType = match ((string) ($attributes['assignment_type'] ?? '')) {
            'AREA' => 'ดูแลตามพื้นที่',
            'INDIVIDUAL' => 'ดูแลเฉพาะราย',
            default => null,
        };

        $assignmentNote = trim(implode("\n", array_filter([
            $assignmentType ? 'ประเภทการดูแล: ' . $assignmentType : null,
            filled($attributes['assignment_note'] ?? null) ? 'หมายเหตุ: ' . trim((string) $attributes['assignment_note']) : null,
        ])));

        $assignmentType = match ((string) ($attributes['assignment_type'] ?? '')) {
            'AREA' => 'ดูแลตามพื้นที่',
            'INDIVIDUAL' => 'ดูแลเฉพาะราย',
            default => null,
        };

        $assignmentNote = trim(implode("\n", array_filter([
            $assignmentType ? 'ประเภทการดูแล: ' . $assignmentType : null,
            filled($attributes['assignment_note'] ?? null) ? 'หมายเหตุ: ' . trim((string) $attributes['assignment_note']) : null,
        ])));

        if (! filled($assignedAdminUserId) && $secondaryAdminUserIds->isEmpty()) {
            return;
        }

        $assignmentRows = [];
        $basePayload = [
            'id' => (string) Str::uuid(),
            'farmer_user_id' => $farmerUserId,
        ];

        if ($this->hasColumn('admin_farmer_assignments', 'note')) {
            $basePayload['note'] = $assignmentNote ?: null;
        }

        if ($this->hasColumn('admin_farmer_assignments', 'created_at')) {
            $basePayload['created_at'] = now();
        }

        if ($this->hasColumn('admin_farmer_assignments', 'updated_at')) {
            $basePayload['updated_at'] = now();
        }

        if ($this->hasColumn('admin_farmer_assignments', 'assigned_by')) {
            $basePayload['assigned_by'] = Auth::id();
        }

        if ($this->hasColumn('admin_farmer_assignments', 'assigned_at')) {
            $basePayload['assigned_at'] = now();
        }

        if (filled($assignedAdminUserId)) {
            $primaryPayload = $basePayload + [
                'id' => (string) Str::uuid(),
                'admin_user_id' => $assignedAdminUserId,
            ];

            if ($this->hasColumn('admin_farmer_assignments', 'is_primary')) {
                $primaryPayload['is_primary'] = true;
            }

            $assignmentRows[] = $primaryPayload;
        }

        foreach ($secondaryAdminUserIds as $secondaryAdminUserId) {
            $secondaryPayload = $basePayload + [
                'id' => (string) Str::uuid(),
                'admin_user_id' => $secondaryAdminUserId,
            ];

            if ($this->hasColumn('admin_farmer_assignments', 'is_primary')) {
                $secondaryPayload['is_primary'] = false;
            }

            $assignmentRows[] = $secondaryPayload;
        }

        if (! empty($assignmentRows)) {
            DB::table('admin_farmer_assignments')->insert($assignmentRows);
        }
    }

    private function syncFarmerProfileUpdateAlert(object $userRecord, array $attributes): void
    {
        if (! Schema::hasTable('dashboard_work_items')) {
            return;
        }

        $roleCode = strtoupper((string) ($attributes['role'] ?? $userRecord->role ?? 'FARMER'));

        if ($roleCode !== 'FARMER') {
            return;
        }

        $farmerName = trim((string) ($attributes['name'] ?? $userRecord->name ?? $userRecord->full_name ?? $userRecord->username ?? ''));
        $farmerCode = trim((string) ($attributes['farmer_code'] ?? $userRecord->farmer_code ?? ''));

        $updatedFields = collect([
            filled($attributes['address_line'] ?? null) ? 'ที่อยู่' : null,
            filled($attributes['phone'] ?? null) ? 'เบอร์โทรศัพท์' : null,
            filled($attributes['farmer_code'] ?? null) ? 'ทะเบียนเกษตรกร' : null,
            filled($attributes['assigned_admin_user_id'] ?? null) ? 'ข้อมูลผู้ดูแล' : null,
        ])->filter()->unique()->values()->all();

        $latestNote = 'มีการอัปเดตข้อมูลเกษตรกร กรุณาตรวจสอบ';

        if ($updatedFields !== []) {
            $latestNote .= ' (' . implode(', ', $updatedFields) . ')';
        }

        $match = [
            'task_title' => 'แจ้งเตือนอัปเดตข้อมูลเกษตรกร',
        ];

        if (Schema::hasColumn('dashboard_work_items', 'user_id')) {
            $match['user_id'] = $userRecord->id;
        }

        $meta = [
            'source' => 'farmer_user_update',
            'detail_url' => '/admin/farmer-users/' . $userRecord->id,
            'user_id' => $userRecord->id,
            'farmer_name' => $farmerName !== '' ? $farmerName : null,
            'farmer_code' => $farmerCode !== '' ? $farmerCode : null,
        ];

        $payload = [
            'task_title' => 'แจ้งเตือนอัปเดตข้อมูลเกษตรกร',
            'issue_category' => 'เอกสาร',
            'status' => 'pending_review',
            'priority' => 'urgent',
            'response_required' => true,
            'latest_note' => $latestNote,
            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
        ];

        if (Schema::hasColumn('dashboard_work_items', 'plot_id') && filled($userRecord->plot_id ?? null)) {
            $payload['plot_id'] = $userRecord->plot_id;
        }

        if (Schema::hasColumn('dashboard_work_items', 'resolved_at')) {
            $payload['resolved_at'] = null;
        }

        $query = DB::table('dashboard_work_items');

        foreach ($match as $column => $value) {
            $query->where($column, $value);
        }

        if ($query->exists()) {
            $query->update(array_merge($payload, [
                'updated_at' => now(),
            ]));

            return;
        }

        DB::table('dashboard_work_items')->insert(array_merge($match, $payload, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function memberRegisteredAtPayload(): array
    {
        if (! $this->hasUserColumn('member_registered_at')) {
            return [];
        }

        return [
            'member_registered_at' => now(),
        ];
    }

    private function optionalUserSelect(string $column, string $alias): \Illuminate\Database\Query\Expression|string
    {
        if ($this->hasUserColumn($column)) {
            return "users.{$column} as {$alias}";
        }

        return DB::raw("NULL as {$alias}");
    }

    private function optionalTableSelect(string $tableAlias, string $column, string $alias): \Illuminate\Database\Query\Expression|string
    {
        $table = str_contains($tableAlias, ' ') ? strstr($tableAlias, ' ', true) : $tableAlias;

        if ($this->hasColumn($table, $column)) {
            return "{$tableAlias}.{$column} as {$alias}";
        }

        return DB::raw("NULL as {$alias}");
    }

    private function passwordPayload(string $plainPassword): array
    {
        $hashedPassword = Hash::make($plainPassword);
        $payload = [];

        if ($this->hasUserColumn('password_hash')) {
            $payload['password_hash'] = $hashedPassword;
        }

        if ($this->hasUserColumn('password')) {
            $payload['password'] = $hashedPassword;
        }

        return $payload;
    }

    private function adminRoleOptions(): array
    {
        return array_values(array_map(
            function (array $role): array {
                $role['name_th'] = match ($role['code'] ?? null) {
                    'SUPERADMIN' => 'Super Admin',
                    'ADMIN' => 'Admin',
                    default => $role['name_th'] ?? (string) ($role['code'] ?? ''),
                };

                return $role;
            },
            array_filter(
                $this->roleOptions(),
                fn (array $role) => in_array(($role['code'] ?? null), ['ADMIN', 'SUPERADMIN'], true)
            )
        ));
    }

    private function assignableAdminOptions(): array
    {
        $currentAdmin = Auth::user();

        $query = DB::table('users')
            ->leftJoin('admin_profiles', 'admin_profiles.user_id', '=', 'users.id')
            ->whereIn('users.role', ['ADMIN', 'SUPERADMIN'])
            ->select([
                'users.id',
                'users.username',
                'users.role',
                $this->optionalTableSelect('admin_profiles', 'display_name', 'display_name'),
                $this->optionalTableSelect('admin_profiles', 'title', 'title'),
            ])
            ->orderBy('users.role')
            ->orderBy('users.username');

        if (! AdminAccess::isSuperAdmin($currentAdmin) && $currentAdmin) {
            $query->where('users.id', $currentAdmin->id);
        }

        return $query
            ->get()
            ->map(function ($admin) {
                $displayName = $admin->display_name ?: $admin->username;
                $parts = array_filter([
                    $displayName,
                    $admin->title ?: null,
                    $admin->role ?: null,
                ]);

                return [
                    'id' => (string) $admin->id,
                    'label' => implode(' | ', $parts),
                ];
            })
            ->values()
            ->all();
    }

    private function assignableAdminIds(): array
    {
        return array_column($this->assignableAdminOptions(), 'id');
    }

    private function findAdminUserRecord(string $userId): ?object
    {
        return DB::table('users')
            ->leftJoin('admin_profiles', 'admin_profiles.user_id', '=', 'users.id')
            ->leftJoin('admin_area_scopes', function ($join): void {
                $join->on('admin_area_scopes.admin_user_id', '=', 'users.id');

                if ($this->hasColumn('admin_area_scopes', 'is_active')) {
                    $join->where('admin_area_scopes.is_active', '=', true);
                }
            })
            ->where('users.id', $userId)
            ->whereIn('users.role', ['ADMIN', 'SUPERADMIN'])
            ->select([
                'users.id',
                'users.username',
                'users.phone',
                'users.role',
                $this->optionalUserSelect('member_registered_at', 'member_registered_at'),
                $this->optionalTableSelect('admin_profiles', 'display_name', 'display_name'),
                $this->optionalTableSelect('admin_profiles', 'title', 'admin_title'),
                $this->optionalTableSelect('admin_area_scopes', 'province_name', 'scope_province'),
                $this->optionalTableSelect('admin_area_scopes', 'district_name', 'scope_district'),
                $this->optionalTableSelect('admin_area_scopes', 'subdistrict_name', 'scope_subdistrict'),
                $this->optionalTableSelect('admin_area_scopes', 'scope_label', 'scope_label'),
            ])
            ->first();
    }

    private function roleOptions(): array
    {
        if (!$this->hasTable('roles')) {
            return $this->filteredRoleOptions($this->fallbackRoleOptions());
        }

        $query = DB::table('roles')
            ->select([
                'code',
                $this->optionalTableSelect('roles', 'name_th', 'name_th'),
                $this->optionalTableSelect('roles', 'description', 'description'),
            ]);

        if ($this->hasColumn('roles', 'is_active')) {
            $query->where('is_active', true);
        }

        if ($this->hasColumn('roles', 'sort_order')) {
            $query->orderBy('sort_order');
        }

        $roles = $query
            ->orderBy('code')
            ->get()
            ->map(fn ($role) => [
                'code' => (string) $role->code,
                'name_th' => $role->name_th ?: (string) $role->code,
                'description' => $role->description ?: null,
            ])
            ->values()
            ->all();

        return $this->filteredRoleOptions($roles !== [] ? $roles : $this->fallbackRoleOptions());
    }

    private function roleValidationRules(bool $required = true): array
    {
        $rules = [$required ? 'required' : 'nullable', 'string', 'max:50'];

        if (! AdminAccess::isSuperAdmin(Auth::user())) {
            $rules[] = Rule::in(['FARMER']);

            return $rules;
        }

        if ($this->hasTable('roles')) {
            $existsRule = Rule::exists('roles', 'code');

            if ($this->hasColumn('roles', 'is_active')) {
                $existsRule = $existsRule->where(fn ($query) => $query->where('is_active', true));
            }

            $rules[] = $existsRule;

            return $rules;
        }

        $rules[] = Rule::in(array_column($this->fallbackRoleOptions(), 'code'));

        return $rules;
    }

    private function defaultRoleCode(): string
    {
        $roleOptions = $this->roleOptions();

        foreach ($roleOptions as $roleOption) {
            if (($roleOption['code'] ?? null) === 'FARMER') {
                return 'FARMER';
            }
        }

        return $roleOptions[0]['code'] ?? 'FARMER';
    }

    private function fallbackRoleOptions(): array
    {
        return [
            [
                'code' => 'SUPERADMIN',
                'name_th' => 'ผู้ดูแลระบบสูงสุด',
                'description' => 'ดูแลได้ทั้งระบบและกำหนดสิทธิ์ให้แอดมินคนอื่น',
            ],
            [
                'code' => 'FARMER',
                'name_th' => 'เกษตรกร',
                'description' => 'ผู้ใช้งานทั่วไปสำหรับเกษตรกร',
            ],
            [
                'code' => 'ADMIN',
                'name_th' => 'ผู้ดูแลระบบ',
                'description' => 'ผู้ใช้งานสำหรับจัดการระบบหลังบ้าน',
            ],
        ];
    }

    private function filteredRoleOptions(array $roleOptions): array
    {
        if (AdminAccess::isSuperAdmin(Auth::user())) {
            return $roleOptions;
        }

        return array_values(array_filter($roleOptions, fn (array $role) => ($role['code'] ?? null) === 'FARMER'));
    }

    private function canManageUserRecord(object $userRecord): bool
    {
        return AdminAccess::canManageUser(
            Auth::user(),
            $userRecord,
            $userRecord->province ?? null,
            $userRecord->district ?? null,
            $userRecord->subdistrict ?? null
        );
    }

    private function hasUserColumn(string $column): bool
    {
        return $this->hasColumn('users', $column);
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

    private function fallbackProvinceName($provinceId): ?string
    {
        if ($provinceId === null || $provinceId === '') {
            return null;
        }

        $map = [
            3 => 'นนทบุรี',
            6 => 'อ่างทอง',
            23 => 'อุบลราชธานี',
            40 => 'ลำปาง',
        ];

        $provinceId = (int) $provinceId;

        return $map[$provinceId] ?? null;
    }

    private function fallbackDistrictName($districtId): ?string
    {
        if ($districtId === null || $districtId === '') {
            return null;
        }

        $map = [
            6 => 'เมืองอ่างทอง',
            23 => 'เมืองอุบลราชธานี',
            40 => 'เมืองลำปาง',
        ];

        $districtId = (int) $districtId;

        return $map[$districtId] ?? null;
    }

    private function fallbackDistrictFromSubdistrict(?string $subdistrict): ?string
    {
        if (! filled($subdistrict)) {
            return null;
        }

        $normalized = trim((string) $subdistrict);

        $map = [
            'นาทิน' => 'ตระการพืชผล',
        ];

        return $map[$normalized] ?? null;
    }

    private function fallbackDistrictFromPostcode(?string $postcode): ?string
    {
        if (! filled($postcode)) {
            return null;
        }

        $normalized = trim((string) $postcode);

        $map = [
            '34130' => 'ตระการพืชผล',
        ];

        return $map[$normalized] ?? null;
    }

    private function fallbackProvinceNameFromDistrict(?string $districtName): ?string
    {
        if (!filled($districtName)) {
            return null;
        }

        $normalized = trim((string) $districtName);

        $map = [
            'เมืองนนทบุรี' => 'นนทบุรี',
            'เมืองอ่างทอง' => 'อ่างทอง',
            'เมืองลำปาง' => 'ลำปาง',
            'เมืองอุบลราชธานี' => 'อุบลราชธานี',
            'ตระการพืชผล' => 'อุบลราชธานี',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        $genericPrefixes = ['เขต', 'อำเภอ', 'เมือง'];

        foreach ($genericPrefixes as $prefix) {
            if (mb_strpos($normalized, $prefix) === 0) {
                $candidate = trim(mb_substr($normalized, mb_strlen($prefix)));

                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function fallbackProvinceNameFromPostcode(?string $postcode): ?string
    {
        if (! filled($postcode)) {
            return null;
        }

        $normalized = trim((string) $postcode);

        $map = [
            '34130' => 'อุบลราชธานี',
        ];

        return $map[$normalized] ?? null;
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
