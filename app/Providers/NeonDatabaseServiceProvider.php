<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connectors\PostgresConnector;
use PDO;

class NeonDatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $endpoint = env('NEON_ENDPOINT');
        if (!$endpoint) return;

        $this->app->bind('db.connector.pgsql', function () use ($endpoint) {
            return new class($endpoint) extends PostgresConnector {
                public function __construct(private string $neonEndpoint) {}

                public function connect(array $config): PDO
                {
                    // สร้าง DSN พร้อมแนบ options=endpoint=xxx
                    $dsn = $this->getDsn($config);
                    if (!str_contains($dsn, 'options=')) {
                        $dsn .= ";options='endpoint={$this->neonEndpoint}'";
                    }

                    // เรียก parent createConnection ด้วย signature ที่ถูกต้อง
                    return $this->createConnection($dsn, $config, $config['options'] ?? []);
                }
            };
        });
    }
}
