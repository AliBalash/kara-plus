<?php

namespace App\Console\Commands;

use App\Services\Audit\ElasticsearchAuditExporter;
use Illuminate\Console\Command;

class AuditElasticsearchBootstrapCommand extends Command
{
    protected $signature = 'audit:es-bootstrap';

    protected $description = 'Create/update audit ILM policy and index template in Elasticsearch';

    public function handle(ElasticsearchAuditExporter $exporter): int
    {
        try {
            $exporter->ensureIlmAndTemplate();
            $this->info('Audit Elasticsearch ILM/template ensured successfully.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Failed to bootstrap Elasticsearch audit settings: ' . $exception->getMessage());

            return self::FAILURE;
        }
    }
}
