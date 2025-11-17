<?php

use Illuminate\Support\Facades\Route;
use XpCommand\CommandCenter\Http\Controllers\CommandActionController;
use XpCommand\CommandCenter\Http\Controllers\CommandCenterController;

Route::get('/', [CommandCenterController::class, 'overview'])->name('overview');
Route::get('/artisan', [CommandCenterController::class, 'artisan'])->name('artisan');
Route::get('/environment', [CommandCenterController::class, 'environment'])->name('environment');
Route::get('/sql', [CommandCenterController::class, 'sql'])->name('sql');
Route::get('/logs', [CommandCenterController::class, 'logs'])->name('logs');
Route::get('/files', [CommandCenterController::class, 'files'])->name('files');
Route::get('/git', [CommandCenterController::class, 'git'])->name('git');
Route::get('/maintenance', [CommandCenterController::class, 'maintenance'])->name('maintenance');
Route::get('/system', [CommandCenterController::class, 'system'])->name('system');
Route::get('/backups', [CommandCenterController::class, 'backups'])->name('backups');
Route::get('/queues', [CommandCenterController::class, 'queues'])->name('queues');

Route::post('/artisan/run', [CommandActionController::class, 'runArtisan'])->name('artisan.run');
Route::post('/environment/upsert', [CommandActionController::class, 'upsertEnv'])->name('environment.upsert');
Route::post('/sql/run', [CommandActionController::class, 'runSql'])->name('sql.run');
Route::post('/logs/tail', [CommandActionController::class, 'tailLogs'])->name('logs.tail');
Route::post('/files/save', [CommandActionController::class, 'saveFile'])->name('files.save');
Route::post('/git/action', [CommandActionController::class, 'gitAction'])->name('git.action');
Route::post('/maintenance/run', [CommandActionController::class, 'maintenanceAction'])->name('maintenance.run');
Route::post('/backups/run', [CommandActionController::class, 'runBackup'])->name('backups.run');
Route::post('/backups/restore', [CommandActionController::class, 'restoreBackup'])->name('backups.restore');
Route::delete('/backups/delete', [CommandActionController::class, 'deleteBackup'])->name('backups.delete');
Route::post('/backups/download', [CommandActionController::class, 'downloadBackup'])->name('backups.download');
Route::post('/queues/flush-failed', [CommandActionController::class, 'flushFailedJobs'])->name('queues.flush-failed');
Route::post('/queues/clear-pending', [CommandActionController::class, 'clearPendingJobs'])->name('queues.clear-pending');
