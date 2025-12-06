# PHP Route
**PHP Route** is a lightweight and flexible routing library for PHP applications. It provides an easy-to-use system for defining routes, grouping, middlewares, error handling, maintenance mode, and custom not-found responses.

---

## Features
* Define routes with HTTP methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
* Support for `any()` method to handle all HTTP methods
* Route parameters with regex support and optional parameters
* Route grouping with optional prefix and middlewares
* **Global, group, and route-specific middlewares with multiple format support**
* **Middleware closure with return value control (true = continue, false = stop)**
* **Support for closure, static class, and instance middleware formats**
* **Request attributes with middleware persistence** - Set attributes in middleware, access in handlers
* Custom error handling and maintenance mode
* Custom 404 Not Found and maintenance responses
* PSR-7 compatible Request and Response objects using Guzzle

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

### Simple Routes
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
    $data = $req->input(); // Get all input data
    $res->json(['status' => 'success', 'data' => $data]);
});

// PUT route
Route::put('/update/{id}', function(Request $req, Response $res) {
    $id = $req->getAttribute('id');
    $res->json(['message' => "Updated item $id"]);
});

// DELETE route
Route::delete('/delete/{id}', function(Request $req, Response $res) {
    $id = $req->getAttribute('id');
    $res->json(['message' => "Deleted item $id"]);
});

// Handle any HTTP method
Route::any('/webhook', function(Request $req, Response $res) {
    $res->json(['received' => true]);
});
```

### Route with Controller
```php
// Using controller class
Route::get('/users', [App\Controllers\UserController::class, 'index']);
Route::get('/users/{id}', [App\Controllers\UserController::class, 'show']);
Route::post('/users', [App\Controllers\UserController::class, 'store']);

// Using controller instance
$userController = new App\Controllers\UserController();
Route::get('/users', [$userController, 'index']);
```

---

## Route Parameters

### Required Parameters
```php
Route::get('/user/{id}', function(Request $req, Response $res) {
    $id = $req->getAttribute('id');
    $res->send("User ID: " . $id);
});

// URL: /user/123
// Output: User ID: 123
```

### Optional Parameters
```php
Route::get('/post/{?slug}', function(Request $req, Response $res) {
    $slug = $req->getAttribute('slug') ?? 'default-slug';
    $res->json(['slug' => $slug]);
});

// URL: /post/hello-world → slug = "hello-world"
// URL: /post → slug = null (use ?? operator for default)
```

### Parameters with Regex Validation
```php
// Numeric ID only
Route::get('/order/{id:\d+}', function(Request $req, Response $res) {
    $id = $req->getAttribute('id');
    $res->json(['order_id' => $id]);
});

// Optional numeric parameter
Route::get('/page/{?page:\d+}', function(Request $req, Response $res) {
    $page = $req->getAttribute('page') ?? 1;
    $res->json(['page' => (int)$page]);
});

// Slug pattern
Route::get('/blog/{slug:[a-z0-9-]+}', function(Request $req, Response $res) {
    $slug = $req->getAttribute('slug');
    $res->json(['slug' => $slug]);
});
```

---

## Middleware System

### Middleware Formats

PHP Route supports **three middleware formats** for maximum flexibility:

#### 1. Closure/Anonymous Function
```php
Route::get('/protected', function(Request $req, Response $res) {
    $res->json(['message' => 'Protected route']);
}, [
    function(Request $req, Response $res) {
        if (!$req->getHeaderLine('Authorization')) {
            $res->json(['error' => 'Unauthorized'], 401);
            return false; // Stop request
        }
        return true; // Continue to next middleware/handler
    }
]);
```

#### 2. Static Class Method
```php
// String format: 'ClassName@methodName'
Route::post('/api/users', function(Request $req, Response $res) {
    $res->json(['message' => 'User created']);
}, [
    'AuthMiddleware@check'
]);

// Array format: [ClassName::class, 'methodName']
Route::post('/api/users', function(Request $req, Response $res) {
    $res->json(['message' => 'User created']);
}, [
    [AuthMiddleware::class, 'check']
]);

