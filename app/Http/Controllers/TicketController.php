<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Mail\TicketMail;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    public function getUserPayments($userId)
{
    $user = User::findOrFail($userId);
    $payments = Payment::where('user_id', $user->id)->get();

    return response()->json([
        'user' => $user->name,
        'payments' => $payments
    ]);
}

public function getAllPayments()
{
    $payments = Payment::with('user', 'event')->get();

    return response()->json([
        'payments' => $payments
    ]);
}

    public function purchase(Request $request)
    {
        $eventData = $request->validate([
            'quantity' => 'required|integer|min:1',
            'event_name' => 'required|string',
            'event_price' => 'required|numeric',
            'user_name' => 'required|string',
            'event_id' => 'required|exists:events,id',
        ]);

        $totalAmount = $eventData['event_price'] * $eventData['quantity'] * 100;

        $fields = array(
            "return_url" => "http://localhost:5173/music",
            "website_url" => "http://localhost:5173/",
            "amount" => $totalAmount,
            "purchase_order_id" => $eventData['event_id'],
            "purchase_order_name" => $eventData['event_name'],

            "customer_info" => array(
                "name" => $eventData['user_name'],
            )
        );

        $postfields = json_encode($fields);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://dev.khalti.com/api/v2/epayment/initiate/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postfields,
            CURLOPT_HTTPHEADER => array(
                'Authorization: key 1bee9fe34f384f73a9dcc1d98dbf844a',
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $responseArray = json_decode($response, true);

        if (isset($responseArray['payment_url'])) {
            return response()->json([
                'khalti_url' => $responseArray['payment_url']
            ]);
        } else {
            return response()->json(['error' => 'Unexpected response'], 500);
        }
    }

    public function handleKhaltiCallback(Request $request)
    {
        try {



            if ($request->has('status') && $request->status === 'Completed') {
                $eventId = $request->purchase_order_id;
                $event = Event::findOrFail($eventId);
                $ticket_price = $event->price;


                $total_amount = $request->total_amount / 100;
                $quantity = $total_amount / $ticket_price;

                $event->available_tickets -= $quantity;


                $userData = User::findOrFail($request->user_id);


                $payment = Payment::create([
                    'user_id' => $userData->id,
                    'event_id' => $eventId,
                    'price' => $ticket_price,
                    'quantity' => $quantity,
                    'total_amount' => $total_amount,
                    'transaction_id' => $request->transaction_id,
                    'payment_method' => 'Khalti',
                    'status' => $request->status
                ]);
                $payment->save();


                $event->save();


                $pdf = Pdf::loadView('pdf.ticket', compact('payment', 'userData'));
                $pdfDirectory = storage_path("app/public/tickets/");


                if (!File::exists($pdfDirectory)) {
                    File::makeDirectory($pdfDirectory, 0777, true, true);
                }
                $pdfPath = $pdfDirectory . "ticket_{$request->transaction_id}.pdf";
                $pdf->save($pdfPath);


                Mail::to($userData->email)->send(new TicketMail($payment, $pdfPath, $userData));

                return response()->json([
                    'message' => 'Transaction completed successfully, Payment for event confirmed',
                    'user' => $userData,
                    'event' => $event,
                    'paymentDetails' => $payment
                ]);

            }
            return response()->json([
                'message' => 'Transaction failed, Payment for event not confirmed',
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Payment failed'], 400);
        }
    }





    public function getTicketDetails($ticketId)
    {
        $ticket = Ticket::with('event')->findOrFail($ticketId);
        return response()->json(['ticket' => $ticket]);
    }


}
