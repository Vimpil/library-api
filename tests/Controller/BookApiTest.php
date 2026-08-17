<?php

declare(strict_types=1);

namespace App\Tests;

final class BookApiTest extends ApiTestCase
{
    public function testListEmpty(): void
    {
        $this->jsonRequest('GET', '/api/books');

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
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'PHP in Action',
            'description' => 'A great book',
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(202, $response->getStatusCode());
        $this->assertEmpty($response->getContent());
    }

    public function testCreateAndRead(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Symfony Guide',
            'description' => 'Learn Symfony',
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $response = $this->client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $data['id']);
        $this->assertSame('Symfony Guide', $data['title']);
        $this->assertSame('Learn Symfony', $data['description']);
        $this->assertSame([], $data['authorIds']);
    }

    public function testCreateWithAuthors(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author A']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author B']);

        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Co-authored Book',
            'authorIds' => [1, 2],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1, 2], $data['authorIds']);
    }

    public function testCreateWithInvalidAuthorIds(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Bad Refs',
            'authorIds' => [999],
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateValidationEmptyTitle(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => '']);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateValidationTitleTooLong(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => str_repeat('a', 256)]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateMissingTitle(): void
    {
        $this->jsonRequest('POST', '/api/books', []);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testReadNotFound(): void
    {
        $this->jsonRequest('GET', '/api/books/999');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdate(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'Original']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/books/1', [
            'title' => 'Updated Title',
            'description' => 'Updated desc',
            'authorIds' => [],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Updated Title', $data['title']);
        $this->assertSame('Updated desc', $data['description']);
    }

    public function testUpdateSetDescriptionNull(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Test',
            'description' => 'Has desc',
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/books/1', [
            'title' => 'Test',
            'description' => null,
            'authorIds' => [],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertNull($data['description']);
    }

    public function testUpdateMissingProperty(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'Test']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/books/1', ['title' => 'Updated']);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testUpdateNotFound(): void
    {
        $this->jsonRequest('PUT', '/api/books/999', [
            'title' => 'Nobody',
            'description' => null,
            'authorIds' => [],
        ]);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testPatch(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Original',
            'description' => 'Original desc',
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/books/1', ['title' => 'Patched Title']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Patched Title', $data['title']);
        $this->assertSame('Original desc', $data['description']);
    }

    public function testPatchNullFieldUnchanged(): void
    {
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Keep Title',
            'description' => 'Keep Desc',
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/books/1', [
            'title' => null,
            'description' => null,
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Keep Title', $data['title']);
        $this->assertSame('Keep Desc', $data['description']);
    }

    public function testPatchNotFound(): void
    {
        $this->jsonRequest('PATCH', '/api/books/999', ['title' => 'Nobody']);
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDelete(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'To Delete']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('DELETE', '/api/books/1');
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteNotFound(): void
    {
        $this->jsonRequest('DELETE', '/api/books/999');
        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    public function testListPagination(): void
    {
        for ($i = 1; $i <= 5; ++$i) {
            $this->jsonRequest('POST', '/api/books', ['title' => "Book $i"]);
        }

        $this->jsonRequest('GET', '/api/books?page=1&pageSize=2');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $data['items']);
        $this->assertSame(5, $data['pagination']['total']);
        $this->assertSame(3, $data['pagination']['pages']);
    }

    public function testListFilterByTitle(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'PHP Basics']);
        $this->jsonRequest('POST', '/api/books', ['title' => 'PHP Advanced']);
        $this->jsonRequest('POST', '/api/books', ['title' => 'Java Intro']);

        $this->jsonRequest('GET', '/api/books?title=PHP');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertCount(2, $data['items']);
    }

    public function testListSortByTitle(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'Zebra']);
        $this->jsonRequest('POST', '/api/books', ['title' => 'Apple']);
        $this->jsonRequest('POST', '/api/books', ['title' => 'Mango']);

        $this->jsonRequest('GET', '/api/books?sort=title&order=asc');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $titles = array_column($data['items'], 'title');
        $this->assertSame(['Apple', 'Mango', 'Zebra'], $titles);
    }

    public function testListInvalidSortField(): void
    {
        $this->jsonRequest('GET', '/api/books?sort=invalid');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testListInvalidOrder(): void
    {
        $this->jsonRequest('GET', '/api/books?order=random');
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }
}
