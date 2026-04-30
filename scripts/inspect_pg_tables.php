<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$queries = [
    'activity_types' => 'select id, code, name_th from activity_types order by id',
    'activity_events' => 'select id, plan_id, type_id, sequence_no, performed_by_name, performed_at, issue_found, status from activity_events order by performed_at desc nulls last limit 10',
    'soil_prep_details' => 'select * from soil_prep_details limit 5',
    'water_control_details' => 'select * from water_control_details limit 5',
    'fertilization_details' => 'select * from fertilization_details limit 5',
    'pest_control_details' => 'select * from pest_control_details limit 5',
    'disease_control_details' => 'select * from disease_control_details limit 5',
    'harvest_details' => 'select * from harvest_details limit 5',
    'sale_details' => 'select * from sale_details limit 5',
];

foreach ($queries as $name => $sql) {
    echo "-- {$name} --" . PHP_EOL;

    try {
        $rows = DB::select($sql);

        foreach ($rows as $row) {
            echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }
    } catch (Throwable $exception) {
        echo 'ERR: ' . $exception->getMessage() . PHP_EOL;
    }

    echo PHP_EOL;
}
