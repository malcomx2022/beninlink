{{-- S94 — les alertes douanières d'un colis, sur sa fiche (back-office et panneau marchand).
     L'app les montre depuis S82 (`customs_alerts` sur `parcel/details`) ; la fiche web ne les montrait pas.
     `$alertesDouanieres` vient du colis DÉJÀ vérifié par le dépôt : ses alertes le suivent.
     `$peutTraiter` : seul le back-office (parcel_update) marque une alerte traitée ; le panneau marchand lit. --}}
@if ($alertesDouanieres->isNotEmpty())
<div class="col-12">
    <div class="card border-{{ $alertesDouanieres->contains(fn ($a) => $a->level == 3) ? 'danger' : 'warning' }}" id="alertes-douanieres-colis">
        <div class="card-header">
            <p class="h4 mb-0">{{ __('customs.title') }}</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('customs.level') }}</th>
                            <th>{{ __('customs.country') }}</th>
                            <th>{{ __('customs.category') }}</th>
                            <th>{{ __('customs.required_document') }}</th>
                            <th>{{ __('levels.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($alertesDouanieres as $alerte)
                        <tr>
                            <td>
                                <span class="badge badge-{{ $alerte->level == 3 ? 'danger' : ($alerte->level == 2 ? 'warning' : 'info') }}">{{ $alerte->level_name }}</span>
                            </td>
                            <td>{{ $alerte->country_name }} ({{ $alerte->country_code }})</td>
                            <td>{{ __('customs.category_' . $alerte->goods_category) }}</td>
                            <td>
                                {{ $alerte->required_document }}
                                @if ($alerte->message)
                                    <br><small class="text-muted">{{ $alerte->message }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($alerte->status == \App\Enums\CustomsAlertStatus::PENDING)
                                    <span class="badge badge-secondary">{{ __('customs.pending') }}</span>
                                    @if ($peutTraiter)
                                        <form action="{{ route('customs.alerts.resolve', $alerte->id) }}" method="POST" class="d-inline ml-2">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-outline-success">{{ __('customs.resolved') }}</button>
                                        </form>
                                    @endif
                                @else
                                    <span class="badge badge-success">{{ __('customs.resolved') }}</span>
                                    <br><small class="text-muted">{{ dateFormat($alerte->resolved_at) }}</small>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif
