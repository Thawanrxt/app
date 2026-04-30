<?php

namespace App\Services;

use App\Support\AdminAccess;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SrpFarmerDirectoryService
{
    private array $tableExistsCache = [];

    public function farmers(): Collection
    {
        try {
            return $this->databaseFarmerCollection();
        } catch (Throwable) {
            return collect();
        }
    }

    public function passedFarmers(): Collection
    {
        return $this->farmers()
            ->filter(fn (array $farmer): bool => (int) ($farmer['average_progress'] ?? 0) >= 100)
            ->values();
    }

    public function summary(Collection $farmers): array
    {
        return [
            'all_farmers' => $farmers->count(),
            'with_primary_admin' => $farmers->filter(fn (array $farmer) => filled($farmer['primary_admin_name']))->count(),
            'with_app_activity' => $farmers->filter(fn (array $farmer) => $farmer['activity_count'] > 0)->count(),
            'all_plots' => $farmers->sum('plot_count'),
        ];
    }

    public function requiredTablesAvailable(): bool
    {
        return $this->missingRequiredTables() === [];
    }

    public function missingRequiredTables(): array
    {
        return collect([
            'users',
            'farmer_profiles',
            'farmer_registrations',
            'plots',
        ])
            ->reject(fn (string $table) => $this->hasTable($table))
            ->values()
            ->all();
    }

    private function databaseFarmerCollection(): Collection
    {
        if (! $this->requiredTablesAvailable()) {
            return collect();
        }

        $joinProvinces = $this->hasTable('provinces');
        $joinDistricts = $this->hasTable('districts');
        $actingUser = Auth::user();

        $farmerRows = DB::table('users')
            ->leftJoin('farmer_profiles as profiles', 'profiles.user_id', '=', 'users.id')
            ->leftJoin('farmer_registrations as registrations', 'registrations.profile_id', '=', 'profiles.id')
            ->when($joinProvinces, fn ($query) => $query->leftJoin('provinces', 'provinces.id', '=', 'profiles.province_id'))
            ->when($joinDistricts, fn ($query) => $query->leftJoin('districts', 'districts.id', '=', 'profiles.district_id'))
            ->when($joinProvinces && $joinDistricts, fn ($query) => $query->leftJoin('provinces as district_provinces', 'district_provinces.id', '=', 'districts.province_id'))
            ->where('users.role', 'FARMER')
            ->select([
                'users.id',
                'users.username',
                'users.phone',
                $this->hasUserColumn('province') ? 'users.province as user_province' : DB::raw('NULL as user_province'),
                $this->hasUserColumn('district') ? 'users.district as user_district' : DB::raw('NULL as user_district'),
                $this->hasUserColumn('subdistrict') ? 'users.subdistrict as user_subdistrict' : DB::raw('NULL as user_subdistrict'),
                $this->hasUserColumn('postcode') ? 'users.postcode as user_postcode' : DB::raw('NULL as user_postcode'),
                'profiles.full_name',
                'profiles.id_card_number',
                'profiles.birthdate',
                'profiles.address',
                $this->hasColumn('farmer_profiles', 'subdistrict') ? 'profiles.subdistrict as profile_subdistrict' : DB::raw('NULL as profile_subdistrict'),
                $this->hasColumn('farmer_profiles', 'postcode') ? 'profiles.postcode as profile_postcode' : DB::raw('NULL as profile_postcode'),
                'registrations.reg_number as farmer_code',
                'registrations.reg_date as registered_at',
                $joinProvinces ? 'provinces.name_th as province_name' : DB::raw('NULL as province_name'),
                $joinProvinces && $joinDistricts ? 'district_provinces.name_th as district_province_name' : DB::raw('NULL as district_province_name'),
                $joinDistricts ? 'districts.name_th as district_name' : DB::raw('NULL as district_name'),
            ])
            ->orderBy('profiles.full_name')
            ->orderBy('users.username')
            ->get();

        if ($farmerRows->isEmpty()) {
            return collect();
        }

        $assignmentsByFarmer = $this->assignmentMapForFarmers($farmerRows->pluck('id'));
        $plotsByFarmer = $this->plotsByFarmer($farmerRows->pluck('id'));
        $assignedFarmerIds = $this->assignedFarmerIdsForAdmin($actingUser?->id);

        return $farmerRows
            ->map(function ($row) use ($assignmentsByFarmer, $plotsByFarmer): array {
                $farmerAssignments = $assignmentsByFarmer->get($row->id, collect());
                $plots = $plotsByFarmer->get($row->id, collect())->values();
                $addressLocation = $this->locationFromAddress($row->address);
                $subdistrict = $row->profile_subdistrict ?: $row->user_subdistrict;
                $postcode = $row->profile_postcode ?: $row->user_postcode;
                $districtName = $row->district_name
                    ?: $row->user_district
                    ?: ($addressLocation['district'] ?? null)
                    ?: $this->fallbackDistrictFromSubdistrict($subdistrict)
                    ?: $this->fallbackDistrictFromPostcode($postcode);
                $provinceName = $row->province_name
                    ?: $row->district_province_name
                    ?: $row->user_province
                    ?: ($addressLocation['province'] ?? null)
                    ?: $this->fallbackProvinceNameFromDistrict($districtName)
                    ?: $this->fallbackProvinceNameFromPostcode($postcode);
                $fullAddress = collect([
                    filled($row->address) ? trim((string) $row->address) : null,
                    filled($subdistrict) ? 'ตำบล' . trim((string) $subdistrict) : null,
                    filled($districtName) ? 'อำเภอ' . trim((string) $districtName) : null,
                    filled($provinceName) ? 'จังหวัด' . trim((string) $provinceName) : null,
                    filled($postcode) ? trim((string) $postcode) : null,
                ])->filter()->implode(' ');

                $primaryAssignment = $farmerAssignments->firstWhere('is_primary', true) ?? $farmerAssignments->first();
                $secondaryAdmins = $farmerAssignments
                    ->filter(fn ($assignment) => ! ($assignment['is_primary'] ?? false))
                    ->pluck('admin_name')
                    ->filter()
                    ->values()
                    ->all();

                $averageProgress = $plots->isNotEmpty()
                    ? (int) round((float) $plots->avg('progress_percent'))
                    : 0;

                $latestPlot = $plots
                    ->sortByDesc(fn (array $plot) => $plot['last_activity_sort'] ?? '')
                    ->first();

                return [
                    'id' => $row->id,
                    'slug' => $this->farmerSlug($row->full_name ?: $row->username ?: 'farmer', (string) $row->id),
                    'name' => $row->full_name ?: $row->username ?: 'ไม่ระบุชื่อ',
                    'username' => $row->username,
                    'phone' => $row->phone ?: '-',
                    'citizen_id' => $row->id_card_number ?: '-',
                    'birthdate' => filled($row->birthdate) ? Carbon::parse($row->birthdate)->format('d/m/Y') : '-',
                    'address' => $row->address ?: '-',
                    'subdistrict' => $subdistrict ?: '-',
                    'postcode' => $postcode ?: '-',
                    'full_address' => $fullAddress !== '' ? $fullAddress : '-',
                    'farmer_code' => $row->farmer_code ?: '-',
                    'registered_at' => filled($row->registered_at) ? Carbon::parse($row->registered_at)->format('d/m/Y') : '-',
                    'province' => $provinceName ?: '-',
                    'district' => $districtName ?: '-',
                    'primary_admin_name' => $primaryAssignment['admin_name'] ?? '-',
                    'assignment_note' => $primaryAssignment['note'] ?? null,
                    'secondary_admins' => $secondaryAdmins,
                    'plot_count' => $plots->count(),
                    'activity_count' => (int) $plots->sum('activity_count'),
                    'average_progress' => $averageProgress,
                    'last_activity_at' => $latestPlot['last_activity_at'] ?? '-',
                    'latest_activity_name' => $latestPlot['latest_activity_name'] ?? '-',
                    'plots' => $plots->all(),
                ];
            })
            ->filter(function (array $farmer) use ($actingUser, $assignedFarmerIds): bool {
                if (AdminAccess::isSuperAdmin($actingUser)) {
                    return true;
                }

                if ($assignedFarmerIds->isNotEmpty()) {
                    return $assignedFarmerIds->contains($farmer['id']);
                }

                if (AdminAccess::shouldRestrictByScope($actingUser)) {
                    return AdminAccess::locationMatches(
                        AdminAccess::activeScopes($actingUser),
                        $farmer['province'] !== '-' ? $farmer['province'] : null,
                        $farmer['district'] !== '-' ? $farmer['district'] : null,
                        null
                    );
                }

                return true;
            })
            ->sortBy('name')
            ->values();
    }

    private function assignmentMapForFarmers(Collection $farmerIds): Collection
    {
        if (! $this->hasTable('admin_farmer_assignments') || $farmerIds->isEmpty()) {
            return collect();
        }

        $rows = DB::table('admin_farmer_assignments as assignments')
            ->leftJoin('users as admins', 'admins.id', '=', 'assignments.admin_user_id')
            ->leftJoin('admin_profiles as profiles', 'profiles.user_id', '=', 'admins.id')
            ->select([
                'assignments.farmer_user_id',
                'assignments.admin_user_id',
                'assignments.note',
                $this->hasColumn('admin_farmer_assignments', 'is_primary')
                    ? 'assignments.is_primary'
                    : DB::raw('false as is_primary'),
                'admins.username as admin_username',
                $this->hasColumn('admin_profiles', 'display_name')
                    ? 'profiles.display_name as admin_display_name'
                    : DB::raw('NULL as admin_display_name'),
            ])
            ->whereIn('assignments.farmer_user_id', $farmerIds->all())
            ->orderByDesc($this->hasColumn('admin_farmer_assignments', 'is_primary') ? 'assignments.is_primary' : 'assignments.created_at')
            ->orderBy('assignments.created_at')
            ->get()
            ->map(function ($row): array {
                return [
                    'farmer_user_id' => $row->farmer_user_id,
                    'admin_user_id' => $row->admin_user_id,
                    'is_primary' => (bool) ($row->is_primary ?? false),
                    'admin_name' => $row->admin_display_name ?: $row->admin_username ?: '-',
                    'note' => $this->cleanAssignmentNote($row->note ?? null),
                ];
            });

        return $rows->groupBy('farmer_user_id');
    }

    private function plotsByFarmer(Collection $farmerIds): Collection
    {
        if (! $this->hasTable('plots') || $farmerIds->isEmpty()) {
            return collect();
        }

        $plotRows = DB::table('plots')
            ->whereIn('user_id', $farmerIds->all())
            ->select([
                'id',
                'user_id',
                'farm_id',
                'plot_name',
                'area_rai',
                'area_sq_wa',
                'crop_type',
                'status',
                'address',
            ])
            ->orderBy('plot_name')
            ->get();

        $activityRows = collect();

        if ($this->hasTable('planting_plans') && $this->hasTable('activity_events')) {
            $activityRows = DB::table('activity_events as events')
                ->join('planting_plans as plans', 'plans.id', '=', 'events.plan_id')
                ->join('plots', 'plots.id', '=', 'plans.plot_id')
                ->leftJoin('activity_types as types', 'types.id', '=', 'events.type_id')
                ->whereIn('plots.user_id', $farmerIds->all())
                ->select([
                    'plots.id as plot_id',
                    'events.status',
                    'events.performed_at',
                    'types.name_th as activity_name',
                ])
                ->orderByDesc('events.performed_at')
                ->get();
        }

        $activitiesByPlot = $activityRows->groupBy('plot_id');

        return $plotRows
            ->map(function ($plot) use ($activitiesByPlot): array {
                $plotActivities = $activitiesByPlot->get($plot->id, collect());
                $latestActivity = $plotActivities->first();
                $completedCount = $plotActivities
                    ->filter(fn ($activity) => strtoupper((string) ($activity->status ?? '')) === 'DONE')
                    ->count();
                $activityCount = $plotActivities->count();
                $progress = $activityCount > 0 ? (int) round(($completedCount / max($activityCount, 1)) * 100) : 0;
                $lastActivityAt = filled($latestActivity?->performed_at)
                    ? Carbon::parse($latestActivity->performed_at)->format('d/m/Y')
                    : '-';

                return [
                    'plot_id' => $plot->id,
                    'user_id' => $plot->user_id,
                    'plot_name' => $plot->plot_name ?: 'แปลงหลัก',
                    'farm_id' => $plot->farm_id ?: '-',
                    'crop_type' => $plot->crop_type ?: '-',
                    'area' => $this->formatArea($plot->area_rai, $plot->area_sq_wa),
                    'status' => $plot->status ?: '-',
                    'address' => $plot->address ?: '-',
                    'activity_count' => $activityCount,
                    'progress_percent' => $progress,
                    'latest_activity_name' => $latestActivity?->activity_name ?: '-',
                    'last_activity_at' => $lastActivityAt,
                    'last_activity_sort' => filled($latestActivity?->performed_at) ? Carbon::parse($latestActivity->performed_at)->timestamp : 0,
                ];
            })
            ->groupBy('user_id');
    }

    private function assignedFarmerIdsForAdmin(?string $adminUserId): Collection
    {
        if (! filled($adminUserId) || ! $this->hasTable('admin_farmer_assignments')) {
            return collect();
        }

        return DB::table('admin_farmer_assignments')
            ->where('admin_user_id', $adminUserId)
            ->pluck('farmer_user_id');
    }

    private function cleanAssignmentNote(?string $note): ?string
    {
        if (! filled($note)) {
            return null;
        }

        $clean = preg_replace('/\s*\[assignment_type:[^\]]+\]/i', '', (string) $note);
        $clean = trim((string) $clean);

        $lines = [];

        foreach (preg_split("/\r\n|\n|\r/", $clean) as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            // Hide mojibake rows from legacy data instead of showing broken text.
            if (preg_match('/Ã|Â|à¸|à¹/u', $line) && ! preg_match('/[\x{0E00}-\x{0E7F}]/u', $line)) {
                continue;
            }

            if (Str::startsWith($line, 'หมายเหตุ:')) {
                $line = trim(Str::after($line, ':'));
            }

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines !== [] ? implode("\n", $lines) : null;
    }

    private function formatArea(mixed $rai, mixed $squareWa): string
    {
        $parts = [];

        if (filled($rai) && (float) $rai > 0) {
            $parts[] = rtrim(rtrim((string) $rai, '0'), '.') . ' ไร่';
        }

        if (filled($squareWa) && (float) $squareWa > 0) {
            $parts[] = rtrim(rtrim((string) $squareWa, '0'), '.') . ' ตร.ว.';
        }

        return $parts !== [] ? implode(' ', $parts) : '-';
    }

    private function hasTable(string $table): bool
    {
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            return $this->tableExistsCache[$table] = Schema::hasTable($table);
        } catch (Throwable) {
            return $this->tableExistsCache[$table] = false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasColumn($table, $column);
        } catch (Throwable) {
            return false;
        }
    }

    private function hasUserColumn(string $column): bool
    {
        return $this->hasColumn('users', $column);
    }

    private function locationFromAddress(?string $address): array
    {
        if (! filled($address)) {
            return ['province' => null, 'district' => null];
        }

        $normalized = preg_replace('/\s+/u', ' ', trim((string) $address));
        $district = $this->extractAddressSegment($normalized, ['อำเภอ', 'อ.', 'เขต']);
        $province = $this->extractAddressSegment($normalized, ['จังหวัด', 'จ.']);

        if (! filled($district) && $this->hasTable('districts')) {
            $district = DB::table('districts')
                ->select('name_th')
                ->orderByRaw('CHAR_LENGTH(name_th) DESC')
                ->get()
                ->pluck('name_th')
                ->first(fn ($name) => filled($name) && Str::contains($normalized, $name));
        }

        if (! filled($province) && $this->hasTable('provinces')) {
            $province = DB::table('provinces')
                ->select('name_th')
                ->orderByRaw('CHAR_LENGTH(name_th) DESC')
                ->get()
                ->pluck('name_th')
                ->first(fn ($name) => filled($name) && Str::contains($normalized, $name));
        }

        if (! filled($province) && filled($district)) {
            $province = $this->fallbackProvinceNameFromDistrict($district);
        }

        return [
            'province' => filled($province) ? $province : null,
            'district' => filled($district) ? $district : null,
        ];
    }

    private function extractAddressSegment(string $address, array $prefixes): ?string
    {
        foreach ($prefixes as $prefix) {
            $pattern = '/(?:^|\s)' . preg_quote($prefix, '/') . '\s*([^\s,]+)/u';

            if (preg_match($pattern, $address, $matches) === 1) {
                $candidate = trim((string) ($matches[1] ?? ''));

                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function fallbackDistrictFromSubdistrict(?string $subdistrict): ?string
    {
        if (! filled($subdistrict)) {
            return null;
        }

        $normalized = trim((string) $subdistrict);

        $map = [
            'นาพิน' => 'ตระการพืชผล',
            'Ã Â¸â„¢Ã Â¸Â²Ã Â¸Å¾Ã Â¸Â´Ã Â¸â„¢' => 'Ã Â¸â€¢Ã Â¸Â£Ã Â¸Â°Ã Â¸ÂÃ Â¸Â²Ã Â¸Â£Ã Â¸Å¾Ã Â¸Â·Ã Â¸Å Ã Â¸Å“Ã Â¸Â¥',
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
        if (! filled($districtName)) {
            return null;
        }

        $normalized = trim((string) $districtName);

        if ($this->hasTable('districts') && $this->hasTable('provinces')) {
            $provinceName = DB::table('districts')
                ->join('provinces', 'provinces.id', '=', 'districts.province_id')
                ->where('districts.name_th', $normalized)
                ->value('provinces.name_th');

            if (filled($provinceName)) {
                return $provinceName;
            }
        }

        $map = [
            'เมืองนนทบุรี' => 'นนทบุรี',
            'เมืองอ่างทอง' => 'อ่างทอง',
            'เมืองลำปาง' => 'ลำปาง',
            'เมืองอุบลราชธานี' => 'อุบลราชธานี',
            'เมืองระยอง' => 'ระยอง',
            'บางบ่อ' => 'สมุทรปราการ',
        ];

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        foreach (['เมือง', 'อำเภอ', 'เขต'] as $prefix) {
            if (mb_strpos($normalized, $prefix) === 0) {
                $candidate = trim(mb_substr($normalized, mb_strlen($prefix)));

                if ($candidate !== '') {
                    if ($this->hasTable('provinces')) {
                        $provinceName = DB::table('provinces')
                            ->where('name_th', $candidate)
                            ->value('name_th');

                        if (filled($provinceName)) {
                            return $provinceName;
                        }
                    }

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

    private function farmerSlug(string $name, string $key): string
    {
        $slug = Str::slug($name);

        return trim($slug !== '' ? $slug : 'farmer', '-') . '-' . Str::lower(Str::substr(md5($key), 0, 8));
    }
}
