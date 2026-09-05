<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;
    /**
     * ⚠️ `company_id` etait absent de cette liste, alors que tout le socle ecrit
     * ces lignes par assignation en masse (`Setting::create(['company_id' => …])`,
     * dans `SettingSeeder` comme dans `PayoutSetupRepository`). Eloquent le
     * laissait donc tomber **en silence** : la ligne partait avec un locataire
     * nul, et `scopeCompanywise()` — comme `globalSettings()` — ne la retrouvait
     * plus jamais. Un reglage de passerelle enregistre depuis l'administration
     * etait ecrit puis perdu de vue, et chaque nouvel enregistrement creait une
     * ligne orpheline de plus. `SmsSetting` le declarait, lui, correctement.
     */
    protected $fillable = ['company_id','key','value'];
    
    public function scopeCompanywise($query){
        return $query->where('company_id',settings()->id);
    }

}
