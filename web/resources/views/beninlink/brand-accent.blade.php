{{--
    Lot 3 de la charte web — l'ocre du transporteur, injecté dans `:root`.

    Inclus par les DEUX mises en page (site public et back-office), après les
    feuilles de la charte : ce bloc a donc le dernier mot, comme le bloc
    `--bs-primary` du socle l'a déjà pour la couleur primaire.

    Ce fichier ne décide de rien : `App\Services\Brand\AccentColor` calcule les
    jetons, lui les écrit. En particulier :

    - le transporteur est resté sur l'ocre de la charte → `jetons()` rend un
      tableau vide et RIEN n'est émis. `tokens.css` continue de servir ses
      quatre valeurs mesurées à la main. Une installation par défaut ne paie pas
      un octet pour ce lot ;
    - le transporteur a choisi sa couleur → les trois jetons dérivés sont
      RECALCULÉS avec elle. Laisser `--bl-on-accent` fixe aurait posé l'encre
      brune de l'ocre sur, mettons, un bleu marine.

    Les valeurs sortent de `AccentColor::normalise()` : ce sont des `#RRGGBB` ou
    rien. Un nom de jeton vient des clés de la classe, jamais d'une saisie.
--}}
@php($blJetons = \App\Services\Brand\AccentColor::jetons(settings()?->accent_color))
@if(filled($blJetons))
    <style>
        :root{
            @foreach($blJetons as $blNom => $blValeur){{ $blNom }}: {{ $blValeur }};
            @endforeach
        }
    </style>
@endif
