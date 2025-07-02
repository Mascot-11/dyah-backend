<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class EventController extends Controller
{

    public function index()
    {
        return response()->json(Event::all());
    }


    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'price' => 'required|numeric|min:0',
            'available_tickets' => 'required|integer|min:1',
            'location' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);


        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $this->uploadImageToImgBB($request->file('image'));
        }


        $event = Event::create([
            'name' => $request->name,
            'description' => $request->description,
            'date' => $request->date,
            'time' => $request->time,
            'price' => $request->price,
            'available_tickets' => $request->available_tickets,
            'location' => $request->location,
            'image_url' => $imageUrl,
        ]);

        return response()->json($event, 201);
    }


    public function show($id)
    {
        return response()->json(Event::findOrFail($id));
    }


    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'date' => 'date',
            'time' => 'string',
            'price' => 'numeric|min:0',
            'available_tickets' => 'integer|min:1',
            'location' => 'string|max:255',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);


        $event = Event::findOrFail($id);


        if ($request->hasFile('image')) {
            $imageUrl = $this->uploadImageToImgBB($request->file('image'));
            $event->image_url = $imageUrl;
        }


        $event->update($request->only([
            'name',
            'description',
            'date',
            'time',
            'price',
            'available_tickets',
            'location',
        ]));

        return response()->json($event);
    }


    public function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return response()->json(['message' => 'Event deleted']);
    }

  
    private function uploadImageToImgBB($image)
    {
        $client = new Client();
        $apiKey = env('IMGBB_API_KEY');

        $imagePath = $image->getRealPath();
        $response = $client->post('https://api.imgbb.com/1/upload', [
            'form_params' => [
                'key' => $apiKey,
                'image' => base64_encode(file_get_contents($imagePath)),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return $data['data']['url'];
    }
}
