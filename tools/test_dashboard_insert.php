<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$match = [
    'task_title' => 'แจ้งเตือนทดสอบระบบ',
];

if (Illuminate\Support\Facades\Schema::hasColumn('dashboard_work_items', 'user_id')) {
    $match['user_id'] = 'de4d1e15-cdd9-42e7-bd03-98847455a244';
}

$payload = [
    'task_title' => 'แจ้งเตือนทดสอบระบบ',
    'issue_category' => 'ทดสอบ',
    'status' => 'pending_review',
    'priority' => 'urgent',
    'response_required' => true,
    'latest_note' => 'ทดสอบการสร้างแจ้งเตือนจากสคริปต์',
    'meta' => json_encode([
        'source' => 'manual_test',
        'detail_url' => '/admin/farmer-users/de4d1e15-cdd9-42e7-bd03-98847455a244',
    ], JSON_UNESCAPED_UNICODE),
    'updated_at' => now(),
];

if (Illuminate\Support\Facades\Schema::hasColumn('dashboard_work_items', 'resolved_at')) {
    $payload['resolved_at'] = null;
}

$query = Illuminate\Support\Facades\DB::table('dashboard_work_items');

foreach ($match as $column => $value) {
    $query->where($column, $value);
}

if ($query->exists()) {
    $query->update($payload);
    echo "updated\n";
    exit(0);
}

Illuminate\Support\Facades\DB::table('dashboard_work_items')->insert(array_merge($match, $payload, [
    'created_at' => now(),
]));

echo "inserted\n";
