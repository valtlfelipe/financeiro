<?php

namespace App\Http\Controllers\Settings;

use App\Actions\CheckForUpdates;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function updates(Request $request, CheckForUpdates $checkForUpdates): JsonResponse
    {
        return response()->json($checkForUpdates->handle($request->boolean('refresh')))
            ->header('Cache-Control', 'no-store');
    }
}
