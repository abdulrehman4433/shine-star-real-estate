<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Support\Facades\Auth;

class BlogController extends Controller
{
    public function show(BlogPost $post)
    {
        $canPreview = Auth::check() && Auth::user()->hasAnyRole(['admin', 'super-admin']);

        abort_if(! $post->isPublished() && ! $canPreview, 404);

        $post->load(['category', 'author', 'tags', 'media']);

        return view('frontend.blog.show', [
            'post' => $post,
            'relatedPosts' => $post->relatedPosts(),
        ]);
    }
}