// Static middleware class
class AuthMiddleware {
    public static function check(Request $req, Response $res) {
        if (!$req->getHeaderLine('Authorization')) {
            $res->json(['error' => 'Unauthorized'], 401);
            return false;
        }
        return true;
    }
}
```

#### 3. Instance Method
```php
// Create instance with parameters
$rateLimiter = new RateLimitMiddleware(100);

Route::get('/api/data', function(Request $req, Response $res) {
    $res->json(['data' => []]);
}, [
    [$rateLimiter, 'check']
]);

// Instance middleware class
class RateLimitMiddleware {
    private int $limit;
    
    public function __construct(int $limit = 100) {
        $this->limit = $limit;
    }
    
    public function check(Request $req, Response $res) {
        $ip = $req->getServerParams()['REMOTE_ADDR'] ?? '';
        // Implement rate limiting logic
        error_log("Rate limit check: {$this->limit} requests from $ip");
        return true; // Continue
    }
}
```

### Multiple Middlewares on Single Route
```php
Route::post('/api/admin/users', [UserController::class, 'store'], [
    // Middleware 1: Check authorization
    'AuthMiddleware@check',
    
    // Middleware 2: Check admin role
    function(Request $req, Response $res) {
        $role = $req->getHeaderLine('X-User-Role');
        if ($role !== 'admin') {
            $res->json(['error' => 'Forbidden'], 403);
            return false;
        }
        return true;
    },
    
    // Middleware 3: Rate limiting instance
    new RateLimitMiddleware(10)
]);
```

### Middleware Control Flow
Middleware execution stops immediately when any middleware returns `false`:

```
Global Middlewares → Group Middlewares → Route Middlewares → Handler
     ↓                    ↓                    ↓              ↓
  [true]              [true]              [true]          Execute Handler
  [false] ❌ STOP    [false] ❌ STOP    [false] ❌ STOP   (not reached)
```

---

## Request Attributes

### Setting Attributes in Middleware

Middleware can set attributes on the request object that persist to the route handler:

```php
class AuthMiddleware {
    public static function check(Request $req, Response $res) {
        $token = $req->getHeaderLine('Authorization');
        
        if (empty($token)) {
            $res->json(['error' => 'Unauthorized'], 401);
            return false;
        }
        
        // Set user attributes from token
        $req->withAttribute('user_id', 123);
        $req->withAttribute('user_role', 'admin');
        $req->withAttribute('user_email', 'admin@example.com');
        
        return true;
    }
}
```

### Accessing Attributes in Route Handlers

Attributes set by middleware are automatically available in route handlers:

```php
Route::get('/profile', function(Request $req, Response $res) {
    // Access attributes set by middleware
    $userId = $req->getAttribute('user_id');
    $role = $req->getAttribute('user_role');
    $email = $req->getAttribute('user_email');
    
    $res->json([
        'user_id' => $userId,
        'role' => $role,
        'email' => $email
    ]);
}, [
    'AuthMiddleware@check'
]);
```

### Combining URL Parameters with Middleware Attributes

```php
Route::get('/user/{id}', function(Request $req, Response $res) {
    // URL parameter
    $userId = $req->getAttribute('id');
    
    // Middleware attributes
    $currentUserId = $req->getAttribute('user_id');
    $userRole = $req->getAttribute('user_role');
    
    $res->json([
        'requested_user_id' => $userId,
        'requested_by' => $currentUserId,
        'requester_role' => $userRole
    ]);
}, [
    'AuthMiddleware@check'
]);
```

### Complete Attribute Example

```php
Route::middleware([
    function(Request $req, Response $res) {
        // Global middleware - set request tracking
        $req->withAttribute('request_id', uniqid('req_'));
        $req->withAttribute('start_time', microtime(true));
        return true;
    }
], function() {
    
    Route::get('/dashboard', function(Request $req, Response $res) {
        // Access URL parameters
        // Access global middleware attributes
        // Access route middleware attributes
        $requestId = $req->getAttribute('request_id');
        $userId = $req->getAttribute('user_id');
        
        $res->json([
            'request_id' => $requestId,
            'user_id' => $userId,
            'message' => 'Dashboard'
        ]);
    }, [
        'AuthMiddleware@check'  // Sets user_id
    ]);
    
});
```

---

## Global Middleware

Apply middleware to all routes within a callback:

```php
Route::middleware([
    function(Request $req, Response $res) {
        // Log all requests
        error_log($req->getMethod() . " " . $req->getUri()->getPath());
        return true; // Continue
    },
    function(Request $req, Response $res) {
        // Add request tracking
        $req->withAttribute('request_id', uniqid());
        return true;
    }
], function() {
    // All routes defined here will have the global middleware applied
    Route::get('/', function(Request $req, Response $res) {
        $res->send("Home");
    });
    
    Route::get('/about', function(Request $req, Response $res) {
        $res->send("About");
    });
});
```

---

## Route Groups

### Basic Group
```php
Route::group('/api', function() {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
});

