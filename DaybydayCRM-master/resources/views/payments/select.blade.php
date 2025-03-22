@extends('layouts.master')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="tablet tablet--tabs tablet--height-fluid">
                <div class="tablet__head">
                    <div class="tablet__head-toolbar">
                        @lang('Before proceeding with the invoice or payment validation, please select a client and then their invoice.')
                    </div>
                </div>
                <div class="tablet__body">
                    @if(isset($clients))
                        <table class="table table-hover" id="clients-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Client Name') }}</th>
                                    <th>{{ __('Factures') }}</th> <!-- Ajout d'une colonne pour indiquer s'il y a des factures -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clients as $client)
                                    <tr>
                                        <td>
                                            <a href="{{ route('payments.selectInvoice', $client) }}">
                                                {{ $client->name ?? 'Client #' . $client->id }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($client->hasInvoices)
                                                <!-- Badge avec couleur verte si le client a des factures -->
                                                <span class="badge badge-success" style="background-color: #28a745;">{{ __('A des factures') }}</span>
                                            @else
                                                <!-- Badge avec couleur rouge si le client n'a pas de factures -->
                                                <span class="badge badge-danger" style="background-color: #dc3545;">{{ __('Pas de factures') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Liens de pagination -->
                        <div class="pagination">
                            {{ $clients->links() }}
                        </div>
                    @elseif(isset($client) && isset($invoices))
                        <h1>Factures de {{ $client->name ?? 'Client #' . $client->id }}</h1>
                        @if($invoices->isEmpty())
                            <p>Aucune facture trouvée pour ce client.</p>
                        @else
                            <table class="table table-hover" id="invoices-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Invoice number') }}</th>
                                        <th>{{ __('Due date') }}</th>
                                        <th>{{ __('Amount') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('payments.form', $invoice->external_id) }}">
                                                    Facture N°{{ $invoice->invoice_number }} - Statut : {{ $invoice->status }}
                                                </a>
                                            </td>
                                            <td>
                                                {{ optional($invoice->due_at)->format(carbonDateWithText()) }}
                                            </td>
                                            <td>{{ formatMoney($invoice->totalPrice) }}</td>
                                            <td><a href="#">@lang('View')</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                        <br>
                        <a href="{{ route('payments.selectClient') }}" class="btn btn-secondary">@lang('Retour à la sélection des clients')</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
