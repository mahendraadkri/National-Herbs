<?php

namespace App\Http\Controllers;

use App\Models\OurTeam;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

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

        return response()->json(['success' => true, 'data' => $products], 200);
    }

    public function create()
    {
        return response()->json(['message' => 'Provide product data to create'], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required','exists:categories,id'],
            'name'        => ['required','string','unique:products,name'],
            'old_price'   => ['nullable','integer'],
            'price'       => ['required','integer'],
            'description' => ['nullable','string'],
            'images'      => ['required' ,'array'],
            'images.*'    => ['file','image','mimes:jpeg,png,jpg,gif'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Normalize to an array of UploadedFile
        $files = $request->file('images');
        $files = is_array($files) ? $files : [$files];

        $paths = [];
        foreach ($files as $image) {
            $paths[] = $image->store('product_images', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'slug'        => $this->generateUniqueSlug($request->name),
            'old_price'   => $request->old_price,
            'price'       => $request->price,
            'description' => $request->description,
            'images'      => json_encode($paths),
        ]);

        // convert storage paths to full URLs for API response
        $product->images = $this->pathsToUrls($product->images);

        return response()->json(['success' => true, 'product' => $product], 201);
    }


    /**
     * Display the specified resource.
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
     */
   public function update(Request $request, $key)
    {
        // Resolve product by id or slug
        $product = ctype_digit((string)$key)
            ? Product::find((int)$key) : Product::where('slug', $key)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'category_id'     => ['exists:categories,id'],
            'name'            => ['string', Rule::unique('products','name')->ignore($product->id)],
            'old_price'       => ['nullable','integer'],
            'price'           => ['integer'],
            'description'     => ['nullable','string'],
            'images'          => ['array'],        
            'images.*'        => ['file','image','mimes:jpeg,png,jpg,gif'],
            'remove_images'   => ['array'],
            'remove_images.*' => ['string'],
        ]);

        if ($validator->fails()) {
            // Log::warning('Product.update validation failed', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ---- LOAD CURRENT IMAGES (always storage paths) ----
        $existingPaths = $this->decodeImages($product->images);

        // ---- REMOVALS ----
        $toRemove = (array) $request->input('remove_images', []);
        if (!empty($toRemove)) {
            $removePaths   = array_map(fn($x) => $this->normalizeUrlToPath($x), $toRemove);
            $existingPaths = array_values(array_diff($existingPaths, $removePaths));

            foreach ($removePaths as $rp) {
                try {
                    if ($rp && Storage::disk('public')->exists($rp)) {
                        Storage::disk('public')->delete($rp);
                    }
                } catch (\Throwable $e) {
                    Log::error('Product.update remove file error', ['path'=>$rp, 'err'=>$e->getMessage()]);
                }
            }
        }

        // ---- NEW UPLOADS ----
        $newPaths = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!is_array($files)) $files = [$files];
            foreach ($files as $file) {
                try {
                    $newPaths[] = $file->store('product_images', 'public');
                } catch (\Throwable $e) {
                    Log::error('Product.update store file error', ['err'=>$e->getMessage()]);
                }
            }
        }

        // ---- MERGE & DE-DUP ----
        $finalPaths = array_values(array_unique(array_merge($existingPaths, $newPaths)));

        // ---- SCALAR UPDATES ----
        $updates = [];

        if ($request->filled('category_id')) $updates['category_id'] = $request->category_id;

        if ($request->filled('name')) {
            $updates['name'] = $request->name;
            if (!Str::is($product->name, $request->name)) {
                $updates['slug'] = $this->generateUniqueSlug($request->name, $product->id);
            }
        }

        if ($request->exists('old_price'))   $updates['old_price']   = $request->old_price; // nullable ok
        if ($request->filled('price'))       $updates['price']       = $request->price;
        if ($request->exists('description')) $updates['description'] = $request->description;

        // ---- PERSIST IMAGES (honor model casts) ----
        $casts = $product->getCasts();
        $updates['images'] = (isset($casts['images']) && $casts['images'] === 'array')
            ? $finalPaths
            : json_encode($finalPaths);

        // ---- SAVE ----
        $product->update($updates);

        // ---- RESPONSE ----
        $product->load('category');
        $product->images = $this->pathsToUrls($product->images);

        Log::info('Product.update success', ['id'=>$product->id, 'images_count'=>count($product->images)]);

        return response()->json(['success'=>true, 'product'=>$product], 200);
    }
    /**
     * Return total number of products.
     */
    public function product_count()
    {
        $total = Product::count();

        return response()->json([
            'total' => $total
        ], 200);
    }


    // Delete products.
    public function destroy($key)
    {
        // Resolve product by id or slug
        $product = ctype_digit((string)$key)
            ? Product::find((int)$key)
            : Product::where('slug', $key)->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }

        try {
            // Delete stored images (handles array/json via helper)
            $this->deleteImagesFromStorage($product->images);

            // Delete the product row
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully.'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Product.destroy error', [
                'key' => $key,
                'err' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product.'
            ], 500);
        }
    }



    /**
     * Helpers function
     */
    private function decodeImages($images): array
    {
        if (is_array($images)) {
            return $images;
        }

        if (is_string($images) && $images !== '') {
            $arr = json_decode($images, true);
            return is_array($arr) ? $arr : [];
        }

        return [];
    }

    /**
     * Map storage paths to public URLs.
     */
    private function pathsToUrls($images): array
    {
        $paths = $this->decodeImages($images);
        return array_map(
            fn ($p) => Storage::disk('public')->url($p),
            $paths
        );
    }

    private function deleteImagesArrayFromStorage(array $paths): void
    {
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

     private function deleteImagesFromStorage($images)
    {
        $paths = $this->decodeImages($images) ?? [];
        $this->deleteImagesArrayFromStorage($paths);
    }

    /**
     * Generate a unique slug of a product according to given name.
     */
    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        $query = Product::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        while ($query->exists()) {
            $slug = $base.'-'.$i++;
            $query = Product::query()->where('slug', $slug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    /**
     * Convert a full URL or "/storage/..." URL to a storage path like "product_images/abc.jpg".
     */
    private function normalizeUrlToPath(string $item): string
    {
        $item = trim($item);

        // Strip scheme+host if present: https://example.com/storage/...
        $item = preg_replace('#^https?://[^/]+#', '', $item);

        // Now handle leading "/storage/..."
        $publicPrefix = rtrim(Storage::url('/'), '/');
        if (strpos($item, $publicPrefix.'/') === 0) {
            $item = substr($item, strlen($publicPrefix.'/'));
        }

        // Remove any leading slash that might remain
        return ltrim($item, '/');
    }
}