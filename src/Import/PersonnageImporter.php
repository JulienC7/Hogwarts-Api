<?php

namespace App\Import;

use App\Entity\Baguette;
use App\Entity\Espece;
use App\Entity\Maison;
use App\Entity\Patronus;
use App\Entity\Personnage;
use App\Repository\BaguetteRepository;
use App\Repository\EspeceRepository;
use App\Repository\MaisonRepository;
use App\Repository\PatronusRepository;
use App\Repository\PersonnageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Import des personnages depuis la Hogwarts API.
 * Chaque bloc correspond à une ligne de l'onglet « Règles ETL » de docs/mapping.xlsx.
 */
final class PersonnageImporter
{
    public const array PAGES = [1, 2];
    public const int PAGE_SIZE = 50;

    /** Mémoire de l'import : entités déjà retrouvées ou créées, indexées par nom. */
    private array $maisons = [];
    private array $especes = [];
    private array $patronus = [];
    private array $baguettes = [];

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly DenormalizerInterface $denormalizer,
        private readonly EntityManagerInterface $em,
        private readonly PersonnageRepository $personnageRepository,
        private readonly MaisonRepository $maisonRepository,
        private readonly EspeceRepository $especeRepository,
        private readonly PatronusRepository $patronusRepository,
        private readonly BaguetteRepository $baguetteRepository,
        #[Autowire(env: 'HOGWARTS_API_URL')]
        private readonly string $apiUrl,
    ) {
    }

    public function importer(): ResultatImport
    {
        $this->maisons = $this->especes = $this->patronus = $this->baguettes = [];
        $importes = $sansNom = $doublons = 0;
        $dejaVus = [];

        foreach ($this->extraire() as $source) {
            // T - règle 1 (T1) : sans nom, on ignore le personnage
            $nom = trim($source->name ?? '');
            if ('' === $nom) {
                ++$sansNom;
                continue;
            }

            // T - règle 2 (T2) : clé naturelle (hogwartsId) déjà en base, on ignore
            $hogwartsId = trim($source->id ?? '');
            if ('' === $hogwartsId
                || isset($dejaVus[$hogwartsId])
                || null !== $this->personnageRepository->findOneBy(['hogwartsId' => $hogwartsId])) {
                ++$doublons;
                continue;
            }
            $dejaVus[$hogwartsId] = true;

            // T - mapping
            $personnage = (new Personnage())
                ->setHogwartsId($hogwartsId)
                ->setNom(mb_substr($nom, 0, 255))               // T7 : nom coupé à 255 caractères
                ->setGenre($this->nettoyer($source->gender))    // T8 : trim, vide = null
                ->setImage($this->nettoyer($source->image))     // T8 : trim, vide = null
                ->setMaison($this->trouverOuCreerMaison($source->house))           // T3
                ->setEspece($this->trouverOuCreerEspece($source->species))         // T4
                ->setPatronus($this->trouverOuCreerPatronus($source->patronus));   // T5

            // T - règle 3 (T6) : baguettes normalisées, retrouvées ou créées
            foreach ($source->wands as $wand) {
                $baguette = $this->trouverOuCreerBaguette($wand);
                if (null !== $baguette) {
                    $personnage->addBaguette($baguette);
                }
            }

            // L - CHARGER : persist de chaque personnage...
            $this->em->persist($personnage);
            ++$importes;
        }

        // ... et un seul flush à la fin
        $this->em->flush();

        return new ResultatImport($importes, $sansNom, $doublons);
    }

    /**
     * E - EXTRAIRE : GET characters?page={page}&page_size=50, pages 1 et 2, seulement les champs utiles.
     *
     * @return iterable<PersonnageSource>
     */
    private function extraire(): iterable
    {
        foreach (self::PAGES as $page) {
            $reponse = $this->client->request('GET', rtrim($this->apiUrl, '/').'/characters', [
                'query' => ['page' => $page, 'page_size' => self::PAGE_SIZE],
            ])->toArray();

            foreach ($reponse['data'] ?? [] as $personnage) {
                yield $this->denormalizer->denormalize($personnage, PersonnageSource::class);
            }
        }
    }

    // T3 : maison retrouvée par son nom, sinon créée ; null = pas de maison
    private function trouverOuCreerMaison(?string $nom): ?Maison
    {
        if (null === $nom || '' === $nom) {
            return null;
        }

        return $this->maisons[$nom] ??= $this->maisonRepository->findOneBy(['nom' => $nom])
            ?? $this->creer((new Maison())->setNom($nom));
    }

    // T4 : espèce retrouvée par son nom, sinon créée ; null = espèce non renseignée
    private function trouverOuCreerEspece(?string $nom): ?Espece
    {
        if (null === $nom || '' === $nom) {
            return null;
        }

        return $this->especes[$nom] ??= $this->especeRepository->findOneBy(['nom' => $nom])
            ?? $this->creer((new Espece())->setNom($nom));
    }

    // T5 : patronus retrouvé par son nom, sinon créé ; null = pas de patronus
    private function trouverOuCreerPatronus(?string $nom): ?Patronus
    {
        if (null === $nom || '' === $nom) {
            return null;
        }

        return $this->patronus[$nom] ??= $this->patronusRepository->findOneBy(['nom' => $nom])
            ?? $this->creer((new Patronus())->setNom($nom));
    }

    // T6 : mention entre parenthèses retirée, trim ; « Unknown… » ignorée ; retrouvée ou créée
    private function trouverOuCreerBaguette(string $wand): ?Baguette
    {
        $description = trim((string) preg_replace('/\s*\([^)]*\)/', '', $wand));
        if ('' === $description || str_starts_with($description, 'Unknown')) {
            return null;
        }

        return $this->baguettes[$description] ??= $this->baguetteRepository->findOneBy(['description' => $description])
            ?? $this->creer((new Baguette())->setDescription($description));
    }

    // T8 : textes nettoyés (trim) ; valeur vide ou absente = null
    private function nettoyer(?string $valeur): ?string
    {
        $valeur = trim($valeur ?? '');

        return '' === $valeur ? null : $valeur;
    }

    /**
     * @template T of object
     *
     * @param T $entite
     *
     * @return T
     */
    private function creer(object $entite): object
    {
        $this->em->persist($entite);

        return $entite;
    }
}
