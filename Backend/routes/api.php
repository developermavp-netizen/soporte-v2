<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API;

// ============================================
// RUTAS PÚBLICAS
// ============================================
Route::post('/auth/register', [API\AuthController::class, 'register']);
Route::post('/auth/login', [API\AuthController::class, 'login']);

Route::get('/health', function() {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'database' => 'MySQL',
        'environment' => env('APP_ENV')
    ]);
});

// ============================================
// RUTAS PROTEGIDAS (requieren token)
// ============================================
Route::middleware('auth:sanctum')->group(function() {
    
    // Auth
    Route::get('/auth/me', [API\AuthController::class, 'me']);
    Route::post('/auth/logout', [API\AuthController::class, 'logout']);
    Route::post('/auth/change-password', [API\AuthController::class, 'changePassword']);
    Route::post('/auth/refresh-token', [API\AuthController::class, 'refreshToken']);
    // En routes/api.php dentro del grupo auth:sanctum
Route::get('/dashboard', [API\OrderController::class, 'dashboard']);
Route::get('/reports/income', [API\OrderController::class, 'incomeReport']);
Route::get('/orders/{id}/summary', [API\OrderController::class, 'ticketSummary']);
    // Catálogos
    Route::apiResource('device-types', API\DeviceTypeController::class);
    Route::apiResource('brands', API\BrandController::class);
    Route::apiResource('statuses', API\StatusController::class);
    
    // Diagnóstico, seguimiento y solución
Route::post('/orders/{id}/diagnosis', [API\OrderController::class, 'saveDiagnosis']);
Route::post('/orders/{id}/follow-up', [API\OrderController::class, 'saveFollowUp']);
Route::post('/orders/{id}/solution', [API\OrderController::class, 'saveSolution']);

// Para admin: roles
// Reemplazar la función anónima por:
Route::get('/roles', [API\RoleController::class, 'index']);
Route::get('/roles/{id}', [API\RoleController::class, 'show']);
Route::post('/roles', [API\RoleController::class, 'store']);
Route::put('/roles/{id}', [API\RoleController::class, 'update']);
Route::delete('/roles/{id}', [API\RoleController::class, 'destroy']);
    // Entidades
    Route::apiResource('customers', API\CustomerController::class);
    Route::apiResource('devices', API\DeviceController::class);
    
    // Búsquedas
    Route::get('/customers/search', [API\CustomerController::class, 'index']);
    Route::get('/devices/search', [API\DeviceController::class, 'index']);
    
    // Órdenes
    Route::apiResource('orders', API\OrderController::class);
    Route::put('/orders/{id}/status', [API\OrderController::class, 'changeStatus']);
    
    // Notas
    Route::get('/orders/{orderId}/notes', [API\OrderNoteController::class, 'index']);
    Route::post('/orders/{orderId}/notes', [API\OrderNoteController::class, 'store']);
    Route::apiResource('notes', API\OrderNoteController::class)->except(['index', 'store']);
    
    // Pagos
    Route::get('/orders/{orderId}/payments', [API\PaymentController::class, 'index']);
    Route::post('/orders/{orderId}/payments', [API\PaymentController::class, 'store']);
    Route::apiResource('payments', API\PaymentController::class)->except(['index', 'store']);
    
    // Entregas
    Route::get('/orders/{orderId}/deliveries', [API\DeliveryController::class, 'index']);
    Route::post('/orders/{orderId}/deliveries', [API\DeliveryController::class, 'store']);
    Route::apiResource('deliveries', API\DeliveryController::class)->except(['index', 'store']);
    
    // ============================================
    // RUTAS SOLO PARA ADMIN
    // ============================================
 // ============================================
// RUTAS SOLO PARA ADMIN
// ============================================
Route::middleware(['auth:sanctum', 'role:ADMIN'])->group(function() {
    // Usuarios - CRUD completo
    Route::apiResource('users', API\UserController::class);
    
    // Roles - usando el controlador que ya creamos
    Route::apiResource('roles', API\RoleController::class);
});
});