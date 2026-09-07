<section id="pricing" class="container-fluid   py-3 pb-0">
    <div class="container  pb-5">
        <div class="row  mb-3">
            <div class="col-lg-8 m-auto">
                <h3 class="display-6 title text-center mb-5"><span class="section-title">{{ settings()->name }} {{ __('levels.pricing') }}</span></h3>
            </div>
        </div>

        {{--
            D4, étape 6 — un onglet par **zone**, plus un par colonne héritée.
            Les quatre colonnes mélangeaient un délai et un périmètre ; la page
            publique affichait donc « jour même » à côté de « hors ville »
            comme s'il s'agissait de quatre offres comparables. Elle montre
            désormais ce que le barème dit vraiment : un prix par zone et par
            tranche. Le supplément de délai est global et s'ajoute par-dessus.
        --}}
        @php($parZone = collect($pricing)->filter(fn ($ligne) => $ligne->zone_id)->groupBy('zone_id'))

        @if($parZone->isEmpty())
            <div class="row py-2">
                <div class="col-12 text-center">
                    <p class="mb-0">{{ __('delivery_zone.aucune_grille') }}</p>
                </div>
            </div>
        @else
            <div class="row py-2 align-items-center">
                <div class="col-12 " aria-label="breadcrumb">
                    <ul class="nav nav-pills pricing justify-content-center mb-5 breadcrumb" id="pills-tab" role="tablist">
                        @foreach($parZone as $zoneId => $lignes)
                            <li class="nav-item breadcrumb-item" role="presentation">
                                <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                        id="pills-zone-{{ $zoneId }}-tab" data-bs-toggle="pill"
                                        data-bs-target="#pills-zone-{{ $zoneId }}" type="button" role="tab"
                                        aria-controls="pills-zone-{{ $zoneId }}"
                                        aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $lignes->first()->zone?->name }}</button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content charge-content" id="pills-tabContent">
                        @foreach($parZone as $zoneId => $lignes)
                            <div class="tab-pane {{ $loop->first ? 'show active' : '' }}" id="pills-zone-{{ $zoneId }}"
                                 role="tabpanel" aria-labelledby="pills-zone-{{ $zoneId }}-tab" tabindex="0">
                                <div class="row justify-content-center">
                                    @foreach($lignes as $ligne)
                                        <div class="col-sm-4 col-lg-2 charge-col ">
                                            <div class="text-center charge-item">
                                                <div class="row align-items-center">
                                                    <p class="mb-0">{{ __('levels.up_to') }} {{ $ligne->weight }} ( {{ @$ligne->category->title }} )</p>
                                                    <h3 class="font-weight-bold ">{{ formatAmount($ligne->amount) }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
