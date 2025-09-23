# PHP Route

**PHP Route** is a lightweight and flexible routing library for PHP applications. It provides an easy-to-use system for defining routes, grouping, middlewares, error handling, maintenance mode, and custom not-found responses.

---

## Features

* Define routes with HTTP methods: GET, POST, PUT, PATCH, DELETE, OPTIONS.
* Support for `any()` method to handle all HTTP methods.
* Route grouping with optional prefix and middlewares.
* Global and route-specific middlewares.
* Custom error handling and maintenance mode.
* Custom 404 Not Found and maintenance responses.
* PSR-7 compatible Request and Response objects using Guzzle.

---

## Installation

Install via Composer:

```bash
composer require refkinscallv/php-route
```

Include Composer autoloader in your project:

```php
require __DIR__ . '/vendor/autoload.php';
```

---

## Basic Usage

```php
use RFRoute\Route\Route;
use RFRoute\Http\Request;
use RFRoute\Http\Response;

// Simple GET route
Route::get('/hello', function(Request $req, Response $res) {
    $res->send("Hello World!");
});

// POST route
Route::post('/submit', function(Request $req, Response $res) {
    $data = $req->getParsedBody();
    $res->json(['status' => 'success', 'data' => $data]);
});

// Route with a controller
Route::get('/user', [App\Controllers\UserController::class, 'index']);
```

---

## Route Groups and Middlewares

```php
Route::group('/admin', function() {
    Route::get('/dashboard', function(Request $req, Response $res) {
        $res->send("Admin Dashboard");
    });
}, [
    function(Request $req, Response $res) {
        if (!isset($_SESSION['admin'])) {
            $res->send("Unauthorized", 401);
            return false;
        }
    }
]);

// Global middleware
Route::middleware([
    function(Request $req, Response $res) {
        error_log($req->getUri());
    }
], function() {
    // Routes inside this callback will have the global middleware applied
});
```

---

## Error Handling

```php
Route::setErrorHandler(function(Throwable $e, Request $req, Response $res) {
    $res->send("Something went wrong: " . $e->getMessage(), 500);
});

Route::setNotFoundHandler(function(Request $req, Response $res) {
    $res->send("Custom 404 Page Not Found", 404);
});

Route::setMaintenanceHandler(function(Request $req, Response $res) {
    $res->send("Service Unavailable: Maintenance Mode", 503);
});

// Enable maintenance mode
Route::enableMaintenance(true);
```

---

## Dispatching Routes

At the end of your script or router file:

```php
Route::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
```

---

## Response Helpers

```php
use RFRoute\Http\Response;

$res = new Response();

// Send plain text
$res->send("Hello World", 200);

// Send JSON
$res->json(['status' => 'ok', 'message' => 'Success']);

// Redirect
$res->redirect('/home');
```

---

## PSR-7 Request Object

```php
use RFRoute\Http\Request;

$req = new Request();
$method = $req->getMethod();
$uri = $req->getUri();
$body = $req->getParsedBody();
$query = $req->getQueryParams();
```

---

## Contributing

Contributions are welcome! You can help by:

* Reporting bugs
* Suggesting features
* Submitting pull requests

Please follow these steps:

1. Fork the repository
2. Create a new branch (`git checkout -b feature-name`)
3. Make your changes and commit (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature-name`)
5. Open a Pull Request

---

## Reporting Issues

If you encounter a bug or have a feature request, please open an issue on the [GitHub repository](https://github.com/refkinscallv/php-route/issues). Include:

* A clear description of the problem
* Steps to reproduce the issue
* Any relevant error messages or stack traces

---

## License

This library is licensed under the [MIT License](LICENSE).
