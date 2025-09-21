<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json(['categories' => $categories], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return response()->json(['message' => 'Provide category data to create'], 200);
    }

    /**
     * Store a newly created resource in storage.
     * Auto-generates a unique slug from name.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required','string','unique:categories,name'],
            'description' => ['nullable','string'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['name']);

        $category = Category::create($validated);

        return response()->json(['success' => true, 'category' => $category], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json(['category' => $category], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return response()->json(['category' => $category], 200);
    }

    /**
     * Update the specified resource in storage.
     * Regenerates slug if name changes (keeps it unique).
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'=> ['required','string', Rule::unique('categories','name')->ignore($category->id),
            ],
            'description' => ['nullable','string'],
        ]);

        // If the name changed, refresh the slug (unique).
        if ($validated['name'] !== $category->name) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $category->id);
        }

        $category->update($validated);

        return response()->json(['success' => true, 'category' => $category], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(['success' => true, 'message' => 'Category deleted'], 200);
    }

    /**
     * Generate a unique slug from a given name.
     * If the slug exists, append -2, -3, ... until unique.
     *
     * @param string $name
     * @param int|null $ignoreId  (category id to ignore when updating)
     * @return string
     */
     private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        $exists = function (string $candidate) use ($ignoreId): bool {
            return Category::where('slug', $candidate)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();
        };

        while ($exists($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
