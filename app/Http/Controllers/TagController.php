<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        return Tag::where('is_active', true)
            ->select('id', 'name')
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags'
        ]);

        $tag = Tag::create([
            'name' => $request->name,
            'is_active' => true
        ]);

        return response()->json($tag, 201);
    }

    public function toggleStatus($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->is_active = !$tag->is_active;
        $tag->save();

        return response()->json(['message' => 'Estado del tag actualizado']);
    }
}
