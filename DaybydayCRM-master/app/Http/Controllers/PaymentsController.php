<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PaymentRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Integration;
use App\Services\Invoice\GenerateInvoiceStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;

class PaymentsController extends Controller
{
    /**
     * Affiche la liste des clients pour sélectionner celui dont on souhaite gérer les paiements.
     */
    public function selectClient()
    {
        $clients = Client::paginate(10);

        foreach ($clients as $client) {
            $client->hasInvoices = $client->invoices()->exists();
        }

        return view('payments.select', compact('clients'));
    }

    
    /**
     * Affiche la liste des factures (Invoices) d'un client donné.
     *
     * @param \App\Models\Client $client
     */
    public function selectInvoice(Client $client)
    {
        $invoices = Invoice::where('client_id', $client->id)->get();
        return view('payments.select', compact('client', 'invoices'));
    }

    /**
     * Affiche le formulaire de paiement pour une facture donnée.
     *
     * @param \App\Models\Invoice $invoice
     */
    public function showPaymentForm(Invoice $invoice)
    {
        if (!$invoice->isSent()) {
            session()->flash('flash_message_warning', __("Cette facture n'est pas encore envoyée, vous ne pouvez pas ajouter un paiement."));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        // Calcul du total payé et du solde restant
        $totalInvoice = $invoice->totalPrice->getAmount(); // Total de la facture
        $totalPayments = $invoice->payments()->sum('amount'); // Total des paiements
        $remaining = $totalInvoice - $totalPayments; // Solde restant

        return redirect()->route('invoices.show', $invoice->external_id);
            // ->with('flash_message', __('Vous avez été redirigé vers la page de la facture.'));
    }


    /**
     * Ajoute un paiement à une facture.
     *
     * @param \App\Http\Requests\Payment\PaymentRequest $request
     * @param \App\Models\Invoice $invoice
     */
    public function addPayment(PaymentRequest $request, Invoice $invoice)
    {
        // Vérifier que la facture a bien été envoyée
        if (!$invoice->isSent()) {
            session()->flash('flash_message_warning', __("Can't add payment on Invoice"));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        $totalInvoice = $invoice->totalPrice->getAmount();
        $totalPayments = $invoice->payments()->sum('amount'); // Total des paiements déjà effectués
        $remaining = $totalInvoice - $totalPayments; // Solde restant

        // Le montant du paiement saisi (converti en centimes)
        $paymentAmount = $request->amount * 100;


        if ($paymentAmount <= 0) {
            session()->flash('flash_message_warning', __("Le montant du paiement doit être supérieur à 0"));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        // Requis V2:
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

    /**
     * Supprime un paiement.
     *
     * @param \App\Models\Payment $payment
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
}
