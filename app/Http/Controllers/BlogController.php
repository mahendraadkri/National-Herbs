<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BlogController extends Controller
{

    /**
     * Display a listing of the blogs.
     */
    public function index()
    {
        $blogs = Blog::all()->map(function ($blog) {
            $blog->image_url = $blog->image ? Storage::disk('public')->url($blog->image) : null;
            return $blog;
        });

        return response()->json([
            'success' => true,
            'data'    => $blogs
        ], 200);
    }

    /**
     * Store a newly created blog in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => ['required', 'string'],
            'description' => ['required', 'string'],
            'image'       => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Authenticated user
        $user = $request->user(); // or Auth::user()

        // Store image to public disk
        $path = $request->file('image')->store('blogs', 'public'); // e.g. blogs/abc123.jpg

        $blog = Blog::create([
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'image'       => $path, // store relative path
            // Auto-fill from login user
            'blog_by'     => $user?->name ?: ($user?->email ?: 'Unknown'),
        ]);

        $blog->image_url = Storage::disk('public')->url($blog->image);

        return response()->json([
            'success' => true,
            'message' => 'Blog created successfully',
            'data'    => $blog
        ], 201);
    }

    /**
     * Display the specified blog.
     */
    public function show($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        $blog->image_url = $blog->image ? Storage::disk('public')->url($blog->image) : null;

        return response()->json([
            'success' => true,
            'data'    => $blog
        ], 200);
    }

    /**
     * Update the specified blog in storage.
     */
    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);
        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title'       => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'image'       => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif'],
        ]);
        // dd($validator);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Safely copy any provided scalar fields
        $blog->fill($request->only(['title', 'description']));

        // Handle image replacement
        if ($request->hasFile('image')) {
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            $blog->image = $request->file('image')->store('blogs', 'public');
        }

        $blog->save(); 
        $blog->refresh(); 

        $blog->image_url = $blog->image ? Storage::disk('public')->url($blog->image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Blog updated successfully',
            'data'    => $blog
        ], 200);
    }

    /**
     * Return total number of products.
     */
    public function blog_count()
    {
        $total = Blog::count();

        return response()->json([
            'total' => $total
        ], 200);
    }


    /**
     * Remove the specified blog from storage.
     */
    public function destroy($id)
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found'
            ], 404);
        }

        // Delete file if present
        if ($blog->image && Storage::disk('public')->exists($blog->image)) {
            Storage::disk('public')->delete($blog->image);
        }

        $blog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blog deleted successfully'
        ], 200);
    }
}
