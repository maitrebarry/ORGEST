<?php

namespace App\Http\Requests\Concerns;

use App\Rules\MalianPhone;

trait NormaliseTelephone
{
    /**
     * Nettoie les champs téléphone présents dans la requête (espaces,
     * tirets, indicatif +223) pour que validation, unicité et stockage
     * travaillent sur la même valeur : 8 chiffres collés.
     */
    protected function normaliserTelephone(string ...$champs): void
    {
        foreach ($champs as $champ) {
            if ($this->has($champ)) {
                $this->merge([$champ => MalianPhone::normaliser($this->input($champ))]);
            }
        }
    }
}
