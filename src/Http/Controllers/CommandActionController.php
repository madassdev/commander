<?php

namespace Madassdev\Commander\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Madassdev\Commander\Http\Controllers\Concerns\HandlesCommandTools;

class CommandActionController extends Controller
{
    use HandlesCommandTools;

    public function runArtisan(Request $request)
    {
        $data = $this->validateAction($request, [
            'command' => ['required', 'string', 'max:255'],
        ]);

        try {
            $exitCode = Artisan::call($data['command']);
            $output = trim(Artisan::output());
        } catch (\Throwable $exception) {
            return $this->toolResponse('artisan', [
                'command' => $data['command'],
                'error' => $exception->getMessage(),
            ], 'Failed to run artisan command.', 'error');
        }

        return $this->toolResponse('artisan', [
            'command' => $data['command'],
            'exitCode' => $exitCode,
            'output' => $output,
        ], 'Command executed successfully.');
    }

    public function upsertEnv(Request $request)
    {
        $data = $this->validateAction($request, [
            'key' => ['required', 'string', 'max:255', 'regex:/^[A-Z0-9_\.]+$/'],
            'value' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->writeEnvValue($data['key'], $data['value'] ?? '');
        } catch (\Throwable $exception) {
            return $this->toolResponse('environment', [
                'key' => $data['key'],
                'error' => $exception->getMessage(),
            ], 'Unable to update .env file.', 'error');
        }

        return $this->toolResponse('environment', [
            'key' => $data['key'],
            'value' => $data['value'] ?? '',
        ], 'Environment variable saved.');
    }

    public function runSql(Request $request)
    {
        $data = $this->validateAction($request, [
            'statement' => ['required', 'string'],
        ]);

        $statement = rtrim($data['statement'], ';');
        $firstToken = str($statement)->trim()->lower()->before(' ')->value();

        try {
            if (in_array($firstToken, ['select', 'show', 'describe', 'pragma', 'explain'])) {
                $result = array_map(fn ($row) => (array) $row, DB::select($statement));
            } else {
                $result = ['rowsAffected' => DB::affectingStatement($statement)];
            }
        } catch (\Throwable $exception) {
            return $this->toolResponse('sql', [
                'statement' => $statement,
                'error' => $exception->getMessage(),
            ], 'SQL execution failed.', 'error');
        }

        return $this->toolResponse('sql', [
            'statement' => $statement,
            'result' => $result,
        ], 'SQL statement executed.');
    }

    public function tailLogs(Request $request)
    {
        $data = $this->validateAction($request, [
            'lines' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $lines = $data['lines'] ?? 200;
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            return $this->toolResponse('logs', [
                'lines' => $lines,
                'error' => 'Log file not found.',
            ], 'laravel.log not found.', 'error');
        }

        try {
            $content = $this->readLogTail($logPath, $lines);
        } catch (\Throwable $exception) {
            return $this->toolResponse('logs', [
                'lines' => $lines,
                'error' => $exception->getMessage(),
            ], 'Unable to read log file.', 'error');
        }

        return $this->toolResponse('logs', [
            'lines' => $lines,
            'content' => $content,
        ], 'Fetched latest log lines.');
    }

    public function saveFile(Request $request)
    {
        $data = $this->validateAction($request, [
            'path' => ['required', 'string'],
            'content' => ['nullable', 'string'],
        ]);

        try {
            $resolved = $this->resolveEditablePath($data['path']);
            $this->writeFileContent($resolved, $data['content'] ?? '');
        } catch (\Throwable $exception) {
            return $this->toolResponse('files', [
                'path' => $data['path'],
                'error' => $exception->getMessage(),
            ], 'Unable to save file.', 'error');
        }

        return $this->toolResponse('files', [
            'path' => $data['path'],
        ], 'File saved successfully.');
    }

    public function gitAction(Request $request)
    {
        $data = $this->validateAction($request, [
            'action' => ['required', 'in:status,fetch,pull'],
            'branch' => ['nullable', 'string', 'max:255'],
        ]);

        $branch = $data['branch'] ?: config('command-center.git.default_branch');

        try {
            $output = match ($data['action']) {
                'status' => $this->runGit(['status', '-sb']),
                'fetch' => $this->runGit(['fetch', '--all']),
                'pull' => $this->runGit(['pull', 'origin', $branch]),
            };
        } catch (\Throwable $exception) {
            return $this->toolResponse('git', [
                'action' => $data['action'],
                'error' => $exception->getMessage(),
            ], 'Git command failed.', 'error');
        }

        return $this->toolResponse('git', [
            'action' => $data['action'],
            'output' => $output,
        ], 'Git command executed.');
    }

    public function maintenanceAction(Request $request)
    {
        $data = $this->validateAction($request, [
            'task' => ['required', 'in:cache-clear,config-clear,config-cache,queue-restart,down,up,schedule-run'],
        ]);

        $command = match ($data['task']) {
            'cache-clear' => 'cache:clear',
            'config-clear' => 'config:clear',
            'config-cache' => 'config:cache',
            'queue-restart' => 'queue:restart',
            'down' => 'down',
            'up' => 'up',
            'schedule-run' => 'schedule:run',
        };

        try {
            Artisan::call($command);
            $output = trim(Artisan::output());
        } catch (\Throwable $exception) {
            return $this->toolResponse('maintenance', [
                'task' => $data['task'],
                'error' => $exception->getMessage(),
            ], 'Maintenance command failed.', 'error');
        }

        return $this->toolResponse('maintenance', [
            'task' => $data['task'],
            'output' => $output,
        ], 'Maintenance task completed.');
    }

    public function runBackup(Request $request)
    {
        $data = $this->validateAction($request, [
            'label' => ['nullable', 'string', 'max:60'],
        ]);

        try {
            $backup = $this->createDatabaseBackup($data['label'] ?? null);
        } catch (\Throwable $exception) {
            return $this->toolResponse('backups', [
                'error' => $exception->getMessage(),
            ], 'Failed to create backup.', 'error');
        }

        return $this->toolResponse('backups', [
            'file' => $backup['filename'],
        ], 'Database backup created.');
    }

    public function restoreBackup(Request $request)
    {
        $data = $this->validateAction($request, [
            'file' => ['required', 'string'],
        ]);

        try {
            $this->restoreDatabaseBackup($data['file']);
        } catch (\Throwable $exception) {
            return $this->toolResponse('backups', [
                'file' => $data['file'],
                'error' => $exception->getMessage(),
            ], 'Restore failed.', 'error');
        }

        return $this->toolResponse('backups', [
            'file' => $data['file'],
        ], 'Database restored from backup.');
    }

    public function deleteBackup(Request $request)
    {
        $data = $this->validateAction($request, [
            'file' => ['required', 'string'],
        ]);

        try {
            $this->deleteDatabaseBackup($data['file']);
        } catch (\Throwable $exception) {
            return $this->toolResponse('backups', [
                'file' => $data['file'],
                'error' => $exception->getMessage(),
            ], 'Unable to delete backup.', 'error');
        }

        return $this->toolResponse('backups', [
            'file' => $data['file'],
        ], 'Backup deleted.');
    }

    public function downloadBackup(Request $request)
    {
        $data = $this->validateAction($request, [
            'file' => ['required', 'string'],
        ]);

        $path = $this->backupFilePath($data['file']);

        return response()->download($path, $data['file']);
    }

    public function flushFailedJobs(Request $request)
    {
        $this->validateAction($request, []);

        if (Schema::hasTable('failed_jobs')) {
            DB::table('failed_jobs')->delete();
        }

        return $this->toolResponse('queues', [
            'message' => 'Failed jobs cleared.',
        ], 'Failed jobs cleared.');
    }

    public function clearPendingJobs(Request $request)
    {
        $this->validateAction($request, []);

        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->delete();
        }

        return $this->toolResponse('queues', [
            'message' => 'Pending jobs table cleared.',
        ], 'Pending jobs table cleared.');
    }

    protected function toolResponse(string $context, array $payload, string $message, string $type = 'success')
    {
        session()->flash('commandFeedback', [
            'context' => $context,
            'type' => $type,
            'payload' => $payload,
        ]);

        session()->flash($type === 'success' ? 'success' : 'error', $message);

        return back();
    }
}
