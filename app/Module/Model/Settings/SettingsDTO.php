<?php
namespace App\Module\Model\Settings;

use Nette;

readonly class SettingsDTO {
    function __construct(
        public mixed $id, public mixed $param, public int|string|bool $value    //phpstan měl problém že constructor očekává např string a dostane mixed
    ) {
        //tady to přiřazování bejt nemusí protože to php dělá za tebe
    }

    
}