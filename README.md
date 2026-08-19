# Library API

REST API для управления книгами и авторами на Symfony 7.4 с SQLite.

## Требования

- PHP 8.2+ с расширениями: `pdo_sqlite`, `intl`, `zip`
- Composer 2
- Docker (опционально)

## Запуск

### Через Docker

```bash
docker compose build
docker compose up -d
docker exec library-api-php composer install
docker exec library-api-php php bin/console doctrine:migrations:migrate --no-interaction
```

API доступен на `http://localhost:8000`.

Тесты:

```bash
docker exec library-api-php php bin/phpunit
```

### Без Docker

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php -S localhost:8000 -t public/
```

## Тесты

```bash
php bin/phpunit
```

97 тестов, 229 проверок. База данных SQLite автоматически пересоздаётся перед каждым тестом.

## API

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/authors` | Список авторов (фильтрация, пагинация, сортировка) |
| POST | `/api/authors` | Создать автора |
| GET | `/api/authors/{id}` | Получить автора |
| PUT | `/api/authors/{id}` | Обновить автора (полная замена) |
| PATCH | `/api/authors/{id}` | Частичное обновление автора |
| DELETE | `/api/authors/{id}` | Удалить автора |
| GET | `/api/books` | Список книг (фильтрация, пагинация, сортировка) |
| POST | `/api/books` | Создать книгу |
| GET | `/api/books/{id}` | Получить книгу |
| PUT | `/api/books/{id}` | Обновить книгу (полная замена) |
| PATCH | `/api/books/{id}` | Частичное обновление книги |
| DELETE | `/api/books/{id}` | Удалить книгу |

Запросы на создание/обновление/удаление возвращают HTTP 202. Обработка происходит через `Symfony Messenger` (sync транспорт).

## Архитектура

- **Сущности:** `Author`, `Book` — связь ManyToMany (у автора много книг, у книги много авторов)
- **DTO:** отдельные объекты для запроса и ответа (`AuthorCreateRequest`, `BookPatchRequest`, `AuthorResponse` и т.д.)
- **Messenger:** команды (`CreateAuthorCommand`, `UpdateBookCommand` и т.д.) и обработчики
- **База данных:** SQLite, миграция в `migrations/`
- **Валидация:** атрибуты Symfony в DTO

## Примеры запросов

```bash
# Создать автора
curl -X POST http://localhost:8000/api/authors \
  -H "Content-Type: application/json" \
  -d '{"name": "Толстой"}'

# Создать книгу с авторами
curl -X POST http://localhost:8000/api/books \
  -H "Content-Type: application/json" \
  -d '{"title": "Война и мир", "authorIds": [1]}'

# Список книг с фильтрацией и пагинацией
curl "http://localhost:8000/api/books?title=Война&page=1&pageSize=10&sort=title&order=asc"
```
