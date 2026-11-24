<?php

namespace App\Import;

final readonly class ResultatImport
{
    public function __construct(
        public int $importes,
        public int $ignoresSansNom,
        public int $ignoresDoublons,
    ) {
    }
}
