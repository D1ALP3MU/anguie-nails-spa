<?php

use App\Core\Route;
use App\Constants\Roles;
use App\Controllers\AppointmentController;
use App\Controllers\AuthController;
use App\Controllers\ClientController;
use App\Controllers\ProfessionalController;
use App\Controllers\ServiceController;

/*
|--------------------------------------------------------------------------
| Tabla de rutas
|--------------------------------------------------------------------------
| Cada ruta declara junto a sí misma lo que exige:
|
|   (sin nada)      público.
|   requireAuth()   necesita un token válido.
|   allowRoles(...) además restringe por rol.
|
| Los segmentos {id} se inyectan en el método del controlador por
| nombre, igual que un parámetro llamado $authUser recibe el
| usuario autenticado.
|
| Política general:
|   Público      : catálogo, equipo, login y registro.
|   Autenticado  : citas y perfil propio. El servicio filtra
|                  además por pertenencia.
|   Administrador: alta/baja de servicios y gestión de clientes.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Servicios
    |--------------------------------------------------------------------------
    | El catálogo es público: debe poder consultarse sin cuenta.
    */
    Route::get('/api/services', ServiceController::class, 'index'),
    Route::get('/api/services/{id}', ServiceController::class, 'show'),

    Route::post('/api/services', ServiceController::class, 'store')
        ->allowRoles(Roles::ADMIN),

    Route::put('/api/services/{id}', ServiceController::class, 'update')
        ->allowRoles(Roles::ADMIN),

    Route::delete('/api/services/{id}', ServiceController::class, 'delete')
        ->allowRoles(Roles::ADMIN),

    /*
    |--------------------------------------------------------------------------
    | Profesionales
    |--------------------------------------------------------------------------
    */
    Route::get('/api/professionals', ProfessionalController::class, 'index'),
    Route::get('/api/professionals/{id}', ProfessionalController::class, 'show'),

    /*
    |--------------------------------------------------------------------------
    | Autenticación
    |--------------------------------------------------------------------------
    | Registrarse y ser cliente son lo mismo en este dominio, así que
    | el registro público comparte controlador con el alta de clientes:
    | un único camino que crea el usuario y su perfil en una transacción.
    */
    Route::post('/api/auth/login', AuthController::class, 'login'),
    Route::post('/api/auth/register', ClientController::class, 'store'),

    Route::get('/api/profile', AuthController::class, 'profile')
        ->requireAuth(),

    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    | Ver y editar admiten al propio cliente además del administrador;
    | la comprobación de pertenencia vive en ClientService.
    */
    Route::get('/api/clients', ClientController::class, 'index')
        ->allowRoles(Roles::ADMIN),

    Route::post('/api/clients', ClientController::class, 'store')
        ->allowRoles(Roles::ADMIN),

    Route::get('/api/clients/{id}', ClientController::class, 'show')
        ->requireAuth(),

    Route::put('/api/clients/{id}', ClientController::class, 'update')
        ->requireAuth(),

    Route::delete('/api/clients/{id}', ClientController::class, 'delete')
        ->allowRoles(Roles::ADMIN),

    /*
    |--------------------------------------------------------------------------
    | Citas
    |--------------------------------------------------------------------------
    | Un cliente solo ve y modifica las suyas: AppointmentService
    | filtra por el id_cliente que resuelve desde el token.
    */
    Route::get('/api/appointments', AppointmentController::class, 'index')
        ->requireAuth(),

    Route::post('/api/appointments', AppointmentController::class, 'store')
        ->requireAuth(),

    Route::get('/api/appointments/{id}', AppointmentController::class, 'show')
        ->requireAuth(),

    Route::put('/api/appointments/{id}', AppointmentController::class, 'update')
        ->requireAuth(),

    Route::delete('/api/appointments/{id}', AppointmentController::class, 'delete')
        ->requireAuth(),

];
