<?php

namespace App\Traits;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;

/**
 * Enveloppe de l'API `/api/v10` : `{success, message, data}`.
 *
 * **S78 (T8)** ajoute `responseWithPage()` : la même enveloppe, plus un bloc
 * `page` à la racine quand la charge utile vient d'un paginateur. Avant lui, le
 * paginateur imbriqué dans `data` perdait ses compteurs à la sérialisation, et
 * les apps déduisaient « il en reste » d'une page pleine — un contrat implicite
 * que rien dans la réponse ne portait (`MerchantAppCustomsContractTest`).
 *
 * `page` est à la racine, pas dans `data` : `data` est tantôt un objet, tantôt
 * un tableau (la liste des relevés), et les apps installées lisent `data` tel
 * quel — l'ajout ne change rien pour elles.
 */
trait ApiReturnFormatTrait {

    protected function responseWithSuccess($message='', $data=[], $code = 200){
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
        ],$code);
    }

    /**
     * Réponse enveloppée dont la charge utile vient d'un paginateur : `data` garde
     * la forme choisie par l'appelant, `page` dit où finit la liste.
     *
     * `ApiPaginationContractTest` exige cette forme de toute route d'API qui pagine.
     */
    protected function responseWithPage($message, $data, LengthAwarePaginator $paginator, $code = 200){
        return response()->json([
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
            'page'      => self::pageDe($paginator),
        ], $code);
    }

    /** Le bloc `page` d'une réponse paginée, seul endroit qui en fixe les clés. */
    public static function pageDe(LengthAwarePaginator $paginator): array
    {
        return [
            'current'  => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last'     => max(1, $paginator->lastPage()),
            'total'    => $paginator->total(),
        ];
    }

    protected function responseWithError($message='', $data=[], $code=400){
        return response()->json([
            'success'     => false,
            'message'     => $message,
            'data'        => $data,
        ], $code);
    }
}