// Routes become: /api/users, /api/users/{id}, /api/users POST
```

### Group with Middleware
```php
Route::group('/admin', function() {
    Route::get('/dashboard', function(Request $req, Response $res) {
        $res->json(['message' => 'Admin Dashboard']);
    });
    
    Route::get('/users', function(Request $req, Response $res) {
        $res->json(['users' => []]);
    });
}, [
    // Group-level middleware
    'AuthMiddleware@check',
    function(Request $req, Response $res) {
        if ($req->getHeaderLine('X-User-Role') !== 'admin') {
            $res->json(['error' => 'Forbidden'], 403);
            return false;
        }
        return true;
    }
]);
```

### Nested Groups
```php
Route::group('/api', function() {
    // /api/public routes
    Route::get('/public', function(Request $req, Response $res) {
        $res->json(['public' => true]);
    });
    
    // /api/v1 group
    Route::group('/v1', function() {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        
        // /api/v1/admin nested group
        Route::group('/admin', function() {
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
        }, [
            'AuthMiddleware@checkAdmin'
        ]);
    }, [
        function(Request $req, Response $res) {
            $version = $req->getHeaderLine('API-Version') ?: '1.0';
            error_log("API Version: $version");
            return true;
        }
    ]);
});
```

---

## Error Handling

### Global Error Handler
```php
Route::setErrorHandler(function(Throwable $e, Request $req, Response $res) {
    $res->json([
        'error' => true,
        'message' => $e->getMessage(),
        'trace' => $e->getTrace()
    ], 500);
});
```

### Not Found Handler (404)
```php
Route::setNotFoundHandler(function(Request $req, Response $res) {
    $res->json([
        'error' => true,
        'message' => 'Route not found',
        'path' => $req->getUri()->getPath()
    ], 404);
});
```

### Maintenance Mode
```php
// Set maintenance handler
Route::setMaintenanceHandler(function(Request $req, Response $res) {
    $res->json([
        'error' => true,
        'message' => 'Service unavailable - Maintenance mode'
    ], 503);
});

// Enable/disable maintenance mode
Route::enableMaintenance(true);  // Enable
Route::enableMaintenance(false); // Disable
```

---

## Request Object

### Getting Input Data
```php
use RFRoute\Http\Request;

$req = new Request();

// Get all input (query + body + JSON)
$all = $req->input();

// Get specific input
$email = $req->input('email');

// Get with default value
$page = $req->input('page', 1);

// Get only specific keys
$data = $req->only(['name', 'email']);

// Get JSON body
$json = $req->json();

// Get query parameters
$query = $req->getQueryParams();

// Get parsed body
$body = $req->getParsedBody();

// Get uploaded files
$file = $req->file('avatar');

// Get headers
$auth = $req->getHeaderLine('Authorization');

// Get route parameters
$id = $req->getAttribute('id');

// Get middleware attributes
$userId = $req->getAttribute('user_id');

// Get with default value
$role = $req->getAttribute('user_role', 'guest');

// Get all attributes
$allAttrs = $req->getAttributes();

// HTTP Method
$method = $req->getMethod();

// URI
$uri = $req->getUri();
```

### Supported Content Types
- `application/json` - Automatically parsed
- `application/x-www-form-urlencoded` - Form data
- Multipart form data and file uploads

---

## Response Object

### Sending Responses
```php
use RFRoute\Http\Response;

