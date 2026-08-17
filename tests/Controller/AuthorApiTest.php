<?php

declare(strict_types=1);

namespace App\Tests;

final class AuthorApiTest extends ApiTestCase
{
    public function testListEmpty(): void
    {
        $this->jsonRequest('GET', '/api/authors');

        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertSame([], $data['items']);
        $this->assertSame(0, $data['pagination']['total']);
    }

    public function testCreate(): void
    {
        $this->jsonRequest('POST', '/api/authors', [
            'name' => 'John Doe',
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(202, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testCreateAndRead(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Jane Smith']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/authors/1');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $data['id']);
        $this->assertSame('Jane Smith', $data['name']);
        $this->assertSame([], $data['bookIds']);
    }

    public function testCreateValidationEmptyName(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => '']);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateValidationNameTooLong(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => str_repeat('a', 256)]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateMissingName(): void
    {
        $this->jsonRequest('POST', '/api/authors', []);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testReadNotFound(): void
    {
        $this->jsonRequest('GET', '/api/authors/999');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdate(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Original']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/authors/1', [
            'name' => 'Updated',
            'bookIds' => [],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Updated', $data['name']);
    }

    public function testUpdateMissingProperty(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Test']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/authors/1', ['name' => 'Updated']);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateNotFound(): void
    {
        $this->jsonRequest('PUT', '/api/authors/999', [
            'name' => 'Nobody',
            'bookIds' => [],
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testPatch(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Original']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/authors/1', ['name' => 'Patched']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Patched', $data['name']);
    }

    public function testPatchNullFieldUnchanged(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Keep Me']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/authors/1', ['name' => null]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Keep Me', $data['name']);
    }

    public function testPatchNotFound(): void
    {
        $this->jsonRequest('PATCH', '/api/authors/999', ['name' => 'Nobody']);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'To Delete']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('DELETE', '/api/authors/1');
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/authors/1');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteNotFound(): void
    {
        $this->jsonRequest('DELETE', '/api/authors/999');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testListPagination(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->jsonRequest('POST', '/api/authors', ['name' => "Author $i"]);
        }

        $this->jsonRequest('GET', '/api/authors?page=1&pageSize=2');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $data['items']);
        $this->assertSame(5, $data['pagination']['total']);
        $this->assertSame(3, $data['pagination']['pages']);
        $this->assertSame(1, $data['pagination']['page']);
        $this->assertSame(2, $data['pagination']['pageSize']);
    }

    public function testListFilterByName(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'John']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Jane']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Bob']);

        $this->jsonRequest('GET', '/api/authors?name=oh');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(1, $data['items']);
        $this->assertSame('John', $data['items'][0]['name']);
    }

    public function testListSortByName(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Charlie']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Alice']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Bob']);

        $this->jsonRequest('GET', '/api/authors?sort=name&order=asc');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $names = array_column($data['items'], 'name');
        $this->assertSame(['Alice', 'Bob', 'Charlie'], $names);
    }

    public function testListSortDesc(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Alice']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Bob']);

        $this->jsonRequest('GET', '/api/authors?sort=name&order=desc');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $names = array_column($data['items'], 'name');
        $this->assertSame(['Bob', 'Alice'], $names);
    }

    public function testListInvalidSortField(): void
    {
        $this->jsonRequest('GET', '/api/authors?sort=invalid');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testListInvalidOrder(): void
    {
        $this->jsonRequest('GET', '/api/authors?order=random');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }
}
