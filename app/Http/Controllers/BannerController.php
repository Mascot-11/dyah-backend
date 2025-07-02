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
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        'title' => 'nullable|string|max:255',
        'subtitle' => 'nullable|string',
    ]);

    $client = new Client();
    $res = $client->post('https://api.imgbb.com/1/upload', [
        'form_params' => [
            'key'   => env('IMGBB_API_KEY'),
            'image' => base64_encode(file_get_contents($req->file('image')->getRealPath())),
        ],
    ]);

    $url = json_decode($res->getBody(), true)['data']['url'];

    // Find existing banner
    $banner = Banner::first();

    if ($banner) {
        // Update existing banner
        $banner->update([
            'image_url' => $url,
            'title'     => $req->title,
            'subtitle'  => $req->subtitle,
        ]);
    } else {
        // Create new banner
        $banner = Banner::create([
            'image_url' => $url,
            'title'     => $req->title,
            'subtitle'  => $req->subtitle,
        ]);
    }

    return response()->json($banner);
}


    public function show()
    {
        return response()->json(Banner::first());
    }
}
