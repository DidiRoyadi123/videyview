<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\Category;
use Illuminate\Http\Request;

class VideoApiController extends Controller
{
    public function index()
    {
        $videos = Video::with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $videos->getCollection()->transform(function($v) {
            return $v->makeHidden(['url', 'mirror_links', 'hosting_status', 'local_path', 'remote_id']);
        });

        return response()->json($videos);
    }

    public function show($slug)
    {
        $video = Video::with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($video->makeHidden(['url', 'mirror_links', 'hosting_status', 'local_path', 'remote_id']));
    }

    public function categories()
    {
        $categories = Category::orderBy('order')->get(['id', 'name', 'slug', 'icon']);
        return response()->json($categories);
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $videos = Video::with('category')
            ->where('title', 'like', "%{$query}%")
            ->paginate(12);

        $videos->getCollection()->transform(function($v) {
            return $v->makeHidden(['url', 'mirror_links', 'hosting_status', 'local_path', 'remote_id']);
        });

        return response()->json($videos);
    }
}
