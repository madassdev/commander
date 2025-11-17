<?php

namespace XpCommand\CommandCenter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use XpCommand\CommandCenter\Http\Controllers\Concerns\HandlesCommandTools;

class CommandCenterController extends Controller
{
    use HandlesCommandTools;

    protected function render(string $component, string $section, array $props = [], string $description = ''): Response
    {
        return Inertia::render($component, array_merge([
            'meta' => [
                'title' => 'Command Center',
                'current' => $section,
                'description' => $description,
            ],
            'requirements' => [
                'masterPasswordSet' => (bool) config('command-center.master_password'),
            ],
        ], $props));
    }

    public function overview(Request $request): Response
    {
        return $this->render('Command/Index', 'overview', [
            'stats' => [
                'env' => count($this->readEnvEntries()),
                'fileRoots' => count($this->availableFileRoots()),
                'git' => $this->gitInfo(),
            ],
        ], 'Central overview of sensitive capabilities.');
    }

    public function artisan(Request $request): Response
    {
        return $this->render('Command/Artisan', 'artisan', [], 'Execute artisan commands with confirmation.');
    }

    public function environment(Request $request): Response
    {
        return $this->render('Command/Environment', 'environment', [
            'entries' => $this->readEnvEntries(),
        ], 'Inspect and update environment variables.');
    }

    public function sql(Request $request): Response
    {
        return $this->render('Command/Sql', 'sql', [], 'Run SQL statements directly against the database.');
    }

    public function logs(Request $request): Response
    {
        return $this->render('Command/Logs', 'logs', [
            'defaultLines' => 200,
        ], 'Tail laravel.log without shell access.');
    }

    public function files(Request $request): Response
    {
        $path = $request->string('path')->value();
        $fileContent = null;
        $resolvedPath = null;

        if ($path) {
            try {
                $resolvedPath = $this->resolveEditablePath($path);
                $fileContent = $this->readFileContent($resolvedPath);
            } catch (\Throwable $exception) {
                session()->flash('error', $exception->getMessage());
            }
        }

        return $this->render('Command/Files', 'files', [
            'filesIndex' => $this->fileIndex(),
            'selectedPath' => $path,
            'resolvedPath' => $resolvedPath,
            'fileContent' => $fileContent,
        ], 'Edit whitelisted files securely.');
    }

    public function git(Request $request): Response
    {
        return $this->render('Command/Git', 'git', [
            'git' => $this->gitInfo(),
            'branch' => config('command-center.git.default_branch'),
        ], 'Review git status and trigger deploy actions.');
    }

    public function maintenance(Request $request): Response
    {
        return $this->render('Command/Maintenance', 'maintenance', [], 'Clear caches, restart queues, or toggle maintenance mode.');
    }

    public function system(Request $request): Response
    {
        return $this->render('Command/System', 'system', [
            'info' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => config('app.env'),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone'),
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
                'database' => config('database.default'),
            ],
        ], 'At-a-glance environment information.');
    }

    public function backups(Request $request): Response
    {
        return $this->render('Command/Backups', 'backups', [
            'backups' => $this->listBackups(),
        ], 'Create, download, or restore database snapshots.');
    }

    public function queues(Request $request): Response
    {
        $stats = [
            'queued' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            'failed' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];

        $failedJobs = collect();

        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(10)
                ->get(['id', 'queue', 'connection', 'failed_at', 'exception'])
                ->map(fn ($job) => [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'connection' => $job->connection,
                    'failed_at' => optional($job->failed_at)->toDateTimeString(),
                    'exception' => Str::of($job->exception)->limit(160)->value(),
                ]);
        }

        return $this->render('Command/Queues', 'queues', [
            'stats' => $stats,
            'failedJobs' => $failedJobs,
        ], 'Monitor and purge queued/failed jobs.');
    }
}
