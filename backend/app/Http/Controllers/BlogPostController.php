<?php

namespace App\Http\Controllers;

use App\Http\Resources\BlogPostResource;
use App\Models\BlogPost;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BlogPostController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'last_seen_id' => 'nullable|integer'
        ]);

        try
        {
            $posts = BlogPost::where('id', '>', $request->last_seen_id || 0)
                ->when($request->category, function (Builder $query, string $category) {
                    return $query->where('category', $category);
                })
                ->orderBy('id', 'desc')
                ->limit(10)
                ->get();
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return response()->error('Error retrieving posts');
        }

        return response()->success(BlogPostResource::collection($posts));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:100',
            'sub_heading' => 'nullable|string|max:500',
            'content' => 'required|string',
            'image' => 'required',
            'category' => 'required|string',
            'meta_title' => 'required|string|max:100',
            'meta_description' => 'required|string|max:300',
            'meta_image_alt' => 'required|string|max:100',
            'mime' => 'required|string'
        ]);

        try
        {

            $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->image));
            $fileName = generateRandomString().'.'.$request->mime;
            Storage::disk('s3')->put('blog/images/'.$fileName, $image, 'public');
            $data['image'] = "https://" . config('filesystems.disks.s3.bucket') . ".s3-" . config('filesystems.disks.s3.region') . ".amazonaws.com/blog/images/" . $fileName;
            $blogPost = new BlogPost($data);
            $blogPost->slug = str_replace(' ', '-', strtolower($blogPost->title));
            $blogPost->save();
        }
        catch (Exception $e)
        {
            var_dump($e->getMessage());
            return response()->error('Error creating blog post');
        }

        return response()->success(['message' => 'Blog post created successfully']);
    }

    public function show(Request $request, string $slug)
    {
        try
        {
            $post = BlogPost::where('slug', $slug)->first();
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to load post.');
        }

        return response()->success(BlogPostResource::make($post));
    }

    public function paths(Request $request)
    {
        try
        {
            $slugs = BlogPost::select('slug')->get()->pluck('slug')->toArray();
        }
        catch (Exception $e)
        {
            return response()->error('Something went wrong while trying to get paths.');
        }

        return response()->success($slugs);
    }
}
