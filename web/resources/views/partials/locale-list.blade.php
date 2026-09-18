{{--
    Les mêmes langues, en liste — c'est la forme qu'attend le pied de page public.
--}}
@foreach (config('locales.supported') as $code => $langue)
    <li>
        <a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}"
           href="{{ route('setlocalization', $code) }}">
            <i class="flag-icon {{ $langue['flag'] }}"></i> {{ __($langue['label']) }}
        </a>
    </li>
@endforeach
