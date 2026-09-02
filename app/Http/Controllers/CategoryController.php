<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/Categories', [
            'categories' => CategoryResource::collection(
                $request->user()->currentWorkspaceOrFail()->categories()->orderBy('is_archived')->orderBy('name')->get(),
            )->resolve(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->currentWorkspaceOrFail()->categories()->create($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => __('app.category.created')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreCategoryRequest $request, string $category): RedirectResponse
    {
        $this->category($request, $category)->update($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => __('app.category.updated')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $category): RedirectResponse
    {
        $this->category($request, $category)->update(['is_archived' => true]);

        return back()->with('toast', ['type' => 'success', 'message' => __('app.category.archived')]);
    }

    private function category(Request $request, string $id): Category
    {
        return $request->user()->currentWorkspaceOrFail()->categories()->findOrFail($id);
    }
}
