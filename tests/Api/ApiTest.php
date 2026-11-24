<?php

namespace App\Tests\Api;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class ApiTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $console = new Application(self::$kernel);
        $console->setAutoExit(false);
        $console->run(new ArrayInput(['command' => 'doctrine:schema:drop', '--force' => true, '--full-database' => true]), new NullOutput());
        $console->run(new ArrayInput(['command' => 'doctrine:migrations:migrate', '--no-interaction' => true]), new NullOutput());
    }

    public function testSwaggerEstAccessible(): void
    {
        $this->client->request('GET', '/api/docs', server: ['HTTP_ACCEPT' => 'text/html']);
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/docs', server: ['HTTP_ACCEPT' => 'application/vnd.openapi+json']);
        self::assertResponseIsSuccessful();
        $paths = array_keys(json_decode($this->client->getResponse()->getContent(), true)['paths']);
        foreach (['/api/personnages', '/api/maisons', '/api/especes', '/api/patronuses', '/api/baguettes'] as $path) {
            self::assertContains($path, $paths);
        }
    }

    public function testCreerUnPersonnageAvecSesRelations(): void
    {
        $maison = $this->post('/api/maisons', ['nom' => 'Gryffindor']);
        $baguette = $this->post('/api/baguettes', ['description' => '11", Holly, phoenix feather']);

        $personnage = $this->post('/api/personnages', [
            'hogwartsId' => 'harry-potter',
            'nom' => 'Harry Potter',
            'maison' => $maison['@id'],
            'baguettes' => [$baguette['@id']],
        ]);

        self::assertResponseStatusCodeSame(201);
        self::assertSame('Gryffindor', $personnage['maison']['nom']);
        self::assertSame('11", Holly, phoenix feather', $personnage['baguettes'][0]['description']);
    }

    /**
     * Les noms uniques doivent donner une erreur de validation, pas une erreur SQL.
     */
    public function testDoublonRefuseParLaValidation(): void
    {
        foreach (['/api/maisons', '/api/especes', '/api/patronuses'] as $url) {
            $this->post($url, ['nom' => 'Doublon']);
            $erreur = $this->post($url, ['nom' => 'Doublon']);

            self::assertResponseStatusCodeSame(422);
            self::assertSame('nom', $erreur['violations'][0]['propertyPath'], $url);
        }

        $this->post('/api/personnages', ['hogwartsId' => 'harry', 'nom' => 'Harry']);
        $erreur = $this->post('/api/personnages', ['hogwartsId' => 'harry', 'nom' => 'Autre']);
        self::assertResponseStatusCodeSame(422);
        self::assertSame('hogwartsId', $erreur['violations'][0]['propertyPath']);
    }

    public function testNomVideRefuse(): void
    {
        $erreur = $this->post('/api/personnages', ['hogwartsId' => 'x', 'nom' => '']);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('nom', $erreur['violations'][0]['propertyPath']);
    }

    private function post(string $url, array $donnees): array
    {
        $this->client->request('POST', $url, server: [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], content: json_encode($donnees));

        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
