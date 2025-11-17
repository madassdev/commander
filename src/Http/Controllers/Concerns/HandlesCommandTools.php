<?php

namespace Madassdev\Commander\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

trait HandlesCommandTools
{
    protected function validateAction(Request $request, array $rules): array
    {
        $validated = $request->validate(
            array_merge($rules, $this->confirmationRules()),
            ['confirm.accepted' => 'Please confirm that you understand this action.']
        );

        $this->assertMasterPassword($validated['master_password'] ?? null);

        unset($validated['master_password'], $validated['confirm']);

        return $validated;
    }

    protected function confirmationRules(): array
    {
        return [
            'master_password' => ['required', 'string', 'min:8'],
            'confirm' => ['accepted'],
        ];
    }

    protected function assertMasterPassword(?string $input): void
    {
        $expected = config('command-center.master_password');

        if (! $expected) {
            throw ValidationException::withMessages([
                'master_password' => 'Master password is not configured. Please set COMMAND_MASTER_PASSWORD.',
            ]);
        }

        if (! $input || ! hash_equals($expected, $input)) {
            throw ValidationException::withMessages([
                'master_password' => 'Invalid master password.',
            ]);
        }
    }

    protected function readEnvEntries(): array
    {
        $path = base_path('.env');

        if (! File::exists($path)) {
            return [];
        }

        $lines = File::lines($path);
        $entries = [];

        if ($lines !== null) {
            foreach ($lines as $line) {
                $parsed = $this->parseEnvLine($line);
                if ($parsed) {
                    $entries[] = $parsed;
                }
            }
        } else {
            foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
                $parsed = $this->parseEnvLine($line);
                if ($parsed) {
                    $entries[] = $parsed;
                }
            }
        }

