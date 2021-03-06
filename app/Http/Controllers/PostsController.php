<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Category;
use App\Post;
use App\Tag;
use Session;

class PostsController extends Controller {
    
    public function index() {
        return view('back.posts.index')->with('posts', Post::all());
    }

    public function create() {
      
        $categories = Category::all();
        $tags = Tag::all();

        if($categories->count() == 0 || $tags->count() == 0) {
            Session::flash('info', 'You must have some categories and tags to create a post');
            return redirect()->back();
        }

        return view('back.posts.create')->with('categories', $categories)
                                              ->with('tags', $tags);
    }

    public function store(Request $request) {
        //dd($request->all());
        $this->validate($request, [
            'title' => 'required',
            'featured' => 'required|image',
            'content' => 'required',
            'cat_id' => 'required',
            'tags' => 'required'
        ]);

        $featured = $request->featured;
        $featured_new_name = time().$featured->getClientOriginalName();
        $featured->move('uploads/posts', $featured_new_name);

        $post = Post::create([
          'title' => $request->title,
          'content' => $request->content,
          'featured' => 'uploads/posts/'. $featured_new_name,
          'cat_id' => $request->cat_id,
          'slug' => str_slug($request->slug)
        ]);

        $post->tags()->attach($request->tags);

        Session::flash('success', 'Succesfully post created');
        return redirect()->route('posts');
    }

    public function show($id) {
    }

    public function edit($id) {
        $post = Post::find($id);
        return view('back.posts.edit')->with('post', $post)
                                      ->with('categories', Category::all())
                                      ->with('tags', Tag::all());
    }

    public function update(Request $request, $id) {
        $this->validate($request, [
            'title' => 'required',
            'content' => 'required',
            'cat_id' => 'required'
        ]);

        $post = Post::find($id);

        if ($request->hasFile('featured')) {
            $featured = $request->featured;
            $featured_new_name = time().$featured->getClientOriginalName();
            $featured->move('uploads/posts', $featured_new_name);
            $post->featured = 'uploads/posts/'.$featured_new_name;
        }

        $post->title = $request->title;
        $post->content = $request->content;
        $post->cat_id = $request->cat_id;

        $post->save();

        $post->tags()->sync($request->tags);

        Session::flash('success', 'Succesfully, post updated');

        return redirect()->route('posts');
    }

    public function trash($id) {
        $post = Post::find($id);
        $post->delete();

        Session::flash('success', 'Post was trashed');
        return redirect()->back();
    }

    public function trashed() {  
       $posts = Post::onlyTrashed()->get();
       return view('back.posts.trashed')->with('posts', $posts);
    }

    public function destroy($id) {
        $post = Post::withTrashed()->where('id', $id)->first();
        $post->forceDelete();

        Session::flash('success', 'Post was deleted');
        return redirect()->back();
    }

    public function restore($id) {
       $post = Post::withTrashed()->where('id', $id)->first();
       $post->restore();
       Session::flash('success', 'Succesfully restore post');
       return redirect()->route('posts');
    }
}
