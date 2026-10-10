<?php

namespace App\Console\Commands;

use App\Domains\Evidence\Services\RiceEvidenceImportService;
use Illuminate\Console\Command;
use JsonException;
use RuntimeException;
use Throwable;

class ImportRiceEvidencePack extends Command
{
    protected $signature = 'evidence:import-rice-pack
        {--seed-json= : Path to rice_evidence_seed.json}
        {--market-csv= : Path to market_observations.csv}
        {--supplier-leads-csv= : Path to supplier_leads.csv}
        {--dry-run : Validate, reconcile, and report without writing evidence}';

    protected $description = 'Import the starter Rice evidence pack without creating ERP transactions';

    public function handle(RiceEvidenceImportService $importer): int
    {
        try {
            $seedPath = $this->requiredFileOption('seed-json');
            $marketCsvPath = $this->requiredFileOption('market-csv');
            $supplierCsvPath = $this->requiredFileOption('supplier-leads-csv');

            $seed = $this->readJson($seedPath);
            $marketRows = [
                ...$this->rowsWithInputSource(
                    $this->arrayValue($seed, 'market_observations'),
                    basename($seedPath)
                ),
                ...$this->rowsWithInputSource(
                    $this->readCsv($marketCsvPath),
                    basename($marketCsvPath)
                ),
            ];
            $supplierLeadRows = [
                ...$this->rowsWithInputSource(
                    $this->arrayValue($seed, 'supplier_leads'),
                    basename($seedPath)
                ),
                ...$this->rowsWithInputSource(
                    $this->readCsv($supplierCsvPath),
                    basename($supplierCsvPath)
                ),
            ];

            $result = $importer->import(
                $marketRows,
                $supplierLeadRows,
                (bool) $this->option('dry-run')
            );

            $this->displaySummary($result, $seed['consumer_survey_data']['status'] ?? null);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function requiredFileOption(string $name): string
    {
        $path = trim((string) $this->option($name));
        if ($path === '') {
            throw new RuntimeException("The --{$name} option is required.");
        }

        if (! is_file($path)) {
            throw new RuntimeException("The supplied {$name} file does not exist: {$path}");
        }

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJson(string $path): array
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read seed JSON: {$path}");
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid seed JSON: {$exception->getMessage()}", previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('The seed JSON root must be an object.');
        }

        return $decoded;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to read CSV: {$path}");
        }

        try {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if (! is_array($header)) {
                throw new RuntimeException("CSV has no header row: {$path}");
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
            $rows = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $rowNumber++;
                if ($row === [null]) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    throw new RuntimeException("CSV row {$rowNumber} has a different column count: {$path}");
                }

                $record = array_combine($header, $row);
                if ($record === false) {
                    throw new RuntimeException("Unable to map CSV row {$rowNumber}: {$path}");
                }

                $rows[] = $record;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    private function arrayValue(array $data, string $key): array
    {
        $value = $data[$key] ?? null;
        if (! is_array($value)) {
            throw new RuntimeException("Seed JSON field [{$key}] must be an array.");
        }

        return $value;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function rowsWithInputSource(array $rows, string $source): array
    {
        return array_map(function (array $row) use ($source): array {
            $row['_input_source'] = $source;

            return $row;
        }, $rows);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function displaySummary(array $result, mixed $consumerSurveyStatus): void
    {
        $market = $result['market'];
        $leads = $result['supplier_leads'];
        $this->info($result['dry_run']
            ? 'Dry run complete. No evidence records were written.'
            : 'Rice evidence import complete.');

        $this->table(['Market evidence', 'Count'], [
            ['Rows read', $market['input_rows']],
            ['Unique source records', $market['unique_records']],
            ['Imported', count($market['imported'])],
            ['Would import (dry run)', count($market['would_import'])],
            ['Already imported', count($market['existing'])],
            ['Duplicate input rows skipped', count($market['duplicate_rows'])],
            ['Invalid input rows skipped', count($market['invalid_rows'])],
            ['Awaiting product reconciliation', count($market['unresolved'])],
        ]);

        if ($market['unresolved'] !== []) {
            $this->warn($result['dry_run']
                ? 'Market evidence awaiting exact product reconciliation (would remain unlinked):'
                : 'Market evidence awaiting exact product reconciliation (stored unlinked):');
            $this->table(['Raw product', 'Canonical candidate', 'Existing ERP candidate', 'Reason'], array_map(
                fn (array $row) => [
                    $row['product_description_raw'],
                    $row['canonical_candidate'] ?? 'None supplied',
                    $this->candidateLabels($row['candidate_inventory_objects']),
                    $row['reconciliation_reason'],
                ],
                $market['unresolved']
            ));
        }

        $this->table(['Supplier lead review', 'Count'], [
            ['Rows read', $leads['input_rows']],
            ['Unique leads', $leads['unique_records']],
            ['Duplicate input rows skipped', count($leads['duplicate_rows'])],
            ['Left unpersisted for review', count($leads['unpersisted'])],
        ]);

        if ($leads['unpersisted'] !== []) {
            $this->warn('Supplier leads were not added to the supplier master or commercial observations:');
            $this->table(['Supplier', 'Commercial price (raw)', 'Disposition'], array_map(
                fn (array $lead) => [
                    $lead['supplier_name'],
                    $lead['commercial_price_raw'] ?? 'UNKNOWN',
                    'Review required',
                ],
                $leads['unpersisted']
            ));
        }

        if ($consumerSurveyStatus !== null) {
            $this->line("Consumer survey data status: {$consumerSurveyStatus}; no respondent, response, or simulated purchase records were created.");
        }
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     */
    private function candidateLabels(array $candidates): string
    {
        if ($candidates === []) {
            return 'None';
        }

        return implode('; ', array_map(
            fn (array $candidate) => trim((string) ($candidate['code'] ?? ''))
                . ' / ' . trim((string) ($candidate['name'] ?? '')),
            $candidates
        ));
    }
}
