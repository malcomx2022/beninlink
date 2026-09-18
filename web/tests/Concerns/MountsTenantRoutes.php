<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route as Router;

/**
 * Monte les routes de locataire dans un test, et les rend joignables en HTTP.
 *
 * ⚠️ `BackOfficeScopingTest` affirmait que « les routes du back-office ne sont
 * montées qu'avec un domaine de locataire, hors de portée d'un test ». La
 * première moitié est vraie, la seconde était fausse : il suffit de fournir le
 * domaine. Ce trait le fait, et c'est ce qui a permis de **prouver S28 par un
 * appel HTTP** au lieu de le déduire de la déclaration.
 *
 * Deux obstacles, dans cet ordre :
 *
 * 1. `routes/web.php` n'enregistre ses routes que si `request()->getHost()`
 *    figure dans la table `domains` ET si `app.app_installed` vaut `yes`. Au
 *    moment de l'enregistrement, l'hôte est celui du test : `localhost`.
 *    D'où le premier domaine semé, et la ré-inclusion du fichier.
 * 2. `PreventAccessFromCentralDomains` refuse (404) tout appel dont l'hôte est
 *    un domaine central — et `config('tenancy.central_domains')` contient
 *    justement `localhost` et `127.0.0.1`. Une requête vers `/` sur `localhost`
 *    répond donc 404 même si la route existe. D'où le second domaine, et
 *    l'obligation d'appeler `$this->get(self::HOTE . '/…')`.
 *
 * Le piège est que l'étape 1 sans l'étape 2 donne un 404 identique à « route
 * absente » : on croit que la route n'est pas montée alors qu'elle l'est.
 */
trait MountsTenantRoutes
{
    /** L'hôte à utiliser dans les appels HTTP : un locataire, pas un domaine central. */
    protected const HOTE = 'http://pme.test';

    /**
     * Sème le locataire, ses deux domaines, puis enregistre `routes/web.php`.
     * À appeler APRÈS `seedTenant()` : le domaine pointe sur la société semée.
     */
    protected function mountTenantRoutes(): void
    {
        DB::table('tenants')->insert([
            'id' => 'test',
            'company_id' => settings()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['localhost', 'pme.test'] as $domaine) {
            DB::table('domains')->insert([
                'domain' => $domaine,
                'tenant_id' => 'test',
                'domain_name' => $domaine,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Config::set('app.app_installed', 'yes');

        Router::middleware('web')->group(base_path('routes/web.php'));
        Router::getRoutes()->refreshNameLookups();
    }
}
