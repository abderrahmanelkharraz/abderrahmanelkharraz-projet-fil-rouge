<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Employer;
use App\Models\Payment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceEmail;

class PaymentController extends Controller
{

    public function index()
    {

        $defaultPaymentDateQuery = Configuration::where('type', 'PAYMENT_DATE')->first();
        $defaultPaymentDate = $defaultPaymentDateQuery->value;
        $convertedPaymentDate = intval($defaultPaymentDate);
        $today = date('d');

        $isPaymentDay = false;

        if ($today == $convertedPaymentDate) {
            $isPaymentDay = true;
        }


        $payments = Payment::latest()->orderBy('id', 'desc')->paginate(10);
        return view('paiements.index', compact('payments', 'isPaymentDay'));
    }

    public function sendInvoices()
    {
        $monthMapping = [
            'JANUARY' => 'JANVIER',
            'FEBRUARY' => 'FEVRIER',
            'MARCH' => 'MARS',
            'APRIL' => 'AVRIL',
            'MAY' => 'MAI',
            'JUNE' => 'JUIN',
            'JULY' => 'JUILLET',
            'AUGUST' => 'AOUT',
            'SEPTEMBER' => 'SEPTEMBRE',
            'OCTOBER' => 'OCTOBRE',
            'NOVEMBER' => 'NOVEMBRE',
            'DECEMBER' => 'DECEMENBRE'
        ];
        $currentMonth = strtoupper(Carbon::now()->formatLocalized('%B'));
        $currentMonthInFrench = $monthMapping[$currentMonth] ?? '';
        $currentYear = Carbon::now()->format('Y');

        $payments = Payment::where('month', $currentMonthInFrench)
            ->where('year', $currentYear)
            ->where('status', 'SUCCESS')
            ->with('employer')
            ->get();
// dd($payments);
        foreach ($payments as $payment) {
            $fullPaymentInfo = Payment::with('employer')->find($payment->id);
            $pdf = PDF::loadView('facture', compact('fullPaymentInfo'));
            $pdfData = $pdf->output();
            $pdf->download('facture.pdf');
            // Send email with invoice PDF attached
            Mail::to($payment->employer->email)
                ->send(new InvoiceEmail($pdfData)); // Assuming you have defined the InvoiceEmail Mailable

            // You can also delete the PDF file after sending if you don't need it anymore
            // Optional: unlink(public_path('facture.pdf'));
        }

        return redirect()->back()->with('success_message', 'Invoices sent successfully.');
    }

    public function initPayment()
    {
        $monthMapping = [
            'JANUARY' => 'JANVIER',
            'FEBRUARY' => 'FEVRIER',
            'MARCH' => 'MARS',
            'APRIL' => 'AVRIL',
            'MAY' => 'MAI',
            'JUNE' => 'JUIN',
            'JULY' => 'JUILLET',
            'AUGUST' => 'AOUT',
            'SEPTEMBER' => 'SEPTEMBRE',
            'OCTOBER' => 'OCTOBRE',
            'NOVEMBER' => 'NOVEMBRE',
            'DECEMBER' => 'DECEMENBRE'
        ];

        $currentMonth = strtoupper(Carbon::now()->formatLocalized('%B'));

        //Mois en cour en francais
        $currentMonthInFrench = $monthMapping[$currentMonth] ?? '';
        //Année en cour

        $currentYear = Carbon::now()->format('Y');


        //Simuler des paiements pour tous les employers dans le mois en cour. Les paiement concerne les employer qui n'ont pas encore été payé dans le mois actuel.


        //Recuperer la liste des employer qui n'ont pas encore été payé pour le mois en cour.

        $employers = Employer::whereDoesntHave('payments', function ($query) use ($currentMonthInFrench, $currentYear) {
            $query->where('month', '=', $currentMonthInFrench)
                ->where('year', '=', $currentYear);
        })->get();

        if ($employers->count() === 0) {
            return redirect()->back()->with('error_message', 'Tous vos employer ont été payés pour ce mois ' . $currentMonthInFrench);
        }
        //Faire les paiement pour ces employers

        foreach ($employers as $employer) {

            $aEtePayer =  $employer->payments()->where('month', '=', $currentMonthInFrench)->where('year', '=', $currentYear)->exists();

            if (!$aEtePayer) {
                $salaire = $employer->montant_journalier * 31;

                $payment = new Payment([
                    'reference' => strtoupper(Str::random(10)),
                    'employer_id' => $employer->id,
                    'amount' => $salaire,
                    'launch_date' => now(),
                    'done_time' => now(),
                    'status' => 'SUCCESS',
                    'month' => $currentMonthInFrench,
                    'year' => $currentYear
                ]);

                $payment->save();
            }
        }

        $this->sendInvoices();

        return redirect()->back()->with('success_message', 'Paiement des employers effectuer pour le mois de ' . $currentMonthInFrench);
    }

    public function download(Payment $payment)
    {
        try {

            $fullPaymentInfo = Payment::with('employer')->find($payment->id);
            //return view('facture', compact('fullPaymentInfo'));
            $pdf = PDF::loadView('facture', compact('fullPaymentInfo'));
            return $pdf->download('facture.pdf');
        } catch (Exception $e) {
            dd($e);
        }
    }
}
