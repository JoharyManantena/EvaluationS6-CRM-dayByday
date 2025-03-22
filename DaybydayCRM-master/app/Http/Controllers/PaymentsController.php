<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PaymentRequest;
use App\Models\Integration;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Invoice\GenerateInvoiceStatus;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Implémentation de la liste des paiements si nécessaire
    }

    public function showPaymentForm(Invoice $invoice)
    {
        // Vérifier que la facture a bien été envoyée
        if (!$invoice->isSent()) {
            session()->flash('flash_message_warning', __("Cette facture n'est pas encore envoyée, vous ne pouvez pas ajouter un paiement."));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        // Calcul du total payé et du solde restant
        $totalInvoice = $invoice->totalPrice->getAmount(); // Total de la facture
        $totalPayments = $invoice->payments()->sum('amount'); // Total des paiements
        $remaining = $totalInvoice - $totalPayments; // Solde restant

        // Retourne la vue avec les données nécessaires
        return view('payments.create', compact('invoice', 'remaining'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Payment $payment
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Payment $payment)
    {
        if (!auth()->user()->can('payment-delete')) {
            session()->flash('flash_message', __("You don't have permission to delete a payment"));
            return redirect()->back();
        }
        $api = Integration::initBillingIntegration();
        if ($api) {
            $api->deletePayment($payment);
        }

        $payment->delete();
        session()->flash('flash_message', __('Payment successfully deleted'));
        return redirect()->back();
    }
     
    public function addPayment(PaymentRequest $request, Invoice $invoice)
    {
        // Vérifier que la facture a bien été envoyée
        if (!$invoice->isSent()) {
            session()->flash('flash_message_warning', __("Can't add payment on Invoice"));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        // Calcul du montant total de la facture (en centimes)
        $totalInvoice = $invoice->totalPrice->getAmount();
        $totalPayments = $invoice->payments()->sum('amount'); // Total des paiements déjà effectués
        $remaining = $totalInvoice - $totalPayments; // Solde restant

        // Le montant du paiement saisi (converti en centimes)
        $paymentAmount = $request->amount * 100;

        // Vérification que le montant saisi ne dépasse pas le solde restant
        if ($paymentAmount > $remaining) {
            session()->flash('flash_message_warning', __("Le montant du paiement dépasse le solde restant de la facture. Solde restant : :remaining", ['remaining' => number_format($remaining / 100, 2)]));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        // Création du paiement
        $payment = Payment::create([
            'external_id' => Uuid::uuid4()->toString(),
            'amount' => $paymentAmount,
            'payment_date' => Carbon::parse($request->payment_date),
            'payment_source' => $request->source,
            'description' => $request->description,
            'invoice_id' => $invoice->id,
        ]);

        // Intégration avec le système de facturation externe si applicable
        $api = Integration::initBillingIntegration();
        if ($api && $invoice->integration_invoice_id) {
            $result = $api->createPayment($payment);
            $payment->integration_payment_id = $result["Guid"];
            $payment->integration_type = get_class($api);
            $payment->save();
        }

        // Mise à jour du statut de la facture après ajout du paiement
        app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();

        session()->flash('flash_message', __('Payment successfully added'));
        return redirect()->back();
    }


}
