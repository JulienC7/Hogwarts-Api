<?php

namespace App\Tests\Import;

use App\Entity\Baguette;
use App\Entity\Maison;
use App\Entity\Personnage;
use App\Import\PersonnageImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PersonnageImporterTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    /** @var list<string> */
    private array $urlsAppelees = [];

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        // Base de test vierge, construite par les migrations
        $console = new Application($kernel);
        $console->setAutoExit(false);
        $console->run(new ArrayInput(['command' => 'doctrine:schema:drop', '--force' => true, '--full-database' => true]), new NullOutput());
        $console->run(new ArrayInput(['command' => 'doctrine:migrations:migrate', '--no-interaction' => true]), new NullOutput());

        $this->em = self::getContainer()->get(EntityManagerInterface::class);

        $pages = [
            1 => [
                $this->personnage('harry-potter', 'Harry Potter', house: 'Gryffindor', patronus: 'Stag',
                    wands: ['11", Holly, phoenix feather', '10¾", Vine, dragon heartstring (formerly)']),
                $this->personnage('sans-nom', '   '),
                $this->personnage('aberforth-dumbledore', 'Aberforth Dumbledore', house: 'Gryffindor',
                    wands: ['Unknown length, wood and core']),
            ],
            2 => [
                $this->personnage('hermione-granger', 'Hermione Granger', house: 'Gryffindor', gender: '  ',
                    wands: ['10¾", Vine, dragon heartstring']),
                $this->personnage('nom-long', str_repeat('a', 300), species: 'Human', image: ' https://img/x.png '),
                $this->personnage('harry-potter', 'Harry Potter'), // doublon dans la même source
            ],
        ];

        $client = new MockHttpClient(function (string $method, string $url) use ($pages) {
            $this->urlsAppelees[] = $url;
            parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);

            return new JsonMockResponse(['data' => $pages[(int) $query['page']] ?? []]);
        });
        self::getContainer()->set(HttpClientInterface::class, $client);
    }

    public function testImporteSelonLesReglesEtl(): void
    {
        $resultat = self::getContainer()->get(PersonnageImporter::class)->importer();

        self::assertSame(4, $resultat->importes);
        self::assertSame(1, $resultat->ignoresSansNom);   // T1
        self::assertSame(1, $resultat->ignoresDoublons);  // T2

        // E : pages 1 et 2, 50 par page
        self::assertCount(2, $this->urlsAppelees);
        self::assertStringContainsString('/characters?page=1&page_size=50', $this->urlsAppelees[0]);
        self::assertStringContainsString('/characters?page=2&page_size=50', $this->urlsAppelees[1]);

        $this->em->clear();
        $personnages = $this->em->getRepository(Personnage::class);

        // T3 : une seule maison Gryffindor pour trois personnages
        self::assertSame(1, $this->em->getRepository(Maison::class)->count());
        $harry = $personnages->findOneBy(['hogwartsId' => 'harry-potter']);
        self::assertSame('Gryffindor', $harry->getMaison()->getNom());
        self::assertSame('Stag', $harry->getPatronus()->getNom());

        // T6 : parenthèses retirées, « Unknown… » ignorée, même baguette partagée
        $descriptions = array_map(fn (Baguette $b) => $b->getDescription(), $this->em->getRepository(Baguette::class)->findAll());
        sort($descriptions);
        self::assertSame(['10¾", Vine, dragon heartstring', '11", Holly, phoenix feather'], $descriptions);
        self::assertCount(0, $personnages->findOneBy(['hogwartsId' => 'aberforth-dumbledore'])->getBaguettes());
        self::assertCount(1, $personnages->findOneBy(['hogwartsId' => 'hermione-granger'])->getBaguettes());

        // T7 : nom tronqué ; T8 : trim, vide = null ; T4 : espèce
        $long = $personnages->findOneBy(['hogwartsId' => 'nom-long']);
        self::assertSame(255, mb_strlen($long->getNom()));
        self::assertSame('https://img/x.png', $long->getImage());
        self::assertSame('Human', $long->getEspece()->getNom());
        self::assertNull($personnages->findOneBy(['hogwartsId' => 'hermione-granger'])->getGenre());
    }

    public function testRelancerLImportNeDupliqueRien(): void
    {
        $importer = self::getContainer()->get(PersonnageImporter::class);
        $importer->importer();
        $resultat = $importer->importer();

        self::assertSame(0, $resultat->importes);
        self::assertSame(5, $resultat->ignoresDoublons);
        self::assertSame(4, $this->em->getRepository(Personnage::class)->count());
        self::assertSame(1, $this->em->getRepository(Maison::class)->count());
        self::assertSame(2, $this->em->getRepository(Baguette::class)->count());
    }

    private function personnage(
        string $id,
        ?string $name,
        ?string $house = null,
        ?string $species = null,
        ?string $patronus = null,
        ?string $gender = 'Male',
        ?string $image = null,
        array $wands = [],
    ): array {
        // Champs inutiles inclus pour vérifier qu'ils sont ignorés (E)
        return compact('id', 'name', 'house', 'species', 'patronus', 'gender', 'image', 'wands')
            + ['slug' => $id, 'blood_status' => null, 'jobs' => [], 'wiki' => 'https://example.test'];
    }
}
