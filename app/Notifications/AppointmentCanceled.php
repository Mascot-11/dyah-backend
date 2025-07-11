<?php

namespace App\Notifications;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppointmentCanceled extends Notification
{
    use Queueable;

    protected $appointment;
    /**
     * Create a new notification instance.
     */
    public function __construct($appointment)
    {
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->from('no-reply@dyahkhyah.com', 'Dyah Khyah Tattoo')
        ->subject('😢 Your Appointment Has Been Canceled')
        ->greeting('Hello ' . $notifiable->name . ',')
        ->line('We regret to inform you that your appointment with **' . $this->appointment->artist->name . '** has been canceled.')
        ->line('📅 **Date:** ' . Carbon::parse($this->appointment->appointment_datetime)->toFormattedDateString())
        ->line('⏰ **Time:** ' . Carbon::parse($this->appointment->appointment_datetime)->format('h:i A'))
        ->action('📌 View Appointment Details', env('FRONTEND_URL') . "/myappointments")
        ->line('If you have any questions, please don’t hesitate to contact us.')
        ->line('Thank you for choosing Dyah Khyah Tattoo! 😊');
}


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
