# Library API

REST API for managing books and authors built with Symfony 7.4 and SQLite.

This project was implemented as a technical assignment with a predefined API and architecture requirements. In addition to standard CRUD operations, the specification required automatic request mapping in controllers and processing write operations through Symfony Messenger.

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

97 tests, 229 assertions.

The test suite covers API behavior, validation, pagination, filtering, sorting, relationships, update semantics, error handling, and edge cases.

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

Create, update, and delete operations return HTTP 202 immediately. The corresponding operations are dispatched through `Symfony Messenger` and executed by their message handlers.

## Architecture

* **Entities:** `Author`, `Book` — many-to-many relationship between authors and books
* **DTOs:** separate request and response objects (`AuthorCreateRequest`, `BookPatchRequest`, `AuthorResponse`, etc.)
* **Request mapping:** controllers use Symfony's automatic request mapping functionality to map incoming request data to typed request DTOs
* **Messenger:** write operations are represented by commands (`CreateAuthorCommand`, `UpdateBookCommand`, etc.) and processed by dedicated handlers
* **Transport:** synchronous Messenger transport, as permitted by the assignment
* **Database:** SQLite with Doctrine migrations in `migrations/`
* **Validation:** Symfony validation attributes on DTOs
* **Tests:** PHPUnit coverage for API behavior and edge cases

## Implementation Notes

The implementation follows the assignment requirements while adding several improvements around validation, querying, and test coverage.

### Request handling

Controllers use Symfony's automatic request mapping to convert incoming request data into dedicated request DTOs.

This keeps HTTP request parsing and validation separate from the application logic and allows different contracts for create, full update, and partial update operations.

### Messenger-based writes

Create, update, and delete operations are dispatched as Messenger commands.

For example:

* `CreateAuthorCommand`
* `UpdateAuthorCommand`
* `DeleteAuthorCommand`
* `CreateBookCommand`
* `UpdateBookCommand`
* `DeleteBookCommand`

The API returns HTTP 202 before the command handler performs the actual operation.

The synchronous transport is used for the assignment, so the Messenger-based architecture can be demonstrated without requiring a separate message broker or worker infrastructure.

### Validation and edge cases

The implementation goes beyond the basic CRUD requirements and includes:

* distinct `PUT` and `PATCH` semantics
* validation of PATCH fields when they are present
* rejection of whitespace-only values where applicable
* validation of pagination and sorting parameters
* explicit handling of missing resources
* explicit handling of invalid author relationships
* edge-case coverage in the test suite

### List API

Collection endpoints support:

* filtering
* pagination
* configurable page size
* sorting
* validation of list query parameters

### Testing

The project contains 97 tests with 229 assertions covering successful operations as well as validation failures, invalid parameters, missing resources, relationships, update semantics, and other edge cases.

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
