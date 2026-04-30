<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$exists = Illuminate\Support\Facades\Schema::hasTable('dashboard_work_items');

$payload = [
    'dashboard_work_items_exists' => $exists,
    'columns' => [],
    'column_details' => [],
    'dashboard_work_items_count' => null,
    'latest_items' => [],
];

if ($exists) {
    $payload['columns'] = Illuminate\Support\Facades\Schema::getColumnListing('dashboard_work_items');
    $payload['column_details'] = Illuminate\Support\Facades\DB::table('information_schema.columns')
        ->where('table_schema', 'public')
        ->where('table_name', 'dashboard_work_items')
        ->orderBy('ordinal_position')
        ->get(['column_name', 'data_type', 'is_nullable', 'column_default'])
        ->toArray();
    $payload['dashboard_work_items_count'] = Illuminate\Support\Facades\DB::table('dashboard_work_items')->count();
    $query = Illuminate\Support\Facades\DB::table('dashboard_work_items')
        ->orderByDesc('updated_at')
        ->limit(10);

    $selectableColumns = array_values(array_intersect(
        ['task_title', 'farmer_name', 'plot_code', 'priority', 'status', 'latest_note', 'updated_at'],
        $payload['columns']
    ));

    $payload['latest_items'] = $query->get($selectableColumns)->toArray();
}

echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
