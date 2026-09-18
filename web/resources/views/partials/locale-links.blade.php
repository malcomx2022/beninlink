{{--
    Les langues servies, en entrées de menu déroulant.

    Sept liens × six emplacements = 42 liens recopiés dans le socle, dont cinq
    langues incomplètes ou hors sujet. La liste est désormais unique :
    config/locales.php. Y rebrancher une langue demande de compléter ses fichiers
    de langue d'abord — LocalizationController refuse ce qui n'y est pas déclaré.
--}}
@foreach (config('locales.supported') as $code => $langue)
    <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}"
       href="{{ route('setlocalization', $code) }}">
        <i class="flag-icon {{ $langue['flag'] }}"></i> {{ __($langue['label']) }}
    </a>
@endforeach
