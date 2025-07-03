<?php
namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class BannerController extends Controller
{
    public function update(Request $req)
{
    $req->validate([
        'image_url' => 'required|url',
        'title' => 'nullable|string|max:255',
        'subtitle' => 'nullable|string',
    ]);

    $banner = Banner::first();

    if ($banner) {
        $banner->update([
            'image_url' => $req->image_url,
            'title' => $req->title,
            'subtitle' => $req->subtitle,
        ]);
    } else {
        $banner = Banner::create([
            'image_url' => $req->image_url,
            'title' => $req->title,
            'subtitle' => $req->subtitle,
        ]);
    }

    return response()->json($banner);
}


    public function show()
    {
        return response()->json(Banner::first());
    }
}
