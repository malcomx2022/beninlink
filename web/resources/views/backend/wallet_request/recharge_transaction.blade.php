<div class="table-responsive">
    <table class="table" style="width:100%">
        <thead>
            <tr>
                <th>{{ __('levels.id') }}</th>
                <th>{{ __('parcel.merchant') }}</th>
                <th>{{ __('levels.date') }}</th>
                <th>{{ __('parcel.transaction_id') }}</th>
                <th>{{ __('parcel.payment_method') }}</th>
                <th>{{ __('parcel.amount') }}</th>
                <th>{{ __('parcel.status') }}</th>
                <th>{{ __('levels.actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @php
                $i = 0;
            @endphp
            @foreach ($recharge_transactions as $recharge_wallet)
                <tr>
                    <td>{{ ++$i }}</td>
                    <td class="merchantpayment">

                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <img src="{{ $recharge_wallet->user->image }}" alt="user" class="rounded" width="40"
                                    height="40">
                            </div>
                            <div class="col-md-8">
                                <p>{{ $recharge_wallet->merchant->business_name }}</p>
                                <p>{{ $recharge_wallet->merchant->user->mobile }}</p>
                                <p>{{ $recharge_wallet->merchant->address }}</p>
                            </div>
                        </div>

                    </td>
                    <td>
                        {{ dateFormat($recharge_wallet->created_at) }}
                    </td>
                    <td>{{ @$recharge_wallet->transaction_id }}</td>
                    <td>{{ __('WalletPaymentMethod.' . $recharge_wallet->payment_method) }}</td>
                    <td>
                        @if ($recharge_wallet->type == App\Enums\Wallet\WalletType::INCOME)
                            <span class="text-success font-weight-bold"> +
                                {{ settings()->currency }}{{ @$recharge_wallet->amount }}</span>
                        @elseif($recharge_wallet->type == App\Enums\Wallet\WalletType::EXPENSE)
                            <span class="text-danger font-weight-bold"> -
                                {{ settings()->currency }}{{ @$recharge_wallet->amount }}</span>
                        @endif
                    </td>
                    <td>
                        @if (App\Enums\Wallet\WalletType::EXPENSE != $recharge_wallet->type)
                            {!! @$recharge_wallet->my_status !!}
                        @endif
                    </td>
                    @if (hasPermission('wallet_request_approve') ||
                            hasPermission('wallet_request_reject') ||
                            hasPermission('wallet_request_delete'))
                        <td class="wallet-action">
                            <div class="row">
                                <button tabindex="-1" data-toggle="dropdown" type="button"
                                    class="btn btn-primary dropdown-toggle dropdown-toggle-split"><span
                                        class="sr-only">Toggle Dropdown</span></button>
                                <div class="dropdown-menu">
                                    @if (App\Enums\Wallet\WalletType::EXPENSE != $recharge_wallet->type)
                                        @if (@$recharge_wallet->status == \App\Enums\Wallet\WalletStatus::PENDING)
                                            @if (hasPermission('wallet_request_approve'))
                                                <form id="delete"
                                                    data-title="{{ __('parcel.are_you_approve_this_request') }}"
                                                    action="{{ route('wallet.request.approve', $recharge_wallet->id) }}"
                                                    method="post">
                                                    @csrf
                                                    @method('put')
                                                    <button type="submit" title="Approve" class="dropdown-item"><i
                                                            class="fa fa-check  me-2 "></i>
                                                        {{ __('parcel.approve') }}</button>
                                                </form>
                                            @endif
                                            @if (hasPermission('wallet_request_reject'))
                                                <form id="delete"
                                                    action="{{ route('wallet.request.reject', $recharge_wallet->id) }}"
                                                    data-title="{{ __('parcel.are_you_reject_this_request') }}"
                                                    method="post">
                                                    @csrf
                                                    @method('put')
                                                    <button type="submit" class="dropdown-item" title="Reject"><i
                                                            class="fa fa-close me-2 "></i>
                                                        {{ __('parcel.reject') }}</button>
                                                </form>
                                            @endif
                                        @endif

                                        @if (hasPermission('wallet_request_delete'))
                                            <form id="delete" data-title="{{ __('parcel.delete_wallet') }}"
                                                action="{{ route('wallet.request.delete', $recharge_wallet->id) }}"
                                                method="post">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" title="Delete" class="dropdown-item"><i
                                                        class="fa fa-trash me-2"></i>{{ __('levels.delete') }}</button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                        </td>
                    @endif

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
