# Assouan Controller

HTTP controller base class for the A PHP libraries.

```bash
composer require assouan/controller
```

Requires PHP 8.5 or later.

## Input

Controller method arguments are resolved by name from route attributes, query string, form bodies, and JSON bodies.

```php
class Search extends A\Http\Controller
{
    public function get(string $q = ''): array
    {
        return ['q' => $q];
    }

    public function post(string $message): array
    {
        return ['message' => $message];
    }
}
```

Input priority is:

- route attributes
- `application/x-www-form-urlencoded` body
- `application/json` body
- query string

Missing nullable arguments stay `null`; scalar arguments are cast to their declared type.

## Rendering

Action return values are rendered with a small default:

```php
return new A\Http\Response();       // used as-is
return ['ok' => true];              // JSON response
return (object)['ok' => true];      // JSON response
return '<h1>Hello</h1>';            // HTML response
return null;                        // empty response
```
