<?php

declare(strict_types=1);

namespace App\Tests;

final class RelationshipApiTest extends ApiTestCase
{
    public function testCreateBookWithAuthorsThenList(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 2']);

        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1, 2],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1, 2], $data['authorIds']);

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1], $data['bookIds']);

        $this->jsonRequest('GET', '/api/authors/2');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1], $data['bookIds']);
    }

    public function testPutReplaceRelationships(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 2']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 3']);

        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1, 2],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/books/1', [
            'title' => 'Book A Updated',
            'description' => null,
            'authorIds' => [2, 3],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([2, 3], $data['authorIds']);

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['bookIds']);
    }

    public function testPatchReplaceRelationships(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 2']);

        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/books/1', ['authorIds' => [2]]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([2], $data['authorIds']);
    }

    public function testPatchClearRelationships(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);

        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/books/1', ['authorIds' => []]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['authorIds']);

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['bookIds']);
    }

    public function testPatchNullRelationshipUnchanged(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);

        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1],
        ]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/books/1', ['authorIds' => null]);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([1], $data['authorIds']);
    }

    public function testPatchInvalidAuthorIds(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'Book A']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PATCH', '/api/books/1', ['authorIds' => [999]]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testPutInvalidAuthorIds(): void
    {
        $this->jsonRequest('POST', '/api/books', ['title' => 'Book A']);
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('PUT', '/api/books/1', [
            'title' => 'Book A',
            'description' => null,
            'authorIds' => [999],
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testDeleteAuthorCascadeRelation(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1],
        ]);

        $this->jsonRequest('DELETE', '/api/authors/1');
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/books/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['authorIds']);
    }

    public function testDeleteBookCascadeRelation(): void
    {
        $this->jsonRequest('POST', '/api/authors', ['name' => 'Author 1']);
        $this->jsonRequest('POST', '/api/books', [
            'title' => 'Book A',
            'authorIds' => [1],
        ]);

        $this->jsonRequest('DELETE', '/api/books/1');
        $this->assertSame(202, $this->client->getResponse()->getStatusCode());

        $this->jsonRequest('GET', '/api/authors/1');
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $data['bookIds']);
    }
}
