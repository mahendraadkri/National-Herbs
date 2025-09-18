<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // If your Product model casts 'images' to array, this is automatic.
        $products = Product::with('category')->get()->map(function ($p) {
            $p->images = $this->decodeImages($p->images);
            return $p;
        });

        return response()->json(['products' => $products], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
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
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|unique:products,name',
            'old_price'   => 'nullable|integer',
            'price'       => 'required|integer',
            'description' => 'nullable|string',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $imagePaths = null;

        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('product_images', 'public');
            }
            // store as JSON string; leave null if none uploaded
            $imagePaths = json_encode($paths);
        }

        $productData = $request->only(['category_id', 'name', 'old_price', 'price', 'description']);
        $productData['images'] = $imagePaths; // may be null

        $product = Product::create($productData);
        $product->images = $this->decodeImages($product->images);

        return response()->json(['success' => true, 'product' => $product], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->images = $this->decodeImages($product->images);

        return response()->json(['product' => $product], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $product->images = $this->decodeImages($product->images);

        return response()->json(['product' => $product], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'name'        => 'sometimes|string|max:255|unique:products,name,' . $product->id . ',id',
            'old_price'   => 'nullable|integer',
            'price'       => 'sometimes|integer',
            'description' => 'nullable|string',

            // images are optional; send as form-data: images[]
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpeg,png,jpg,gif',

            // optional flag to clear all existing images without uploading new ones
            'clear_images'=> 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prepare updatable fields
        $productData = $request->only(['category_id', 'name', 'old_price', 'price', 'description']);

        // If client wants to clear current images explicitly
        if ($request->boolean('clear_images')) {
            $this->deleteImagesFromStorage($product->images);
            $productData['images'] = null; // DB column is nullable
        }

        // If new images uploaded, replace old ones
        if ($request->hasFile('images')) {
            $this->deleteImagesFromStorage($product->images);

            $paths = [];
            foreach ($request->file('images') as $image) {
                $paths[] = $image->store('product_images', 'public');
            }
            $productData['images'] = json_encode($paths);
        }

        // Persist changes
        $product->update($productData); // requires $fillable on the model

        // Return fresh data
        $product->refresh();
        $product->images = $this->decodeImages($product->images);

        return response()->json(['success' => true, 'product' => $product], 200);
    }

    /**
     * Helpers
     * (keep these in your controller; shown here for completeness)
     */
    private function decodeImages($images)
    {
        if (is_null($images) || $images === '') {
            return null;
        }
        $decoded = json_decode($images, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function deleteImagesFromStorage($images)
    {
        $paths = $this->decodeImages($images) ?? [];
        foreach ($paths as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->deleteImagesFromStorage($product->images);
        $product->delete();

        return response()->json(['success' => true, 'message' => 'Product deleted successfully.'], 200);
    }
}
