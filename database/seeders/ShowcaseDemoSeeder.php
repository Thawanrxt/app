<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ShowcaseDemoSeeder extends Seeder
{
    private array $usersByKey = [];

    private array $profilesByKey = [];

    private array $plotsByKey = [];

    private array $plansByKey = [];

    private array $typeIds = [];

    private array $riceVarietyIds = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedRoles();
            $this->seedAdminUsers();
            $this->seedFarmerUsers();
            $this->seedAssignments();
            $this->seedRiceVarieties();
            $this->seedActivityTypes();
            $this->seedTrackingCoreData();
            $this->seedLegacyTrackingTables();
            $this->seedDashboardWorkItems();
            $this->seedSupportTickets();
        });
    }

    private function seedRoles(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $now = now();

        $roles = [
            ['code' => 'SUPERADMIN', 'name_th' => 'ผู้ดูแลระบบสูงสุด', 'description' => 'ดูแลได้ทั้งระบบและกำหนดสิทธิ์ให้แอดมินคนอื่น', 'sort_order' => 0],
            ['code' => 'ADMIN', 'name_th' => 'ผู้ดูแลระบบ', 'description' => 'ผู้ใช้งานสำหรับจัดการระบบหลังบ้าน', 'sort_order' => 10],
            ['code' => 'FARMER', 'name_th' => 'เกษตรกร', 'description' => 'ผู้ใช้งานทั่วไปสำหรับเกษตรกร', 'sort_order' => 20],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['code' => $role['code']],
                [
                    'name_th' => $role['name_th'],
                    'description' => $role['description'],
                    'is_active' => true,
                    'sort_order' => $role['sort_order'],
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                ]
            );
        }
    }

    private function seedAdminUsers(): void
    {
        foreach ([
            [
                'key' => 'superadmin',
                'username' => 'superadmin',
                'email' => 'superadmin.demo@example.com',
                'name' => 'Super Admin Demo',
                'display_name' => 'ผู้ดูแลระบบสาธิต',
                'title' => 'หัวหน้าระบบกลาง',
                'role' => 'SUPERADMIN',
                'phone' => '0811111111',
            ],
            [
                'key' => 'admin_central',
                'username' => 'admin.central',
                'email' => 'admin.central.demo@example.com',
                'name' => 'Central Admin Demo',
                'display_name' => 'ฟารีดา หวันตาหลา',
                'title' => 'ผู้ประสานงานภาคกลาง',
                'role' => 'ADMIN',
                'phone' => '0822222222',
            ],
            [
                'key' => 'admin_north',
                'username' => 'admin.north',
                'email' => 'admin.north.demo@example.com',
                'name' => 'North Admin Demo',
                'display_name' => 'อรอนงค์ แก้วคำ',
                'title' => 'ผู้ดูแลพื้นที่ภาคเหนือ',
                'role' => 'ADMIN',
                'phone' => '0833333333',
            ],
        ] as $admin) {
            $userId = $this->upsertUser([
                'username' => $admin['username'],
                'email' => $admin['email'],
                'name' => $admin['name'],
                'role' => $admin['role'],
                'phone' => $admin['phone'],
                'status' => 'ใช้งาน',
                'password_hash' => Hash::make('Admin1234'),
            ]);

            $this->usersByKey[$admin['key']] = $userId;

            if (Schema::hasTable('admin_profiles')) {
                $payload = [
                    'id' => $this->deterministicUuid('admin-profile-' . $admin['key']),
                    'user_id' => $userId,
                    'display_name' => $admin['display_name'],
                    'title' => $admin['title'],
                    'is_active' => true,
                    'notes' => 'บัญชีเดโม่สำหรับการพรีเซนต์',
                ];

                $this->touchTimestamps('admin_profiles', $payload);

                DB::table('admin_profiles')->updateOrInsert(
                    ['user_id' => $userId],
                    $payload
                );
            }
        }
    }

    private function seedFarmerUsers(): void
    {
        $farmers = [
            [
                'key' => 'farmer_a',
                'username' => 'natchaya24',
                'email' => 'natchaya24.demo@example.com',
                'name' => 'ณัฐชยา หน่อใหม่',
                'citizen_id' => '1348500034365',
                'phone' => '0972686348',
                'birth_date' => '1998-07-24',
                'address_line' => '73 หมู่ 1',
                'province' => 'อุบลราชธานี',
                'district' => 'ตระการพืชผล',
                'subdistrict' => 'นาทิน',
                'postcode' => '34130',
                'farmer_code' => '520602001711',
                'registered_at' => '2014-04-17',
                'registered_province' => 'อุบลราชธานี',
                'farm_province' => 'อุบลราชธานี',
                'farm_area_rai' => 2,
                'farm_area_ngan' => 3,
                'farm_area_square_wa' => 53,
                'crop_type' => 'ข้าว',
                'plot_name' => 'นาข้าววังทิน',
                'farm_id' => 'FARM-C8A7CE',
                'plot_status' => 'active',
            ],
            [
                'key' => 'farmer_b',
                'username' => 'thawanrat_04',
                'email' => 'thawanrat04.demo@example.com',
                'name' => 'ธวัลรัตน์ เลิศเทอดสกุล',
                'citizen_id' => '2345600034366',
                'phone' => '0617737384',
                'birth_date' => '1992-02-12',
                'address_line' => '154/1',
                'province' => 'นนทบุรี',
                'district' => 'เมืองนนทบุรี',
                'subdistrict' => 'บางกระสอ',
                'postcode' => '11000',
                'farmer_code' => '520602001712',
                'registered_at' => '2015-06-01',
                'registered_province' => 'นนทบุรี',
                'farm_province' => 'นนทบุรี',
                'farm_area_rai' => 4,
                'farm_area_ngan' => 0,
                'farm_area_square_wa' => 50,
                'crop_type' => 'ข้าว',
                'plot_name' => 'นามะลิ',
                'farm_id' => 'FARM-FBB07C',
                'plot_status' => 'active',
            ],
            [
                'key' => 'farmer_c',
                'username' => 'nawin.w',
                'email' => 'nawinw.demo@example.com',
                'name' => 'นาวิน วิจุลสะหา',
                'citizen_id' => '3456700034367',
                'phone' => '0965554444',
                'birth_date' => '1990-11-08',
                'address_line' => '45 หมู่ 6',
                'province' => 'อ่างทอง',
                'district' => 'เมืองอ่างทอง',
                'subdistrict' => 'ตลาดหลวง',
                'postcode' => '14000',
                'farmer_code' => '520602001713',
                'registered_at' => '2016-08-20',
                'registered_province' => 'อ่างทอง',
                'farm_province' => 'อ่างทอง',
                'farm_area_rai' => 5,
                'farm_area_ngan' => 1,
                'farm_area_square_wa' => 20,
                'crop_type' => 'ข้าว',
                'plot_name' => 'นาทุ่ง',
                'farm_id' => 'FARM-073387',
                'plot_status' => 'active',
            ],
            [
                'key' => 'farmer_d',
                'username' => 'wiraya01',
                'email' => 'wiraya01.demo@example.com',
                'name' => 'วิระยา หิมะคุณ',
                'citizen_id' => '4567800034368',
                'phone' => '0954443333',
                'birth_date' => '1995-09-19',
                'address_line' => '86/12',
                'province' => 'อุบลราชธานี',
                'district' => 'ตระการพืชผล',
                'subdistrict' => 'นาทิน',
                'postcode' => '34130',
                'farmer_code' => '520602001714',
                'registered_at' => '2017-10-10',
                'registered_province' => 'อุบลราชธานี',
                'farm_province' => 'อุบลราชธานี',
                'farm_area_rai' => 3,
                'farm_area_ngan' => 0,
                'farm_area_square_wa' => 60,
                'crop_type' => 'ข้าว',
                'plot_name' => 'นาวัง',
                'farm_id' => 'FARM-877CB9',
                'plot_status' => 'active',
            ],
        ];

        foreach ($farmers as $farmer) {
            $provinceId = $this->resolveProvinceIdByName($farmer['province'] ?? null);
            $districtId = $this->resolveDistrictIdByName($farmer['district'] ?? null, $provinceId);
            $registeredProvinceId = $this->resolveProvinceIdByName($farmer['registered_province'] ?? null);
            $farmProvinceId = $this->resolveProvinceIdByName($farmer['farm_province'] ?? null);

            $userId = $this->upsertUser([
                'username' => $farmer['username'],
                'email' => $farmer['email'],
                'name' => $farmer['name'],
                'role' => 'FARMER',
                'phone' => $farmer['phone'],
                'status' => 'ใช้งาน',
                'citizen_id' => $farmer['citizen_id'],
                'birth_date' => $farmer['birth_date'],
                'address_line' => $farmer['address_line'],
                'province' => $farmer['province'],
                'district' => $farmer['district'],
                'subdistrict' => $farmer['subdistrict'],
                'postcode' => $farmer['postcode'],
                'farmer_code' => $farmer['farmer_code'],
                'registered_at' => $farmer['registered_at'],
                'registered_province' => $farmer['registered_province'],
                'farm_province' => $farmer['farm_province'],
                'farm_area_rai' => (string) $farmer['farm_area_rai'],
                'farm_area_ngan' => (string) $farmer['farm_area_ngan'],
                'farm_area_square_wa' => (string) $farmer['farm_area_square_wa'],
                'crop_type' => $farmer['crop_type'],
                'password_hash' => Hash::make('Farmer1234'),
            ]);

            $this->usersByKey[$farmer['key']] = $userId;

            if (Schema::hasTable('farmer_profiles')) {
                $existingProfile = DB::table('farmer_profiles')
                    ->when($this->hasColumn('farmer_profiles', 'id_card_number'), fn ($query) => $query->orWhere('id_card_number', $farmer['citizen_id']))
                    ->orWhere('user_id', $userId)
                    ->first();

                $profileId = $existingProfile->id ?? $this->deterministicUuid('farmer-profile-' . $farmer['key']);
                $payload = [
                    'id' => $profileId,
                    'user_id' => $userId,
                    'full_name' => $farmer['name'],
                    'id_card_number' => $farmer['citizen_id'],
                    'birthdate' => $farmer['birth_date'],
                    'address' => $farmer['address_line'],
                    'subdistrict' => $farmer['subdistrict'],
                    'postcode' => $farmer['postcode'],
                ];

                if ($this->hasColumn('farmer_profiles', 'province_id')) {
                    $payload['province_id'] = $provinceId;
                }

                if ($this->hasColumn('farmer_profiles', 'district_id')) {
                    $payload['district_id'] = $districtId;
                }

                $this->touchTimestamps('farmer_profiles', $payload);
                DB::table('farmer_profiles')->updateOrInsert(['id' => $profileId], $payload);
                $this->profilesByKey[$farmer['key']] = $profileId;
            }

            if (Schema::hasTable('farmer_registrations') && isset($this->profilesByKey[$farmer['key']])) {
                $payload = [
                    'id' => $this->deterministicUuid('registration-' . $farmer['key']),
                    'profile_id' => $this->profilesByKey[$farmer['key']],
                    'reg_number' => $farmer['farmer_code'],
                    'reg_date' => $farmer['registered_at'],
                ];

                if ($this->hasColumn('farmer_registrations', 'reg_province_id')) {
                    $payload['reg_province_id'] = $registeredProvinceId;
                }

                $this->touchTimestamps('farmer_registrations', $payload);
                DB::table('farmer_registrations')->updateOrInsert(['reg_number' => $farmer['farmer_code']], $payload);
            }

            if (Schema::hasTable('plots')) {
                $existingPlot = DB::table('plots')
                    ->where('farm_id', $farmer['farm_id'])
                    ->orWhere(function ($query) use ($userId, $farmer): void {
                        $query->where('user_id', $userId)
                            ->where('plot_name', $farmer['plot_name']);
                    })
                    ->first();

                $plotId = $existingPlot->id ?? $this->deterministicUuid('plot-' . $farmer['key']);
                $payload = [
                    'id' => $plotId,
                    'user_id' => $userId,
                    'farm_id' => $farmer['farm_id'],
                    'plot_name' => $farmer['plot_name'],
                    'area_rai' => $farmer['farm_area_rai'],
                    'area_sq_wa' => $farmer['farm_area_square_wa'],
                    'crop_type' => $farmer['crop_type'],
                    'status' => $farmer['plot_status'],
                    'address' => trim($farmer['address_line'] . ' ' . $farmer['district'] . ' ' . $farmer['province']),
                    'subdistrict' => $farmer['subdistrict'],
                    'postcode' => $farmer['postcode'],
                    'is_primary' => true,
                ];

                if ($this->hasColumn('plots', 'area_ngan')) {
                    $payload['area_ngan'] = $farmer['farm_area_ngan'];
                }

                if ($this->hasColumn('plots', 'province_id')) {
                    $payload['province_id'] = $farmProvinceId ?: $provinceId;
                }

                if ($this->hasColumn('plots', 'district_id')) {
                    $payload['district_id'] = $districtId;
                }

                $this->touchTimestamps('plots', $payload);
                DB::table('plots')->updateOrInsert(['id' => $plotId], $payload);
                $this->plotsByKey[$farmer['key']] = $plotId;
            }
        }
    }

    private function seedAssignments(): void
    {
        if (! Schema::hasTable('admin_farmer_assignments')) {
            return;
        }

        $rows = [
            ['farmer' => 'farmer_a', 'admin' => 'admin_central', 'is_primary' => true, 'note' => 'ดูแลเกษตรกรตัวอย่างสำหรับการพรีเซนต์'],
            ['farmer' => 'farmer_b', 'admin' => 'admin_north', 'is_primary' => true, 'note' => 'ดูแลกลุ่มสาธิตภาคเหนือ'],
            ['farmer' => 'farmer_c', 'admin' => 'admin_central', 'is_primary' => true, 'note' => 'ดูแลตามพื้นที่'],
            ['farmer' => 'farmer_d', 'admin' => 'admin_central', 'is_primary' => false, 'note' => 'ผู้ดูแลร่วมสำหรับเคสสาธิต'],
            ['farmer' => 'farmer_d', 'admin' => 'admin_north', 'is_primary' => true, 'note' => 'ผู้ดูแลหลักของรายการสาธิตนี้'],
        ];

        foreach ($rows as $row) {
            $farmerUserId = $this->usersByKey[$row['farmer']] ?? null;
            $adminUserId = $this->usersByKey[$row['admin']] ?? null;

            if (! $farmerUserId || ! $adminUserId) {
                continue;
            }

            $payload = [
                'id' => $this->deterministicUuid('assignment-' . $row['farmer'] . '-' . $row['admin']),
                'farmer_user_id' => $farmerUserId,
                'admin_user_id' => $adminUserId,
                'assigned_by' => $this->usersByKey['superadmin'] ?? null,
                'is_primary' => $row['is_primary'],
                'note' => $row['note'],
                'assigned_at' => Carbon::parse('2026-04-01 09:00:00'),
            ];
            $this->touchTimestamps('admin_farmer_assignments', $payload);

            DB::table('admin_farmer_assignments')->updateOrInsert(
                ['farmer_user_id' => $farmerUserId, 'admin_user_id' => $adminUserId],
                $payload
            );
        }
    }

    private function seedRiceVarieties(): void
    {
        if (! Schema::hasTable('rice_varieties')) {
            return;
        }

        $rows = [
            ['name' => 'กข43', 'rice_type' => 'ข้าวเจ้า', 'standard_duration_days' => '95-100', 'disease_resistance' => 'ค่อนข้างดี', 'pest_resistances' => json_encode(['เพลี้ยกระโดดสีน้ำตาล'])],
            ['name' => 'กข79', 'rice_type' => 'ข้าวเจ้า', 'standard_duration_days' => '110-120', 'disease_resistance' => 'ดี', 'pest_resistances' => json_encode(['หนอนกอ', 'เพลี้ยไฟ'])],
            ['name' => 'หอมมะลิ105', 'rice_type' => 'ข้าวหอมมะลิ', 'standard_duration_days' => '120-130', 'disease_resistance' => 'ปานกลาง', 'pest_resistances' => json_encode(['เพลี้ยกระโดด'])],
        ];

        foreach ($rows as $row) {
            $existingId = DB::table('rice_varieties')->where('name', $row['name'])->value('id');
            $payload = $row;

            if ($this->hasColumn('rice_varieties', 'id')) {
                $payload['id'] = $existingId ?: $this->nextIdentifierFor('rice_varieties', 'rice-variety-' . $row['name']);
            }

            $this->touchTimestamps('rice_varieties', $payload);
            DB::table('rice_varieties')->updateOrInsert(
                ['name' => $row['name']],
                $payload
            );

            $this->riceVarietyIds[$row['name']] = $payload['id'] ?? DB::table('rice_varieties')->where('name', $row['name'])->value('id');
        }
    }

    private function seedActivityTypes(): void
    {
        if (! Schema::hasTable('activity_types')) {
            return;
        }

        $types = [
            'SOIL' => 'การเตรียมดิน',
            'WATER' => 'การจัดการน้ำ',
            'FERT' => 'หว่านปุ๋ย',
            'PEST' => 'การจัดการศัตรูพืช',
            'DISEASE' => 'การจัดการโรคพืช',
            'HARVEST' => 'การเก็บเกี่ยว',
            'SALE' => 'ขายข้าวเข้าโรงสี',
        ];

        foreach ($types as $code => $name) {
            $existingId = DB::table('activity_types')->where('code', $code)->value('id');

            if ($existingId) {
                $this->typeIds[$code] = (int) $existingId;
                DB::table('activity_types')->where('id', $existingId)->update(['name_th' => $name]);
                continue;
            }

            $id = ((int) DB::table('activity_types')->max('id')) + 1;
            $payload = ['id' => $id, 'code' => $code, 'name_th' => $name];
            $this->touchTimestamps('activity_types', $payload);
            DB::table('activity_types')->insert($payload);
            $this->typeIds[$code] = $id;
        }
    }

    private function seedTrackingCoreData(): void
    {
        if (! Schema::hasTable('planting_plans') || ! Schema::hasTable('activity_events') || $this->plotsByKey === []) {
            return;
        }

        foreach ([
            'farmer_a' => ['start_date' => '2026-03-20', 'expected_harvest_date' => '2026-07-20'],
            'farmer_b' => ['start_date' => '2026-03-15', 'expected_harvest_date' => '2026-07-10'],
            'farmer_c' => ['start_date' => '2026-03-18', 'expected_harvest_date' => '2026-07-18'],
            'farmer_d' => ['start_date' => '2026-03-12', 'expected_harvest_date' => '2026-07-05'],
        ] as $farmerKey => $planData) {
            $plotId = $this->plotsByKey[$farmerKey] ?? null;
            if (! $plotId) {
                continue;
            }

            $planId = $this->deterministicUuid('plan-' . $farmerKey);
            $payload = [
                'id' => $planId,
                'plot_id' => $plotId,
                'rice_id' => $this->riceVarietyIds['หอมมะลิ105'] ?? $this->fallbackRiceVarietyId(),
                'status' => 'ACTIVE',
                'start_date' => $planData['start_date'],
                'expected_harvest_date' => $planData['expected_harvest_date'],
            ];
            $this->touchTimestamps('planting_plans', $payload);
            DB::table('planting_plans')->updateOrInsert(['id' => $planId], $payload);
            $this->plansByKey[$farmerKey] = $planId;
        }

        $events = [
            ['id' => 'soil-a', 'farmer' => 'farmer_a', 'type' => 'SOIL', 'round' => 1, 'date' => '2026-04-27 09:00:00', 'status' => 'DONE', 'issue' => null],
            ['id' => 'water-a', 'farmer' => 'farmer_a', 'type' => 'WATER', 'round' => 1, 'date' => '2026-04-28 10:00:00', 'status' => 'ACTIVE', 'issue' => 'น้ำในแปลงลดเร็วผิดปกติ'],
            ['id' => 'fert-a', 'farmer' => 'farmer_a', 'type' => 'FERT', 'round' => 1, 'date' => '2026-04-29 08:30:00', 'status' => 'ACTIVE', 'issue' => null],
            ['id' => 'soil-b', 'farmer' => 'farmer_b', 'type' => 'SOIL', 'round' => 1, 'date' => '2026-04-26 08:00:00', 'status' => 'DONE', 'issue' => null],
            ['id' => 'disease-b', 'farmer' => 'farmer_b', 'type' => 'DISEASE', 'round' => 2, 'date' => '2026-04-30 14:00:00', 'status' => 'NEEDS_FIX', 'issue' => 'พบอาการโรคไหม้บริเวณขอบแปลง'],
            ['id' => 'soil-c', 'farmer' => 'farmer_c', 'type' => 'SOIL', 'round' => 1, 'date' => '2026-04-24 08:20:00', 'status' => 'DONE', 'issue' => null],
            ['id' => 'pest-c', 'farmer' => 'farmer_c', 'type' => 'PEST', 'round' => 2, 'date' => '2026-04-30 09:40:00', 'status' => 'ACTIVE', 'issue' => 'พบเพลี้ยกระโดดสีน้ำตาลบางจุด'],
            ['id' => 'soil-d', 'farmer' => 'farmer_d', 'type' => 'SOIL', 'round' => 1, 'date' => '2026-04-25 07:45:00', 'status' => 'DONE', 'issue' => null],
            ['id' => 'harvest-d', 'farmer' => 'farmer_d', 'type' => 'HARVEST', 'round' => 3, 'date' => '2026-04-30 13:00:00', 'status' => 'DONE', 'issue' => null],
            ['id' => 'sale-d', 'farmer' => 'farmer_d', 'type' => 'SALE', 'round' => 4, 'date' => '2026-05-01 08:00:00', 'status' => 'DONE', 'issue' => null],
        ];

        foreach ($events as $event) {
            $planId = $this->plansByKey[$event['farmer']] ?? null;
            $userId = $this->usersByKey[$event['farmer']] ?? null;
            $typeId = $this->typeIds[$event['type']] ?? null;

            if (! $planId || ! $userId || ! $typeId) {
                continue;
            }

            $payload = [
                'id' => $this->deterministicUuid('event-' . $event['id']),
                'plan_id' => $planId,
                'type_id' => $typeId,
                'sequence_no' => $event['round'],
                'performed_by_name' => $this->displayNameForFarmerKey($event['farmer']),
                'performed_at' => $event['date'],
                'issue_found' => $event['issue'],
                'status' => $event['status'],
                'reviewed_by' => $event['status'] === 'ACTIVE' ? null : 'admin',
                'reviewed_at' => $event['status'] === 'ACTIVE' ? null : $event['date'],
                'admin_note' => $event['issue'] ? 'ติดตามต่อในรอบถัดไป' : 'ผ่านการตรวจ',
            ];
            $this->touchTimestamps('activity_events', $payload);
            DB::table('activity_events')->updateOrInsert(['id' => $payload['id']], $payload);

            $this->seedDetailRow($event, $payload['id']);
        }
    }

    private function seedDetailRow(array $event, string $activityId): void
    {
        $table = match ($event['type']) {
            'SOIL' => 'soil_prep_details',
            'WATER' => 'water_control_details',
            'FERT' => 'fertilization_details',
            'PEST' => 'pest_control_details',
            'DISEASE' => 'disease_control_details',
            'HARVEST' => 'harvest_details',
            'SALE' => 'sale_details',
            default => null,
        };

        if (! $table || ! Schema::hasTable($table)) {
            return;
        }

        $payload = ['activity_id' => $activityId];

        match ($event['type']) {
            'SOIL' => $payload += [
                $this->existingColumn($table, ['method', 'soil_preparation_method']) => 'ไถดะและเก็บตอซัง',
                'straw_burning' => false,
                'land_leveling' => true,
                'soil_ph' => 6.4,
                'soil_n' => 22,
                'soil_p' => 18,
                'soil_k' => 90,
                'organic_matter' => '2.8',
            ],
            'WATER' => $payload += [
                'method' => 'ปล่อยน้ำเข้าร่องและตรวจระดับทุกเช้า',
                'water_level_cm' => 8,
                'ref_point' => 'ขอบคันนา',
                'note' => 'แนะนำเพิ่มความถี่ตรวจช่วงอากาศร้อน',
            ],
            'FERT' => $payload += [
                'fertilizer_kind' => 'ปุ๋ยเคมี',
                'fertilizer_formula' => '16-20-0',
                'qty_kg_per_rai' => 25,
            ],
            'PEST' => $payload += [
                'pest_type' => 'เพลี้ยกระโดดสีน้ำตาล',
                'chemical_common_name' => 'ไดโนทีฟูแรน',
                'amount_used' => 40,
                'water_liters' => 20,
            ],
            'DISEASE' => $payload += [
                'disease_type' => 'โรคไหม้',
                'chemical_comm_name' => 'ไตรไซคลาโซล',
                'amount_used' => 30,
                'water_liters' => 20,
            ],
            'HARVEST' => $payload += [
                'harvest_start_date' => '2026-04-30',
                'harvest_end_date' => '2026-04-30',
                'total_yield_kg' => 2450,
                'moisture_percent' => 14.5,
            ],
            'SALE' => $payload += [
                'mill_name' => 'โรงสีชุมชนอุบล',
                'product_name' => 'ข้าวเปลือกหอมมะลิ',
                'ticket_no' => 'Q-2026-001',
                'plate_no' => 'กข 1234 อุบล',
                'in_time' => '08:10:00',
                'out_time' => '09:15:00',
                'weight_total_kg' => 2600,
                'weight_net_kg' => 2520,
                'price_per_kg' => 11.8,
                'total_income' => 29736,
            ],
        };

        $payload = array_filter(
            $payload,
            fn ($value, $column) => $column !== null && $this->hasColumn($table, $column),
            ARRAY_FILTER_USE_BOTH
        );

        DB::table($table)->updateOrInsert(['activity_id' => $activityId], $payload);
    }

    private function seedLegacyTrackingTables(): void
    {
        $legacyRows = [
            'prep_tracking_activities' => [
                [
                    'farmer_name' => 'ณัฐชยา หน่อใหม่',
                    'plot_code' => 'FARM-C8A7CE',
                    'round_number' => 1,
                    'activity_name' => 'การเตรียมดิน',
                    'method' => 'ไถดะ',
                    'activity_date' => '2026-04-27',
                    'soil_preparation_method' => 'ไถกลบตอซัง',
                    'tillage_depth' => '15 ซม.',
                    'soil_result' => 'pH 6.4, N 22, P 18, K 90, OM 2.8',
                    'details' => 'เตรียมดินพร้อมหว่านภายใน 3 วัน',
                    'issue_found' => null,
                    'status' => 'passed',
                ],
            ],
            'water_tracking_activities' => [
                [
                    'farmer_name' => 'ณัฐชยา หน่อใหม่',
                    'plot_code' => 'FARM-C8A7CE',
                    'round_number' => 1,
                    'activity_name' => 'การจัดการน้ำ',
                    'method' => 'ควบคุมระดับน้ำ',
                    'activity_date' => '2026-04-28',
                    'water_level' => '8 ซม.',
                    'details' => 'น้ำในแปลงลดเร็วผิดปกติ',
                    'issue_found' => 'น้ำในแปลงลดเร็ว',
                    'status' => 'pending_review',
                ],
            ],
            'fertilizer_tracking_activities' => [
                [
                    'farmer_name' => 'ณัฐชยา หน่อใหม่',
                    'plot_code' => 'FARM-C8A7CE',
                    'round_number' => 1,
                    'activity_name' => 'หว่านปุ๋ย',
                    'method' => 'หว่านทั่วแปลง',
                    'activity_date' => '2026-04-29',
                    'fertilizer_type' => '16-20-0',
                    'amount_per_rai' => '25 กก./ไร่',
                    'details' => 'หว่านช่วงเช้า ลมอ่อน',
                    'issue_found' => null,
                    'status' => 'pending_review',
                ],
            ],
            'pest_tracking_activities' => [
                [
                    'farmer_name' => 'นาวิน วิจุลสะหา',
                    'plot_code' => 'FARM-073387',
                    'round_number' => 2,
                    'activity_name' => 'การจัดการศัตรูพืช',
                    'pest_type' => 'เพลี้ยกระโดดสีน้ำตาล',
                    'chemical_name' => 'ไดโนทีฟูแรน',
                    'mix_ratio' => '40 มล./20 ลิตร',
                    'activity_date' => '2026-04-30',
                    'details' => 'พบการระบาดบางจุด',
                    'issue_found' => 'พบเพลี้ยกระโดด',
                    'status' => 'pending_review',
                ],
            ],
            'disease_tracking_activities' => [
                [
                    'farmer_name' => 'ธวัลรัตน์ เลิศเทอดสกุล',
                    'plot_code' => 'FARM-FBB07C',
                    'round_number' => 2,
                    'activity_name' => 'การจัดการโรคพืช',
                    'disease_type' => 'โรคไหม้',
                    'chemical_name' => 'ไตรไซคลาโซล',
                    'used_amount' => '30 มล.',
                    'mix_ratio' => '20 ลิตร',
                    'activity_date' => '2026-04-30',
                    'details' => 'พบบริเวณขอบแปลง',
                    'issue_found' => 'พบอาการโรคไหม้',
                    'status' => 'needs_fix',
                ],
            ],
            'harvest_tracking_activities' => [
                [
                    'farmer_name' => 'วิระยา หิมะคุณ',
                    'plot_code' => 'FARM-877CB9',
                    'round_number' => 3,
                    'activity_name' => 'การเก็บเกี่ยว',
                    'activity_date' => '2026-04-30',
                    'started_at' => '2026-04-30',
                    'ended_at' => '2026-04-30',
                    'yield_amount_kg' => 2450,
                    'moisture_percent' => 14.5,
                    'details' => 'เก็บเกี่ยวครบทั้งแปลง',
                    'issue_found' => null,
                    'status' => 'passed',
                ],
            ],
            'mill_tracking_activities' => [
                [
                    'farmer_name' => 'วิระยา หิมะคุณ',
                    'plot_code' => 'FARM-877CB9',
                    'round_number' => 4,
                    'activity_name' => 'ขายข้าวเข้าโรงสี',
                    'activity_date' => '2026-05-01',
                    'mill_name' => 'โรงสีชุมชนอุบล',
                    'queue_number' => 'Q-2026-001',
                    'document_number' => 'DOC-2026-001',
                    'product_name' => 'ข้าวเปลือกหอมมะลิ',
                    'vehicle_plate' => 'กข 1234 อุบล',
                    'time_in' => '08:10',
                    'time_out' => '09:15',
                    'pre_mill_weight_kg' => 2600,
                    'post_mill_weight_kg' => 2520,
                    'net_weight_kg' => 2520,
                    'price_per_kg' => 11.8,
                    'total_income' => 29736,
                    'details' => 'ชั่งน้ำหนักและขายเรียบร้อย',
                    'issue_found' => null,
                    'status' => 'passed',
                ],
            ],
        ];

        foreach ($legacyRows as $table => $rows) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($rows as $row) {
                $payload = array_filter(
                    $row,
                    fn ($value, $column) => $this->hasColumn($table, $column),
                    ARRAY_FILTER_USE_BOTH
                );
                $this->touchTimestamps($table, $payload);

                DB::table($table)->updateOrInsert(
                    [
                        'plot_code' => $row['plot_code'],
                        'activity_date' => $row['activity_date'],
                    ],
                    $payload
                );
            }
        }
    }

    private function seedDashboardWorkItems(): void
    {
        if (! Schema::hasTable('dashboard_work_items')) {
            return;
        }

        $rows = [
            [
                'id' => $this->deterministicUuid('dashboard-water-a'),
                'user_id' => $this->usersByKey['farmer_a'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_a'] ?? null,
                'farmer_name' => 'ณัฐชยา หน่อใหม่',
                'plot_code' => 'FARM-C8A7CE',
                'task_title' => 'ติดตามการจัดการน้ำรอบล่าสุด',
                'issue_category' => 'water',
                'status' => 'pending_review',
                'priority' => 'urgent',
                'progress_percent' => 66,
                'due_date' => '2026-05-01',
                'last_activity_at' => '2026-04-30 10:00:00',
                'response_required' => true,
                'latest_note' => 'น้ำในแปลงลดเร็วผิดปกติ ต้องตรวจซ้ำ',
                'meta' => json_encode(['type_code' => 'WATER', 'round' => 1, 'detail_url' => '/admin/tracking/water']),
            ],
            [
                'id' => $this->deterministicUuid('dashboard-disease-b'),
                'user_id' => $this->usersByKey['farmer_b'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_b'] ?? null,
                'farmer_name' => 'ธวัลรัตน์ เลิศเทอดสกุล',
                'plot_code' => 'FARM-FBB07C',
                'task_title' => 'รายงานโรคพืชต้องแก้ไข',
                'issue_category' => 'disease',
                'status' => 'needs_fix',
                'priority' => 'urgent',
                'progress_percent' => 45,
                'due_date' => '2026-05-01',
                'last_activity_at' => '2026-04-30 14:00:00',
                'response_required' => true,
                'latest_note' => 'พบอาการโรคไหม้บริเวณขอบแปลง',
                'meta' => json_encode(['type_code' => 'DISEASE', 'round' => 2, 'detail_url' => '/admin/tracking/disease']),
            ],
            [
                'id' => $this->deterministicUuid('dashboard-sale-d'),
                'user_id' => $this->usersByKey['farmer_d'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_d'] ?? null,
                'farmer_name' => 'วิระยา หิมะคุณ',
                'plot_code' => 'FARM-877CB9',
                'task_title' => 'สรุปผลขายข้าวเข้าโรงสี',
                'issue_category' => 'mill',
                'status' => 'passed',
                'priority' => 'medium',
                'progress_percent' => 100,
                'due_date' => '2026-05-01',
                'last_activity_at' => '2026-05-01 09:15:00',
                'response_required' => false,
                'latest_note' => 'ขายข้าวเรียบร้อย รายได้ 29,736 บาท',
                'meta' => json_encode(['type_code' => 'SALE', 'round' => 4, 'detail_url' => '/admin/tracking/mill']),
            ],
            // demo items วันที่ 9 พ.ค. 2026 (วันนี้) — planned_date ใน meta เพราะตารางไม่มีคอลัมน์ due_date
            [
                'id' => $this->deterministicUuid('dashboard-water-a-may10'),
                'user_id' => $this->usersByKey['farmer_a'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_a'] ?? null,
                'task_title' => 'นัดติดตามการจัดการน้ำ รอบ 2',
                'issue_category' => 'water',
                'status' => 'pending_review',
                'priority' => 'urgent',
                'response_required' => true,
                'latest_note' => 'ต้องตรวจความชื้นดินและระดับน้ำหลังฝนตก',
                'meta' => json_encode([
                    'planned_date' => '2026-05-10',
                    'type_code' => 'WATER',
                    'round' => 2,
                    'detail_url' => '/admin/tracking/water',
                    'farmer_name' => 'ณัฐชยา หน่อใหม่',
                    'plot_code' => 'FARM-C8A7CE',
                ]),
            ],
            [
                'id' => $this->deterministicUuid('dashboard-disease-b-may10'),
                'user_id' => $this->usersByKey['farmer_b'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_b'] ?? null,
                'task_title' => 'นัดตรวจแปลงโรคพืชซ้ำ',
                'issue_category' => 'disease',
                'status' => 'needs_fix',
                'priority' => 'urgent',
                'response_required' => true,
                'latest_note' => 'พบโรคไหม้บริเวณขอบแปลง ต้องตรวจซ้ำและพ่นยาเพิ่ม',
                'meta' => json_encode([
                    'planned_date' => '2026-05-10',
                    'type_code' => 'DISEASE',
                    'round' => 3,
                    'detail_url' => '/admin/tracking/disease',
                    'farmer_name' => 'ธวัลรัตน์ เลิศเทอดสกุล',
                    'plot_code' => 'FARM-FBB07C',
                ]),
            ],
            [
                'id' => $this->deterministicUuid('dashboard-fert-c-may10'),
                'user_id' => $this->usersByKey['farmer_c'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_c'] ?? null,
                'task_title' => 'นัดติดตามการหว่านปุ๋ย รอบ 2',
                'issue_category' => 'fertilizer',
                'status' => 'pending_review',
                'priority' => 'medium',
                'response_required' => true,
                'latest_note' => 'ตรวจสอบการกระจายปุ๋ยและบันทึกผลหลังหว่าน',
                'meta' => json_encode([
                    'planned_date' => '2026-05-10',
                    'type_code' => 'FERT',
                    'round' => 2,
                    'detail_url' => '/admin/tracking/fertilizer',
                    'farmer_name' => 'นาวิน วิจุลสะหา',
                    'plot_code' => 'FARM-073387',
                ]),
            ],
            [
                'id' => $this->deterministicUuid('dashboard-doc-d-may10'),
                'user_id' => $this->usersByKey['farmer_d'] ?? null,
                'plot_id' => $this->plotsByKey['farmer_d'] ?? null,
                'task_title' => 'ตรวจสอบเอกสาร SRP รอบ 2',
                'issue_category' => 'document',
                'status' => 'pending_review',
                'priority' => 'normal',
                'response_required' => true,
                'latest_note' => 'รอเอกสารการรับรองเพิ่มเติมจากเกษตรกร',
                'meta' => json_encode([
                    'planned_date' => '2026-05-10',
                    'detail_url' => '/admin/srp/farmers',
                    'farmer_name' => 'วิระยา หิมะคุณ',
                    'plot_code' => 'FARM-877CB9',
                ]),
            ],
            // demo items สำหรับ "รายงานปัญหาใหม่"
            [
                'id' => $this->deterministicUuid('report-upload-img'),
                'user_id' => $this->usersByKey['farmer_a'] ?? null,
                'task_title' => 'รายงานปัญหาอัปโหลดรูปภาพไม่ได้',
                'issue_category' => 'รายงานปัญหาการใช้งานระบบ',
                'status' => 'pending_review',
                'priority' => 'urgent',
                'response_required' => true,
                'latest_note' => 'ผู้ใช้แจ้งว่ากดส่งรูปแล้วหน้าแอปค้างที่ 90% ทดสอบซ้ำหลายครั้ง',
                'meta' => json_encode([
                    'report_type' => 'system',
                    'farmer_name' => 'ณัฐชยา หน่อใหม่',
                    'contact_phone' => '0972686348',
                    'detail_url' => '/admin/report/system',
                ]),
            ],
            [
                'id' => $this->deterministicUuid('report-login-fail'),
                'user_id' => $this->usersByKey['farmer_b'] ?? null,
                'task_title' => 'รายงานปัญหาเข้าสู่ระบบไม่ได้',
                'issue_category' => 'รายงานปัญหาการใช้งานระบบ',
                'status' => 'in_progress',
                'priority' => 'urgent',
                'response_required' => true,
                'latest_note' => 'กดรีเซ็ตรหัสผ่านแล้วแต่ไม่ได้รับอีเมล ติดต่อกลับแล้ว กำลังตรวจสอบการตั้งค่าอีเมล',
                'meta' => json_encode([
                    'report_type' => 'system',
                    'farmer_name' => 'ธวัลรัตน์ เลิศเทอดสกุล',
                    'contact_email' => 'thawanrat04.demo@example.com',
                    'detail_url' => '/admin/report/system',
                ]),
            ],
            [
                'id' => $this->deterministicUuid('report-data-wrong'),
                'user_id' => $this->usersByKey['farmer_c'] ?? null,
                'task_title' => 'รายงานข้อมูลแปลงแสดงผิดพลาด',
                'issue_category' => 'รายงานปัญหาการใช้งานระบบ',
                'status' => 'needs_fix',
                'priority' => 'medium',
                'response_required' => true,
                'latest_note' => 'ข้อมูลพื้นที่แปลงแสดงตัวเลขไม่ตรงกับความเป็นจริง ต้องแก้ไขฐานข้อมูล',
                'meta' => json_encode([
                    'report_type' => 'system',
                    'farmer_name' => 'นาวิน วิจุลสะหา',
                    'contact_phone' => '0617737384',
                    'detail_url' => '/admin/report/system',
                ]),
            ],
        ];

        foreach ($rows as $row) {
            $payload = array_filter(
                $row,
                fn ($value, $column) => $this->hasColumn('dashboard_work_items', $column),
                ARRAY_FILTER_USE_BOTH
            );
            $this->touchTimestamps('dashboard_work_items', $payload);
            DB::table('dashboard_work_items')->updateOrInsert(['id' => $row['id']], $payload);
        }
    }

    private function seedSupportTickets(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        $rows = [
            [
                'id' => $this->deterministicUuid('ticket-1'),
                'user_id' => $this->usersByKey['farmer_a'] ?? null,
                'assigned_to' => $this->usersByKey['admin_central'] ?? null,
                'subject' => 'เข้าสู่ระบบไม่ได้',
                'message' => 'ผู้ใช้กดรีเซ็ตรหัสผ่านแล้วแต่ยังไม่ได้อีเมล',
                'contact_email' => 'natchaya24.demo@example.com',
                'contact_phone' => '0972686348',
                'status' => 'OPEN',
                'admin_note' => 'รอตรวจสอบการตั้งค่าอีเมล',
                'resolved_at' => null,
            ],
            [
                'id' => $this->deterministicUuid('ticket-2'),
                'user_id' => $this->usersByKey['farmer_b'] ?? null,
                'assigned_to' => $this->usersByKey['admin_north'] ?? null,
                'subject' => 'รูปภาพอัปโหลดไม่ขึ้น',
                'message' => 'ส่งรูปแปลงแล้วหน้าแอพค้างที่ 90%',
                'contact_email' => 'thawanrat04.demo@example.com',
                'contact_phone' => '0617737384',
                'status' => 'IN_PROGRESS',
                'admin_note' => 'ทดสอบกับไฟล์ขนาดใหญ่เพิ่ม',
                'resolved_at' => null,
            ],
        ];

        foreach ($rows as $row) {
            $payload = array_filter(
                $row,
                fn ($value, $column) => $this->hasColumn('support_tickets', $column),
                ARRAY_FILTER_USE_BOTH
            );
            $this->touchTimestamps('support_tickets', $payload);
            DB::table('support_tickets')->updateOrInsert(['id' => $row['id']], $payload);
        }
    }

    private function upsertUser(array $attributes): string
    {
        $existing = DB::table('users')
            ->where('username', $attributes['username'])
            ->first();

        $userId = $existing->id ?? $this->deterministicUuid('user-' . $attributes['username']);

        $payload = [
            'id' => $userId,
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'username' => $attributes['username'],
            'role' => $attributes['role'],
            'status' => $attributes['status'] ?? 'ใช้งาน',
            'citizen_id' => $attributes['citizen_id'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'birth_date' => $attributes['birth_date'] ?? null,
            'address_line' => $attributes['address_line'] ?? null,
            'province' => $attributes['province'] ?? null,
            'district' => $attributes['district'] ?? null,
            'subdistrict' => $attributes['subdistrict'] ?? null,
            'postcode' => $attributes['postcode'] ?? null,
            'farmer_code' => $attributes['farmer_code'] ?? null,
            'registered_at' => $attributes['registered_at'] ?? null,
            'registered_province' => $attributes['registered_province'] ?? null,
            'farm_province' => $attributes['farm_province'] ?? null,
            'farm_area_rai' => $attributes['farm_area_rai'] ?? null,
            'farm_area_square_wa' => $attributes['farm_area_square_wa'] ?? null,
            'crop_type' => $attributes['crop_type'] ?? null,
            'member_registered_at' => '2026-04-01',
            'password_hash' => $attributes['password_hash'],
        ];

        $payload = array_filter(
            $payload,
            fn ($value, $column) => $this->hasColumn('users', $column),
            ARRAY_FILTER_USE_BOTH
        );

        if ($this->hasColumn('users', 'farm_area_ngan') && array_key_exists('farm_area_ngan', $attributes)) {
            $payload['farm_area_ngan'] = $attributes['farm_area_ngan'];
        }

        if ($this->hasColumn('users', 'password')) {
            $payload['password'] = $attributes['password_hash'];
        }

        if ($this->hasColumn('users', 'email_verified_at')) {
            $payload['email_verified_at'] = now();
        }

        $this->touchTimestamps('users', $payload);

        DB::table('users')->updateOrInsert(['username' => $attributes['username']], $payload);

        return $userId;
    }

    private function displayNameForFarmerKey(string $key): string
    {
        return match ($key) {
            'farmer_a' => 'ณัฐชยา หน่อใหม่',
            'farmer_b' => 'ธวัลรัตน์ เลิศเทอดสกุล',
            'farmer_c' => 'นาวิน วิจุลสะหา',
            'farmer_d' => 'วิระยา หิมะคุณ',
            default => 'เกษตรกรสาธิต',
        };
    }

    private function existingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($this->hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function resolveProvinceIdByName(?string $provinceName): string|int|null
    {
        $normalized = trim((string) $provinceName);

        if ($normalized === '' || ! Schema::hasTable('provinces')) {
            return null;
        }

        return DB::table('provinces')
            ->whereRaw('LOWER(name_th) = ?', [mb_strtolower($normalized)])
            ->value('id');
    }

    private function resolveDistrictIdByName(?string $districtName, string|int|null $provinceId = null): string|int|null
    {
        $normalized = trim((string) $districtName);

        if ($normalized === '' || ! Schema::hasTable('districts')) {
            return null;
        }

        $query = DB::table('districts')
            ->whereRaw('LOWER(name_th) = ?', [mb_strtolower($normalized)]);

        if ($provinceId !== null && $this->hasColumn('districts', 'province_id')) {
            $query->where('province_id', $provinceId);
        }

        return $query->value('id');
    }

    private function touchTimestamps(string $table, array &$payload): void
    {
        if ($this->hasColumn($table, 'created_at') && ! array_key_exists('created_at', $payload)) {
            $payload['created_at'] = now();
        }

        if ($this->hasColumn($table, 'updated_at')) {
            $payload['updated_at'] = now();
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }

    private function deterministicUuid(string $seed): string
    {
        $hash = md5($seed);

        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12),
        );
    }

    private function nextIdentifierFor(string $table, string $seed): string|int
    {
        $type = $this->columnType($table, 'id');

        if ($type !== null && str_contains($type, 'int')) {
            return ((int) DB::table($table)->count()) + 1;
        }

        return $this->deterministicUuid($seed);
    }

    private function fallbackRiceVarietyId(): string|int|null
    {
        if (! Schema::hasTable('rice_varieties')) {
            return null;
        }

        return DB::table('rice_varieties')->value('id');
    }

    private function columnType(string $table, string $column): ?string
    {
        try {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                return null;
            }

            return strtolower((string) Schema::getColumnType($table, $column));
        } catch (\Throwable) {
            return null;
        }
    }
}