        return $entries;
    }

    protected function parseEnvLine(string $line): ?array
    {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            return null;
        }

        if (! str_contains($line, '=')) {
            return null;
        }

        [$key, $value] = explode('=', $line, 2);

        $value = trim($value);
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = trim($value, '"');
        }

        return [
            'key' => $key,
            'value' => $value,
        ];
    }

    protected function writeEnvValue(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! File::exists($path)) {
            throw new \RuntimeException('.env file not found.');
        }

        if (! is_writable($path)) {
            throw new \RuntimeException('.env file is not writable.');
        }

        $content = File::get($path);
        $escapedValue = $this->formatEnvValue($value);
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "{$key}={$escapedValue}", $content);
        } else {
            $content .= PHP_EOL . "{$key}={$escapedValue}";
        }

        File::put($path, $content);
    }

    protected function formatEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|"/', $value)) {
            $escaped = str_replace('"', '\"', $value);
            return "\"{$escaped}\"";
        }

        return $value;
    }

    protected function availableFileRoots(): array
    {
        return array_filter(
            config('command-center.file_roots', []),
            fn ($root) => isset($root['path']) && (File::exists($root['path']))
        );
    }

    protected function fileIndex(int $limit = 200): array
    {
        $roots = $this->availableFileRoots();
        $index = [];

        foreach ($roots as $root) {
            if (($root['type'] ?? null) === 'file') {
                $index[] = [
                    'group' => $root['label'],
                    'files' => [[
                        'label' => $root['label'],
                        'path' => $root['path'],
                        'relative' => $root['key'],
                    ]],
                ];
                continue;
            }

            $files = [];
            foreach (File::allFiles($root['path']) as $file) {
                if (count($files) >= $limit) {
                    break;
                }

                $files[] = [
                    'label' => str_replace($root['path'] . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                    'path' => $file->getPathname(),
                    'relative' => $root['key'] . '/' . str_replace($root['path'] . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                ];
            }

            $index[] = [
                'group' => $root['label'],
                'files' => $files,
            ];
        }

        return $index;
    }

    protected function resolveEditablePath(string $relativePath): string
    {
        $relativePath = str_replace(['..', './', '\\'], ['', '', DIRECTORY_SEPARATOR], $relativePath);
        $relativePath = trim($relativePath, '/');

        foreach ($this->availableFileRoots() as $root) {
            $rootKey = $root['key'];

            if (($root['type'] ?? null) === 'file' && ($relativePath === $rootKey || $relativePath === basename($root['path']))) {
                return $root['path'];
            }

            if (str_starts_with($relativePath, $rootKey)) {
                $suffix = ltrim(Str::of($relativePath)->after($rootKey)->value(), '/');
                $fullPath = $root['path'] . ($suffix ? DIRECTORY_SEPARATOR . $suffix : '');

                if (File::exists($fullPath) && File::isFile($fullPath)) {
                    return $fullPath;
                }
            }
        }

        throw ValidationException::withMessages([
            'path' => 'File not allowed or does not exist.',
        ]);
    }

    protected function readFileContent(string $path): string
    {
        if (! File::exists($path) || ! File::isFile($path)) {
            throw new \RuntimeException('File not found.');
        }

        return File::get($path);
    }

    protected function writeFileContent(string $path, string $content): void
    {
        if (! File::exists($path) || ! File::isFile($path)) {
            throw new \RuntimeException('File not found.');
        }

        if (! is_writable($path)) {
            throw new \RuntimeException('File is not writable.');
        }

        File::put($path, $content);
    }

    protected function runGit(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), base_path());
        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return trim($process->getOutput());
    }

    protected function gitInfo(): array
    {
        try {
            $branch = $this->runGit(['rev-parse', '--abbrev-ref', 'HEAD']);
            $head = $this->runGit(['rev-parse', 'HEAD']);
            $status = $this->runGit(['status', '-sb']);
            $latest = $this->runGit(['log', '-1', '--pretty=%h %s (%cr)']);
        } catch (\Throwable $exception) {
            Log::warning('Failed to gather git info.', ['exception' => $exception->getMessage()]);

            return [
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'branch' => $branch,
            'head' => $head,
            'status' => $status,
            'latest' => $latest,
        ];
    }

    protected function readLogTail(string $path, int $lines): string
    {
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();
        $startLine = max(0, $lastLine - $lines + 1);

        $output = [];
        for ($line = $startLine; $line <= $lastLine; $line++) {
            $file->seek($line);
            $output[] = rtrim((string) $file->current(), "\r\n");
        }

        return implode(PHP_EOL, $output);
    }

    protected function backupDirectory(): string
    {
        $directory = config('command-center.backup_path', storage_path('command-backups'));

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return $directory;
    }

    protected function listBackups(): array
    {
        $directory = $this->backupDirectory();

        if (! File::exists($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(fn ($file) => [
                'filename' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => $file->getMTime(),
            ])
            ->values()
            ->all();
    }

    protected function createDatabaseBackup(?string $label = null): array
    {
        $config = $this->databaseConfig();
        $directory = $this->backupDirectory();
        $timestamp = now()->format('Ymd_His');
        $prefix = $label ? Str::of($label)->slug('_')->limit(40)->value() . '_' : '';
        $filename = "{$prefix}{$timestamp}.sql";
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $dumped = false;

        try {
            $this->dumpDatabaseViaCli($config, $path);
            $dumped = true;
        } catch (\Throwable $exception) {
            Log::warning('mysqldump failed, falling back to query export.', [
                'message' => $exception->getMessage(),
            ]);
        }

        if (! $dumped) {
            $this->dumpDatabaseViaQuery($config['database'], $path);
        }

        return [
            'filename' => $filename,
            'path' => $path,
        ];
    }
    protected function dumpDatabaseViaCli(array $config, string $path): void
    {
        $binary = config('command-center.mysql_dump_binary', 'mysqldump');
        $command = [
            $binary,
            "--user={$config['username']}",
            "--host={$config['host']}",
            "--port={$config['port']}",
            '--single-transaction',
            '--quick',
            $config['database'],
        ];

        $process = new Process($command, base_path());
        $process->setTimeout(180);

        if (! empty($config['password'])) {
            $process->setEnv(['MYSQL_PWD' => $config['password']]);
        }

        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        File::put($path, $process->getOutput());
    }

    protected function dumpDatabaseViaQuery(string $database, string $path): void
    {
        $connection = DB::connection();
        $tables = $connection->select('SHOW TABLES');

        if (! $tables) {
            File::put($path, '');
            return;
        }

        $tableKey = 'Tables_in_' . $database;
        $pdo = $connection->getPdo();
        $schemaBuilder = $connection->getSchemaBuilder();

        $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $row) {
            $table = $row->$tableKey ?? collect((array) $row)->first();
            if (! $table) {
                continue;
            }

            $create = $connection->selectOne("SHOW CREATE TABLE `{$table}`");
            $createStatement = $create->{'Create Table'} ?? collect((array) $create)->last() ?? '';
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n{$createStatement};\n\n";

            $columns = $schemaBuilder->getColumnListing($table);
            $rows = $connection->table($table)->get();

            if (! $rows->count()) {
                continue;
            }

            $chunks = array_chunk($rows->toArray(), 200);

            foreach ($chunks as $chunk) {
                $values = [];
                foreach ($chunk as $record) {
                    $recordArray = (array) $record;
                    $valueList = array_map(
                        fn ($column) => $this->quoteValue($recordArray[$column] ?? null, $pdo),
                        $columns
                    );
                    $values[] = '(' . implode(', ', $valueList) . ')';
                }
                $sql .= sprintf(
                    "INSERT INTO `%s` (`%s`) VALUES %s;\n",
                    $table,
                    implode('`,`', $columns),
                    implode(",\n", $values)
                );
            }

            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        File::put($path, $sql);
    }

    protected function quoteValue($value, \PDO $pdo): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $pdo->quote($value);
    }

    protected function restoreDatabaseBackup(string $filename): void
    {
        $path = $this->backupFilePath($filename);
        $sql = File::get($path);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::unprepared($sql);
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function deleteDatabaseBackup(string $filename): void
    {
        $path = $this->backupFilePath($filename);
        File::delete($path);
    }

    protected function backupFilePath(string $filename): string
    {
        $directory = realpath($this->backupDirectory());
        $path = realpath($directory . DIRECTORY_SEPARATOR . $filename);

        if (! $path || ! str_starts_with($path, $directory)) {
            throw ValidationException::withMessages([
                'file' => 'Backup not found.',
            ]);
        }

        if (! File::exists($path) || ! File::isFile($path)) {
            throw ValidationException::withMessages([
                'file' => 'Backup not found.',
            ]);
        }

        return $path;
    }

    protected function databaseConfig(): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (! $config) {
            throw new \RuntimeException('Database configuration not found.');
        }

        if (($config['driver'] ?? null) !== 'mysql') {
            throw new \RuntimeException('Only MySQL/MariaDB connections are supported.');
        }

        return [
            'username' => $config['username'],
            'password' => $config['password'] ?? '',
            'database' => $config['database'],
            'host' => $config['host'] ?? '127.0.0.1',
            'port' => $config['port'] ?? 3306,
        ];
    }
}
