<?php

use RFRoute\Route\Route;

// --- GLOBAL ERROR HANDLER ---
Route::setErrorHandler(function($exception, $req, $res) {
    $res->json([
        'error' => true,
        'message' => $exception->getMessage(),
        'trace' => $exception->getTrace()
    ], 500);
});

// --- NOT FOUND HANDLER ---
Route::setNotFoundHandler(function($req, $res) {
    $res->json([
        'error' => true,
        'message' => 'Custom 404 - Route not found'
    ], 404);
});

// --- MAINTENANCE HANDLER ---
Route::setMaintenanceHandler(function($req, $res) {
    $res->json([
        'error' => true,
        'message' => 'Service unavailable - Maintenance mode'
    ], 503);
});

// Enable maintenance mode if needed
// Route::enableMaintenance(true);

// --- GLOBAL MIDDLEWARE ---
Route::middleware([
    function($req, $res) {
        // Example: Log each request
        error_log("Request: " . $req->getMethod() . " " . $req->getUri());
        return true; // return false to stop request
    }
], function() {

    // --- SINGLE ROUTES ---
    Route::get('/', function($req, $res) {
        $res->send("Hello World!");
    });

    Route::post('/submit', function($req, $res) {
        $data = $req->getParsedBody();
        $res->json([
            'success' => true,
            'data' => $data
        ]);
    });

    Route::put('/update', function($req, $res) {
        $res->send("PUT request handled");
    });

    Route::delete('/delete', function($req, $res) {
        $res->send("DELETE request handled");
    });

    Route::any('/any', function($req, $res) {
        $res->send("This route works for any HTTP method");
    });

    // --- ROUTE GROUPS ---
    Route::group('api', function() {

        Route::get('users', function($req, $res) {
            $res->json([
                'users' => ['Alice', 'Bob', 'Charlie']
            ]);
        });

        Route::get('products', function($req, $res) {
            $res->json([
                'products' => ['Laptop', 'Mouse', 'Keyboard']
            ]);
        });

    }, [
        // Group middleware example
        function($req, $res) {
            $headers = $req->getHeaders();
            if (!isset($headers['Authorization'])) {
                $res->json(['error' => 'Unauthorized'], 401);
                return false;
            }
            return true;
        }
    ]);

    // --- ROUTE WITH CONTROLLER ---
    class SampleController {
        public function index($req, $res) {
            $res->send("Hello from controller index");
        }

        public function show($req, $res) {
            $id = $req->getQueryParams()['id'] ?? null;
            $res->json([
                'message' => 'Show user',
                'id' => $id
            ]);
        }
    }

    Route::get('/controller', [SampleController::class, 'index']);
    Route::get('/controller/show', [SampleController::class, 'show']);
});

// Param biasa
Route::get('/user/{id}', function($req, $res) {
    $res->send("User ID: " . $req->getAttribute('id') ?? 1);
});

// Param optional
Route::get('/post/{slug}/{page}', function($req, $res) {
    $res->json([
        "slug" => $req->getAttribute('slug'),
        "page" => $req->getAttribute('page') ?? 1,
    ]);
});

// Param dengan regex
Route::get('/order/{id:\d+}', function($req, $res) {
    $res->send("Order ID (numeric): " . $req->getAttribute('id'));
});

// --- DISPATCH ---
Route::dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
