<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AppointmentConfirmed;
use App\Notifications\AppointmentCanceled;
use Twilio\Rest\Client;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function getArtists()
    {
        try {
            $artists = User::where('role', 'tattoo_artist')->get(['id', 'name']);

            if ($artists->isEmpty()) {
                return response()->json(['message' => 'No artists found.'], 404);
            }

            return response()->json($artists);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching artists.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bookAppointment(Request $request)
    {
        try {
            $validated = $request->validate([
                'artist_id' => 'required|exists:users,id',
                'appointment_datetime' => [
                    'required',
                    'date',
                    'after:now',
                    function ($attribute, $value, $fail) {
                        if (strtotime($value) < strtotime(date('Y-m-d'))) {
                            $fail('The appointment date cannot be in the past.');
                        }
                    },
                ],
                'phone_number' => [
                    'required',
                    'digits:10',
                    'regex:/^[0-9]{10}$/',
                ],
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $existingAppointment = Appointment::where('artist_id', $validated['artist_id'])
                ->where('appointment_datetime', $validated['appointment_datetime'])
                ->exists();

            if ($existingAppointment) {
                return response()->json([
                    'message' => 'The artist is already booked for this date and time.',
                    'errors' => [
                        'artist_id' => ['The selected artist is unavailable for the chosen time.']
                    ]
                ], 422);
            }

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imageFile = $request->file('image');
                $imageUrl = $this->uploadImageToImgBB($imageFile);

                if (!$imageUrl) {
                    return response()->json([
                        'message' => 'Failed to upload the image. Please try again later.',
                    ], 500);
                }
            }

            $appointment = Appointment::create([
                'user_id' => auth()->id(),
                'artist_id' => $validated['artist_id'],
                'appointment_datetime' => $validated['appointment_datetime'],
                'description' => $validated['description'] ?? '',
                'phone_number' => $validated['phone_number'],
                'image_url' => $imageUrl,
                'status' => 'pending',
            ]);
            $userName = $appointment->user->name ?? 'Customer';
            $artistName = $appointment->artist->name ?? 'Artist';
            $phone = $validated['phone_number'];

            $date = Carbon::parse($appointment->appointment_datetime)->format('F j, Y');
            $time = Carbon::parse($appointment->appointment_datetime)->format('g:i A');

            $message = "Dear {$userName},\n\n✅ Your appointment with *{$artistName}* on *{$date}* at *{$time}* has been *booked*. Please wait for confirmation.\n\n_Dyah Khyah Tattoo_";

            $this->sendWhatsAppMessage($phone, $message);


            return response()->json([
                'message' => 'Appointment created successfully.',
                'appointment' => $appointment
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error occurred.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred. Please try again later.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function uploadImageToImgBB($imageFile)
    {
        $apiKey = env('IMGBB_API_KEY');
        $url = 'https://api.imgbb.com/1/upload?key=' . $apiKey;

        $response = Http::attach('image', file_get_contents($imageFile), $imageFile->getClientOriginalName())
            ->post($url);

        $data = $response->json();

        if ($response->successful() && isset($data['data']['url'])) {
            return $data['data']['url'];
        }

        return null;
    }

    public function getUserAppointments()
    {
        try {
            $appointments = Appointment::where('user_id', auth()->id())
                ->with('artist')
                ->get();

            if ($appointments->isEmpty()) {
                return response()->json(['message' => 'No appointments found for this user.'], 404);
            }

            $appointments = $appointments->map(function ($appointment) {
                $appointment->artist_name = $appointment->artist ? $appointment->artist->name : 'Unknown Artist';
                $appointment->image_url = $appointment->image_url ?? null;
                return $appointment;
            });

            return response()->json($appointments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching user appointments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAllAppointments()
    {
        try {
            if (auth()->user()->role !== 'admin') {
                return response()->json(['message' => 'You do not have permission to view all appointments.'], 403);
            }

            $appointments = Appointment::with(['user', 'artist'])->get();

            if ($appointments->isEmpty()) {
                return response()->json(['message' => 'No appointments found.'], 404);
            }

            return response()->json($appointments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching all appointments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateAppointmentStatus(Request $request, $id)
{
    try {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'You do not have permission to update the appointment status.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,canceled',
        ]);

        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        // Send email notification
        if ($validated['status'] === 'confirmed') {
            $appointment->user->notify(new AppointmentConfirmed($appointment));
        } elseif ($validated['status'] === 'canceled') {
            $appointment->user->notify(new AppointmentCanceled($appointment));
        }

        // Update appointment status
        $appointment->update(['status' => $validated['status']]);

    // ✅ Send WhatsApp message
    $phone = $appointment->phone_number;
    $userName = $appointment->user->name ?? 'Customer';
    $artistName = $appointment->artist->name ?? 'Artist';

    $date = Carbon::parse($appointment->appointment_datetime)->format('F j, Y');
    $time = Carbon::parse($appointment->appointment_datetime)->format('g:i A');

    if ($validated['status'] === 'confirmed') {
        $message = "Dear {$userName},\n\n🎉 Your appointment with *{$artistName}* on *{$date}* at *{$time}* has been *confirmed*! Thank you for booking.\n\n_Dyah Khyah Tattoo_";
        $this->sendWhatsAppMessage($phone, $message);
    } elseif ($validated['status'] === 'canceled') {
        $message = "Dear {$userName},\n\n❌ Your appointment with *{$artistName}* on *{$date}* at *{$time}* has been *canceled*. Please try booking a different time.\n\n_Dyah Khyah Tattoo_";
        $this->sendWhatsAppMessage($phone, $message);
    }




        return response()->json(['message' => 'Appointment status updated', 'appointment' => $appointment]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while updating the appointment status.',
            'error' => $e->getMessage(),
        ], 500);
    }
}


    public function deleteAppointment($id)
    {
        try {
            if (auth()->user()->role !== 'admin') {
                return response()->json(['message' => 'You do not have permission to delete the appointment.'], 403);
            }

            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Appointment not found.'], 404);
            }

            $appointment->delete();

            return response()->json(['message' => 'Appointment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the appointment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getArtistAppointments()
    {
        try {
            if (auth()->user()->role !== 'tattoo_artist') {
                return response()->json(['message' => 'You are not authorized to view artist appointments.'], 403);
            }

            $appointments = Appointment::where('artist_id', auth()->id())->get();

            if ($appointments->isEmpty()) {
                return response()->json(['message' => 'No appointments found for this artist.'], 404);
            }

            return response()->json($appointments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching artist appointments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    private function sendWhatsAppMessage($toPhoneNumber, $message)
{
    try {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            Log::error('Twilio config missing: ', compact('sid', 'token', 'from'));
            return;
        }

        $twilio = new Client($sid, $token);

        $twilio->messages->create("whatsapp:+977" . $toPhoneNumber, [
            'from' => $from,
            'body' => $message,
        ]);
    } catch (\Exception $e) {
        Log::error('WhatsApp Message Error: ' . $e->getMessage());
    }
}

}
