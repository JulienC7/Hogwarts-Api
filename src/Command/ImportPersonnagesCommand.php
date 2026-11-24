<?php

namespace App\Command;

use App\Import\PersonnageImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-personnages',
    description: 'Importe les personnages depuis la Hogwarts API (pages 1 et 2, 50 par page)',
)]
final class ImportPersonnagesCommand
{
    public function __construct(
        private readonly PersonnageImporter $importer,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $resultat = $this->importer->importer();

        $io->success(sprintf(
            '%d personnage(s) importé(s), %d ignoré(s) sans nom, %d ignoré(s) déjà en base.',
            $resultat->importes,
            $resultat->ignoresSansNom,
            $resultat->ignoresDoublons,
        ));

        return Command::SUCCESS;
    }
}
