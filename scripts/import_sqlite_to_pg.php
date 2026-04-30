<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sqlitePath = __DIR__ . '/../database/database.sqlite';

if (!file_exists($sqlitePath)) {
    fwrite(STDERR, "SQLite database not found.\n");
    exit(1);
}

$sqlite = new PDO('sqlite:' . $sqlitePath);
$sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pg = Illuminate\Support\Facades\DB::connection('pgsql');

$tables = [
    'dashboard_work_items' => [
        'id',
        'farmer_name',
        'plot_code',
        'task_title',
        'issue_category',
        'status',
        'priority',
        'progress_percent',
        'due_date',
        'last_activity_at',
        'responded_at',
        'response_required',
        'latest_note',
        'meta',
        'created_at',
        'updated_at',
    ],
    'app_settings' => [
        'id',
        'theme',
        'font_family',
        'font_size',
        'data_density',
        'list_display',
        'language',
        'timezone',
        'date_format',
        'area_unit',
        'email_notifications',
        'system_notifications',
        'weekly_summary',
        'two_factor_enabled',
        'auto_logout_minutes',
        'backup_enabled',
        'backup_frequency',
        'data_retention',
        'created_at',
        'updated_at',
    ],
];

foreach ($tables as $table => $columns) {
    $rows = $sqlite->query('SELECT ' . implode(',', $columns) . ' FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo $table . ": no rows\n";
        continue;
    }

    foreach ($rows as &$row) {
        if (array_key_exists('meta', $row) && $row['meta'] === '') {
            $row['meta'] = null;
        }
        if (array_key_exists('response_required', $row)) {
            $row['response_required'] = (bool) $row['response_required'];
        }
    }
    unset($row);

    $pg->table($table)->upsert($rows, ['id']);

    echo $table . ': imported ' . count($rows) . " rows\n";
}
