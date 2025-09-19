<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
// If you prefer Auth::user()
use Illuminate\Support\Facades\Auth;

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
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image'       => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
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
            'image'       => $path, // store relative path only
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
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'image'       => ['sometimes', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            // 'blog_by'   => removed; cannot be changed by request
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        // Only set the fields that were provided (and allow NO override of blog_by)
        $updateData = [];
        if ($request->has('title'))       $updateData['title'] = $request->input('title');
        if ($request->has('description')) $updateData['description'] = $request->input('description');

        if ($request->hasFile('image')) {
            // Delete old file if exists
            if ($blog->image && Storage::disk('public')->exists($blog->image)) {
                Storage::disk('public')->delete($blog->image);
            }
            // Store new file
            $newPath = $request->file('image')->store('blogs', 'public');
            $updateData['image'] = $newPath;
        }

        $blog->update($updateData);

        $blog->image_url = $blog->image ? Storage::disk('public')->url($blog->image) : null;

        return response()->json([
            'success' => true,
            'message' => 'Blog updated successfully',
            'data'    => $blog
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
