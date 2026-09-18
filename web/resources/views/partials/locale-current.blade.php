{{--
    Drapeau et nom de la langue courante.

    Le socle répétait une cascade de sept @if/@elseif — une par langue — dans
    CHACUN des trois bandeaux, deux fois chacun. Six copies à maintenir d'accord.
    La liste vit maintenant dans config/locales.php, et ce partiel la lit.
--}}
@php($courante = config('locales.supported')[app()->getLocale()] ?? null)
@if ($courante)
    <i class="flag-icon {{ $courante['flag'] }}"></i> {{ __($courante['label']) }}
@endif
