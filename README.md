# Library API

REST API for managing books and authors built with Symfony 7.4 and SQLite.

The project was implemented against a predefined API specification rather than as a generic CRUD application. It includes specification-specific validation rules, edge-case handling, DTO-based request/response separation, and command handling through Symfony Messenger.

## Requirements

### Without Docker

* PHP 8.3+ with the following extensions: `pdo_sqlite`, `intl`, `zip`, `xml`, `dom`
* Composer 2

### With Docker

* Docker with Docker Compose

## Setup

### With Docker

```bash
docker compose build
docker compose up -d
docker exec library-api-php composer install
docker exec library-api-php php bin/console doctrine:migrations:migrate --no-interaction
```

The API is available at `http://localhost:8000`.

Run tests:

```bash
docker exec library-api-php php bin/phpunit
```

### Without Docker

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php -S localhost:8000 -t public/
```

## Tests

```bash
php bin/phpunit
```

97 tests, 229 assertions. The SQLite database is reset before each test.

The test suite covers API behavior, validation rules, pagination, filtering, sorting, update semantics, relationships, error cases, and other edge cases defined by the specification.

## API

| Method | Endpoint            | Description                                          |
| ------ | ------------------- | ---------------------------------------------------- |
| GET    | `/api/authors`      | List authors with filtering, pagination, and sorting |
| POST   | `/api/authors`      | Create an author                                     |
| GET    | `/api/authors/{id}` | Get an author                                        |
| PUT    | `/api/authors/{id}` | Replace an author                                    |
| PATCH  | `/api/authors/{id}` | Partially update an author                           |
| DELETE | `/api/authors/{id}` | Delete an author                                     |
| GET    | `/api/books`        | List books with filtering, pagination, and sorting   |
| POST   | `/api/books`        | Create a book                                        |
| GET    | `/api/books/{id}`   | Get a book                                           |
| PUT    | `/api/books/{id}`   | Replace a book                                       |
| PATCH  | `/api/books/{id}`   | Partially update a book                              |
| DELETE | `/api/books/{id}`   | Delete a book                                        |

Create, update, and delete operations return HTTP 202. Write operations are handled through `Symfony Messenger` using the synchronous transport.

## Architecture

* **Entities:** `Author`, `Book` — many-to-many relationship between authors and books
* **DTOs:** separate request and response objects (`AuthorCreateRequest`, `BookPatchRequest`, `AuthorResponse`, etc.)
* **Messenger:** commands (`CreateAuthorCommand`, `UpdateBookCommand`, etc.) and their handlers
* **Database:** SQLite with migrations in `migrations/`
* **Validation:** Symfony validation attributes on DTOs
* **Tests:** API and domain behavior covered by PHPUnit

## Implementation Notes

This project follows a predefined API specification with several non-standard requirements and edge cases.

* `PUT` and `PATCH` have distinct validation and update semantics.
* Whitespace-only values are rejected where required by the specification.
* List endpoints validate pagination, sorting, and filtering parameters.
* Invalid and missing related entity IDs are handled explicitly.
* Write operations are dispatched through Symfony Messenger as required by the specification.
* The synchronous Messenger transport keeps command handling separated from the HTTP layer without introducing asynchronous infrastructure.
* Validation and edge-case behavior are covered by the automated test suite.

The goal was to implement the specified API contract precisely while keeping the application structure simple and testable.

## Example Requests

```bash
# Create an author
curl -X POST http://localhost:8000/api/authors \
  -H "Content-Type: application/json" \
  -d '{"name": "Leo Tolstoy"}'

# Create a book with authors
curl -X POST http://localhost:8000/api/books \
  -H "Content-Type: application/json" \
  -d '{"title": "War and Peace", "authorIds": [1]}'

# List books with filtering and pagination
curl "http://localhost:8000/api/books?title=War&page=1&pageSize=10&sort=title&order=asc"
```
