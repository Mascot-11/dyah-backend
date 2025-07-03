<?php

namespace App\Http\Controllers;

use App\Models\TattooGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TattooGalleryController extends Controller
{

    public function __construct()
    {

    }

       public function index()
    {
        $images = TattooGallery::all();
        return response()->json($images);
    }


    public function store(Request $request)
{
    if (auth()->user()->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $validator = Validator::make($request->all(), [
        'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        'description' => 'nullable|string|max:1000',
        'artist_id' => 'required|exists:users,id', // validate artist_id exists in users table
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $imagePath = $this->uploadImageToImgBB($request->file('image'));

    if ($imagePath === '') {
        return response()->json(['message' => 'Image upload failed'], 500);
    }

    $tattooGallery = TattooGallery::create([
        'image_url' => $imagePath,
        'description' => $request->description,
        'artist_id' => $request->artist_id,
    ]);

    return response()->json($tattooGallery, 201);
}



    public function destroy($id)
    {
        // Check if user is admin
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tattooGallery = TattooGallery::find($id);

        if (!$tattooGallery) {
            return response()->json(['message' => 'Image not found'], 404);
        }


        $tattooGallery->delete();
        return response()->json(['message' => 'Image deleted successfully']);
    }


    private function uploadImageToImgBB($image)
    {
        $apiKey = config('services.imgbb.key');



        if (!$apiKey) {

            return '';
        }


        $imageData = file_get_contents($image->getRealPath());


        return $this->uploadToImgBBApi($imageData, $apiKey);
    }


    Private function uploadToImgBBApi($imageData, $apiKey)
{
    $url = 'https://api.imgbb.com/1/upload';

    $data = [
        'key' => $apiKey,
        'image' => base64_encode($imageData),
    ];

    try {
        $response = Http::asForm()->post($url, $data);
        $responseData = json_decode($response->getBody()->getContents(), true);

        if ($response->successful() && isset($responseData['data']['url'])) {
            return $responseData['data']['url'];
        } else {
            // Log the full response for debugging
            Log::error('ImgBB upload failed: ' . $response->body());
        }
    } catch (\Exception $e) {
        // Log the exception message and trace
        Log::error('Exception during ImgBB upload: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
    }

    return ''; // return empty string if failed
}
    }

