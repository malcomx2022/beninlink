<div class="table-responsive">
    <table class="table   " style="width:100%">
        <thead>
            <tr>

                <th>{{ __('levels.id') }}</th>
                <th>{{ __('parcel.source') }}</th>
                <th>{{ __('levels.date') }}</th>
                <th>{{ __('parcel.transaction_id') }}</th>
                <th>{{ __('parcel.payment_method') }}</th>
                <th>{{ __('parcel.amount') }}</th>
                <th>{{ __('parcel.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i = 0;
            @endphp
            @foreach ($recharge_transactions as $recharge_wallet)
                <tr>
                    <td>{{ ++$i }}</td>
                    <td>{{ $recharge_wallet->source }}</td>
                    <td> {{ dateFormat($recharge_wallet->created_at) }} </td>
                    <td>{{ @$recharge_wallet->transaction_id }}</td>
                    <td>{{ __('WalletPaymentMethod.' . $recharge_wallet->payment_method) }}</td>
                    <td>
                        @if ($recharge_wallet->type == App\Enums\Wallet\WalletType::INCOME)
                            <span class="text-success font-weight-bold"> +
                                {{ settings()->currency }}{{ @$recharge_wallet->amount }}</span>
                        @elseif($recharge_wallet->type == App\Enums\Wallet\WalletType::EXPENSE)
                            <span
                                class="text-danger font-weight-bold">{{ settings()->currency }}{{ @$recharge_wallet->amount }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($recharge_wallet->type == App\Enums\Wallet\WalletType::INCOME)
                            {!! @$recharge_wallet->my_status !!}
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr>
        </tbody>
    </table>
</div>
<div class="px-3 d-flex flex-row-reverse align-items-center">
    <span>{{ $recharge_transactions->links() }}</span>
    <p class="p-2 small">
        {!! __('Showing') !!}
        <span class="font-medium">{{ $recharge_transactions->firstItem() }}</span>
        {!! __('to') !!}
        <span class="font-medium">{{ $recharge_transactions->lastItem() }}</span>
        {!! __('of') !!}
        <span class="font-medium">{{ $recharge_transactions->total() }}</span>
        {!! __('results') !!}
    </p>
</div>
