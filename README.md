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