$res = new Response();

// Send plain text
$res->send("Hello World", 200);

// Send JSON
$res->json(['status' => 'success'], 200);

// Send with status codes
$res->send("Not found", 404);
$res->json(['error' => 'Unauthorized'], 401);

// Redirect
$res->redirect('/home');

// Chain methods
$res
    ->withHeader('Content-Type', 'application/json')
    ->json(['message' => 'Success'], 201);
```

---

## Complete Example

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use RFRoute\Route\Route;
use RFRoute\Http\Request;
use RFRoute\Http\Response;

// ===== Middleware Classes =====

class AuthMiddleware {
    public static function check(Request $req, Response $res) {
        $token = $req->getHeaderLine('Authorization');
        
        if (empty($token)) {
            $res->json(['error' => 'Unauthorized'], 401);
            return false;
        }
        
        // Set user attributes
        $req->withAttribute('user_id', 123);
        $req->withAttribute('user_role', 'admin');
        $req->withAttribute('user_email', 'admin@example.com');
        
        return true;
    }
}

class RateLimitMiddleware {
    public function __construct(private int $limit = 100) {}
    
    public function check(Request $req, Response $res) {
        $ip = $req->getServerParams()['REMOTE_ADDR'] ?? '';
        
        // Set rate limit attribute
        $req->withAttribute('rate_limit', $this->limit);
        
        error_log("Rate limit check: {$this->limit} requests from $ip");
        return true;
    }
}

// ===== Controllers =====

class UserController {
    public function index(Request $req, Response $res) {
        $userId = $req->getAttribute('user_id');
        $res->json(['users' => ['Alice', 'Bob'], 'requested_by' => $userId]);
    }
    
    public function show(Request $req, Response $res) {
        $id = $req->getAttribute('id');
        $res->json(['id' => $id, 'name' => "User $id"]);
    }
    
    public function store(Request $req, Response $res) {
        $data = $req->input();
        $userId = $req->getAttribute('user_id');
        
        $res->json([
            'success' => true,
            'created_by' => $userId,
            'data' => $data
        ], 201);
    }
}

// ===== Error Handlers =====

Route::setErrorHandler(function($e, $req, $res) {
    $res->json(['error' => $e->getMessage()], 500);
});

Route::setNotFoundHandler(function($req, $res) {
    $res->json(['error' => 'Route not found'], 404);
});

// ===== Routes =====

Route::middleware([
    function(Request $req, Response $res) {
        $req->withAttribute('request_id', uniqid('req_'));
        error_log("Request: " . $req->getMethod() . " " . $req->getUri());
        return true;
    }
], function() {
    // Public routes
    Route::get('/', function(Request $req, Response $res) {
        $requestId = $req->getAttribute('request_id');
        $res->json(['message' => 'Welcome', 'request_id' => $requestId]);
    });

    // API routes
    Route::group('/api', function() {
        // Public endpoints
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        
        // Protected endpoints
        Route::post('/users', [UserController::class, 'store'], [
            'AuthMiddleware@check',
            new RateLimitMiddleware(10)
        ]);
        
        // Admin group
        Route::group('/admin', function() {
            Route::delete('/users/{id}', function(Request $req, Response $res) {
                $id = $req->getAttribute('id');
                $userId = $req->getAttribute('user_id');
                $res->json(['deleted' => $id, 'deleted_by' => $userId]);
            });
        }, [
            'AuthMiddleware@check',
            function(Request $req, Response $res) {
                $role = $req->getAttribute('user_role');
                return $role === 'admin' ? true : false;
            }
        ]);
    });
});

// ===== Dispatch =====

Route::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
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

## Version History

### v1.0.7
* ✅ Added middleware closure with return value control (true/false)
* ✅ Support for multiple middleware formats (closure, static class, instance)
* ✅ Fixed optional parameter support with `{?param}` syntax
* ✅ **Added request attribute persistence across middleware chain**
* ✅ Middleware can now set attributes accessible in route handlers
* ✅ Improved middleware execution flow
* ✅ Full backward compatibility

### v1.0.7
* Previous version

---

## License
This library is licensed under the [MIT License](LICENSE).