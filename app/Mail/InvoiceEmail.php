<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdfData;

    public function __construct($pdfData)
    {
        $this->pdfData = $pdfData;
    }

    public function build()
    {
        return $this->view('emails.invoice')
            ->attachData($this->pdfData, 'invoice.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
