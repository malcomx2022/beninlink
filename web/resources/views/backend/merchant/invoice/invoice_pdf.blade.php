{{--
    Vue héritée du socle et rendue nulle part (bloc G de `web/CARTOGRAPHIE.md` :
    « ORPHELINE — référencée nulle part »). Elle portait une erreur fatale — deux
    constantes, `ParcelStatus::RETURN_TRANSFER_BY_HUB` et `RETURN_RECEIVED_PARCEL`,
    qui n'existent pas dans l'énumération — et une quatrième mise en page du relevé,
    en anglais, en taka, en violet (S69, lot de nettoyage T1).

    Elle est conservée (règle du projet : 0 fichier supprimé du socle) et DÉLÈGUE :
    qui la rend avec `$invoice` obtient le relevé officiel du chantier 4
    (`backend.invoice.statement_pdf`, via `SettlementStatement`), pas une variante.
    Le document n'existe qu'en un seul exemplaire, et c'est lui qui est testé.
--}}
@php($statement = $statement ?? \App\Services\Invoicing\SettlementStatement::for($invoice))
@include('backend.invoice.statement_pdf', ['statement' => $statement])
