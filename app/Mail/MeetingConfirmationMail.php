<?php

namespace App\Mail;

use App\Models\MeetingBooking;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public MeetingBooking $booking;
    public string $formattedDate;
    public string $cancelUrl;

    public function __construct(MeetingBooking $booking)
    {
        $this->booking = $booking;
        $this->formattedDate = Carbon::parse($booking->meeting_date)
            ->locale('pt_BR')
            ->isoFormat('dddd, D [de] MMMM [de] YYYY');
        $this->cancelUrl = route('booking.cancel', $booking->confirmation_token);
    }

    public function build(): static
    {
        return $this
            ->subject("✅ Reunião confirmada – {$this->formattedDate} às {$this->booking->meeting_time}")
            ->view('emails.meeting_confirmation');
    }
}
