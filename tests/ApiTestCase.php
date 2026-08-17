<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->resetDatabase();
    }

    protected function jsonRequest(string $method, string $uri, array $data = []): KernelBrowser
    {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $data ? json_encode($data) : null,
        );

        return $this->client;
    }

    private function resetDatabase(): void
    {
        $this->em->clear();
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM author_book');
        $connection->executeStatement('DELETE FROM book');
        $connection->executeStatement('DELETE FROM author');
        $connection->executeStatement('DELETE FROM sqlite_sequence');
    }
}
