<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->get()->map(function ($p) {
            $p->images = $this->pathsToUrls($p->images);
            return $p;
        });

        return response()->json(['products' => $products], 200);
    }

    public function create()
    {
        return response()->json(['message' => 'Provide product data to create'], 200);
    }

    /**
     * Store a newly created resource in storage.
     * Creates a unique slug from name.
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'category_id' => ['required','exists:categories,id'],
            'name'        => ['required','string','max:255','unique:products,name'],
            'old_price'   => ['nullable','integer'],
            'price'       => ['required','integer'],
            'description' => ['nullable','string'],
            'images'      => ['nullable','array'],
            'images.*'    => ['image','mimes:jpeg,png,jpg,gif','max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // store files and keep PATHS in DB
        $paths = null;
        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('product_images', 'public');
            }
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => $this->generateUniqueSlug($request->name),
            'old_price'   => $request->old_price,
            'price'       => $request->price,
            'description' => $request->description,
            'images'      => $paths ? json_encode($paths) : null,
        ]);

        $product->images = $this->pathsToUrls($product->images);

        return response()->json(['success' => true, 'product' => $product], 201);
    }

    /**
     * Display the specified resource.
     * Works with route model binding (id or slug if configured).
     */
    public function show(Product $product)
    {
        $product->load('category');
        $product->images = $this->pathsToUrls($product->images);

        return response()->json(['product' => $product], 200);
    }

    public function edit(Product $product)
    {
        $product->images = $this->pathsToUrls($product->images);

        return response()->json(['product' => $product], 200);
    }

    /**
     * Update the specified resource in storage.
     * Regenerates slug if name changes.
     */
    public function update(Request $request, Product $product)
    {
        $validator = \Validator::make($request->all(), [
            'category_id' => ['sometimes','exists:categories,id'],
            'name'        => ['sometimes','string','max:255', Rule::unique('products','name')->ignore($product->id)],
            'old_price'   => ['nullable','integer'],
            'price'       => ['sometimes','integer'],
            'description' => ['nullable','string'],
            'images'      => ['nullable','array'],
            'images.*'    => ['image','mimes:jpeg,png,jpg,gif','max:2048'],
            'clear_images'=> ['sometimes','boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $hasPayload =
            $request->hasAny(['category_id','name','old_price','price','description','clear_images'])
            || $request->hasFile('images');

        if (!$hasPayload) {
            return response()->json(['message' => 'No fields to update.'], 400);
        }

        $productData = $request->only(['category_id','name','old_price','price','description']);

        // If name changed, refresh slug (keep unique, ignore current id)
        if ($request->filled('name') && $request->name !== $product->name) {
            $productData['slug'] = $this->generateUniqueSlug($request->name, $product->id);
        }

        if ($request->boolean('clear_images')) {
            $this->deleteImagesFromStorage($product->images);
            $productData['images'] = null;
        }

        if ($request->hasFile('images')) {
            $this->deleteImagesFromStorage($product->images);
            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('product_images', 'public');
            }
            $productData['images'] = json_encode($paths);
        }

        $product->update($productData);
        $product->refresh();
        $product->images = $this->pathsToUrls($product->images);

        return response()->json(['success' => true, 'product' => $product], 200);
    }

    public function destroy(Product $product)
    {
        $this->deleteImagesFromStorage($product->images);
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product deleted successfully.'], 200);
    }

    /**
     * ---- Helpers ----
     */

    private function decodeImages($images): ?array
    {
        if (is_null($images) || $images === '') return null;
        $decoded = json_decode($images, true);
        return is_array($decoded) ? $decoded : null;
    }

    /** Convert stored JSON paths to public URLs for responses */
    private function pathsToUrls($images): ?array
    {
        $paths = $this->decodeImages($images);
        if (!$paths) return null;

        return array_map(function ($path) {
            return Storage::disk('public')->url($path);
        }, $paths);
    }

    private function deleteImagesFromStorage($images): void
    {
        $paths = $this->decodeImages($images) ?? [];
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Generate a unique slug from a given name.
     * Appends -2, -3, ... until unique. Ignores $ignoreId on update.
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        $exists = function (string $candidate) use ($ignoreId): bool {
            return Product::where('slug', $candidate)
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