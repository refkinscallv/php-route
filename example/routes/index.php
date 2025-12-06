<?php
use RFRoute\Route\Route;
use RFRoute\Http\{
    Request,
    Response,
};

// ========================================
// MIDDLEWARE CLASSES
// ========================================

// Example: Static method middleware
class AuthMiddleware {
    public static function check(Request $req, Response $res) {
        $token = $req->getHeaderLine('Authorization');
        if (empty($token)) {
            $res->json(['error' => 'Unauthorized'], 401);
            return false; // Stop request
        }
        return true; // Continue to next middleware
    }
}

// Example: Instance method middleware
class RateLimitMiddleware {
    private int $limit;
    
    public function __construct(int $limit = 100) {
        $this->limit = $limit;
    }
    
    public function check(Request $req, Response $res) {
        // Implement rate limiting logic
        error_log("Rate limit check: {$this->limit} requests");
        return true; // Continue
    }
}

// Example: Controller class
class UserController {
    public function index($req, $res) {
        $res->json(['users' => ['Alice', 'Bob', 'Charlie']]);
    }
    
    public function show($req, $res) {
        $id = $req->getAttribute('id');
        $res->json([
            'id' => $id,
            'name' => 'User ' . $id,
            'email' => "user{$id}@example.com"
        ]);
    }
    
    public function store($req, $res) {
        $data = $req->input();
        $res->json([
            'success' => true,
            'message' => 'User created',
            'data' => $data
        ], 201);
    }
}

// ========================================
// ERROR HANDLERS
// ========================================

Route::setErrorHandler(function($exception, $req, $res) {
    $res->json([
        'error' => true,
        'message' => $exception->getMessage(),
        'trace' => $exception->getTrace()
    ], 500);
});

Route::setNotFoundHandler(function($req, $res) {
    $res->json([
        'error' => true,
        'message' => 'Route not found',
        'path' => $req->getUri()->getPath()
    ], 404);
});

Route::setMaintenanceHandler(function($req, $res) {
    $res->json([
        'error' => true,
        'message' => 'Service unavailable - Maintenance mode'
    ], 503);
});

// ========================================
// GLOBAL MIDDLEWARE
// ========================================

Route::middleware([
    // Closure middleware
    function($req, $res) {
        $req->withAttribute('ss', 'ss');
        error_log("Request: " . $req->getMethod() . " " . $req->getUri()->getPath());
        return true;
    }
], function() {

    // ========================================
    // BASIC ROUTES (WITHOUT MIDDLEWARE)
    // ========================================
    
    Route::get('/', function($req, $res) {
        print_r($req->getAttribute('ss'));
        $res->json(['message' => 'Hello World']);
    });

    Route::post('/submit', function($req, $res) {
        $data = $req->input();
        $res->json([
            'success' => true,
            'data' => $data
        ]);
    });

    // ========================================
    // ROUTES WITH PARAMETERS
    // ========================================
    
    // Required parameter
    Route::get('/user/{id}', function($req, $res) {
        $id = $req->getAttribute('id');
        $res->send("User ID: " . $id);
    });

    // Optional parameter - NOW FIXED!
    Route::get('/post/{?slug}', function($req, $res) {
        $slug = $req->getAttribute('slug') ?? 'default-slug';
        $res->json(['slug' => $slug]);
    });

    // Optional with default
    Route::get('/page/{?page:\d+}', function($req, $res) {
        $page = $req->getAttribute('page') ?? 1;
        $res->json(['page' => (int)$page]);
    });

    // Regex parameter
    Route::get('/order/{id:\d+}', function($req, $res) {
        $id = $req->getAttribute('id');
        $res->json(['order_id' => $id]);
    });

    // ========================================
    // PROTECTED ROUTES WITH MIDDLEWARE
    // ========================================
    
    // Route with single closure middleware
    Route::post('/protected', function($req, $res) {
        $res->json(['message' => 'This is protected']);
    }, [
        function($req, $res) {
            if (!$req->getHeaderLine('Authorization')) {
                $res->json(['error' => 'Unauthorized'], 401);
                return false;
            }
            return true;
        }
    ]);

    // Route with static class middleware
    Route::post('/api/secure', function($req, $res) {
        $res->json(['message' => 'Secure route']);
    }, [
        'AuthMiddleware@check' // Static method format
    ]);

    // Route with instance middleware
    $rateLimitMiddleware = new RateLimitMiddleware(50);
    Route::get('/api/limited', function($req, $res) {
        $res->json(['message' => 'Rate limited']);
    }, [
        [$rateLimitMiddleware, 'check'] // Instance method format
    ]);

    // Route with multiple middlewares
    Route::post('/api/admin', function($req, $res) {
        $res->json(['message' => 'Admin panel']);
    }, [
        'AuthMiddleware@check',
        function($req, $res) {
            // Additional admin check
            $role = $req->getHeaderLine('X-User-Role');
            if ($role !== 'admin') {
                $res->json(['error' => 'Forbidden'], 403);
                return false;
            }
            return true;
        }
    ]);

    // ========================================
    // ROUTE GROUPS WITH MIDDLEWARE
    // ========================================
    
    Route::group('api', function() {
        // Controller routes with array format
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);

        // Nested group
        Route::group('admin', function() {
            Route::get('/users', function($req, $res) {
                $res->json(['message' => 'Admin users list']);
            });
            
            Route::delete('/users/{id}', function($req, $res) {
                $id = $req->getAttribute('id');
                $res->json(['message' => "User $id deleted"]);
            });
        }, [
            'AuthMiddleware@check',
            function($req, $res) {
                $role = $req->getHeaderLine('X-User-Role');
                return $role === 'admin' ? true : false;
            }
        ]);

    }, [
        // Group-level middleware (applies to all routes in this group)
        function($req, $res) {
            $version = $req->getHeaderLine('API-Version') ?: '1.0';
            error_log("API Version: $version");
            return true;
        }
    ]);

    // ========================================
    // INSTANCE CONTROLLER ROUTES
    // ========================================
    
    $userController = new UserController();
    Route::get('/instance/users', [$userController, 'index']);

});

// ========================================
// DISPATCH
// ========================================

Route::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
