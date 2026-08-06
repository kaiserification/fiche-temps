<?php

use App\Http\Controllers\GitCommitController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->post('/git-commits/format', [GitCommitController::class, 'formatFromCommits'])->name('api.git-commits.format');
