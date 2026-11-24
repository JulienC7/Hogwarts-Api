<?php

namespace App\Import;

/**
 * Un personnage tel que renvoyé par GET /characters, réduit aux seuls champs utiles (règle E).
 */
final readonly class PersonnageSource
{
    /**
     * @param list<string> $wands
     */
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?string $gender = null,
        public ?string $image = null,
        public ?string $house = null,
        public ?string $species = null,
        public ?string $patronus = null,
        public array $wands = [],
    ) {
    }
}
