<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\Comment;
use App\Services\ContentFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public $filter;

    public function __construct(ContentFilterService $filter)
    {
        $this->filter = $filter;
    }

    public function store(Request $request, Video $video)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        if (!$this->filter->isClean($request->input('content'))) {
            return back()->withErrors(['content' => $this->filter->getErrorMessage($request->input('content'))]);
        }

        Comment::create([
            'user_id' => Auth::id(),
            'video_id' => $video->id,
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Komentar berhasil dikirim.');
    }

    public function destroy(Comment $comment)
    {
        if (Auth::id() !== $comment->user_id && !Auth::user()->is_admin) {
            abort(403);
        }

        $comment->delete();
        return back()->with('success', 'Komentar berhasil dihapus.');
    }
}
