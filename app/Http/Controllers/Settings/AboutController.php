<?php

namespace App\Http\Controllers\Settings;

use App\Actions\CheckForUpdates;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AboutController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('settings/About', [
            'project' => config('financeiro'),
        ]);
    }

    public function updates(CheckForUpdates $checkForUpdates): JsonResponse
    {
        return response()->json($checkForUpdates->handle())
            ->header('Cache-Control', 'no-store');
    }
}
