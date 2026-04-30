<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'support_tickets',
    'tracking_advices',
    'dashboard_work_items',
];

$payload = [];

foreach ($tables as $table) {
    $exists = Schema::hasTable($table);

    $payload[$table] = [
        'exists' => $exists,
        'count' => null,
        'latest' => [],
    ];

    if (! $exists) {
        continue;
    }

    $payload[$table]['count'] = DB::table($table)->count();

    $columns = Schema::getColumnListing($table);

    $select = array_values(array_intersect([
        'id',
        'user_id',
        'subject',
        'message',
        'status',
        'page_key',
        'page_title',
        'task_title',
        'issue_category',
        'latest_note',
        'created_at',
        'updated_at',
    ], $columns));

    $orderColumn = in_array('created_at', $columns, true)
        ? 'created_at'
        : (in_array('updated_at', $columns, true) ? 'updated_at' : $columns[0]);

    $payload[$table]['latest'] = DB::table($table)
        ->orderByDesc($orderColumn)
        ->limit(5)
        ->get($select)
        ->toArray();
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
