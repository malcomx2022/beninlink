<?php

namespace Tests\Concerns;

/**
 * Relève le texte **écrit en dur** dans une vue Blade : ce qu'un visiteur lit et
 * qui ne passe pas par `__()` (**S115**, généralisé en **S116**).
 *
 * On retire d'abord ce qui n'est pas du texte affiché — commentaires, scripts,
 * styles, expressions `{{ }}` / `{!! !!}`, blocs `@php … @endphp`, directives
 * Blade et leurs parenthèses (`->` y contient un `>`), entités HTML — puis on
 * lit les nœuds de texte entre balises.
 */
trait FindsHardcodedText
{
    /** @return list<string> les textes littéraux de la vue, ponctuation repliée */
    protected function textesEnDur(string $source, array $telsQuels = []): array
    {
        $source = preg_replace('/\{\{--.*?--\}\}|<script.*?<\/script>|<style.*?<\/style>|<!--.*?-->|\{\{.*?\}\}|\{!!.*?!!\}|@php\b.*?@endphp/s', ' ', $source);
        $source = preg_replace('/@\w+\s*\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)|@\w+/s', ' ', $source);
        $source = preg_replace('/&#?\w+;?/', ' ', $source);

        preg_match_all('/>([^<>]*)</', $source, $noeuds);
        $textes = [];
        foreach ($noeuds[1] as $noeud) {
            $texte = trim(preg_replace('/[\s:,.;!?()\-|#*\/%0-9]+/u', ' ', $noeud));
            if (preg_match('/\p{L}{2,}/u', $texte) && ! in_array($texte, $telsQuels, true)) {
                $textes[] = $texte;
            }
        }

        return $textes;
    }
}
