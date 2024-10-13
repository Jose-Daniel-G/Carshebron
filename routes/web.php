<?php

use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\HorarioController;

use App\Http\Controllers\WebController;
use Illuminate\Support\Facades\Route;


// Route::get('/', function () {return view('welcome');});
Route::get('/', function () {return view('auth.login');});


Route::middleware(['auth:sanctum',config('jetstream.auth_session'),'verified'])
->group(function () {Route::get('/dashboard', [HomeController::class, 'index'])->name('admin.home');});
// ->group(function () {Route::get('/dashboard', function () {return view('dashboard');})->name('dashboard');});

//RUTAS HORARIOS ADMIN
Route::resource('/admin/horarios', HorarioController::class)->names('admin.horarios')->middleware('auth', 'can:admin.horarios');


// ->middleware('auth','can:show_datos_cursos');
Route::get('/admin/horarios/show_reserva_profesores/{id}', [HomeController::class, 'show_reserva_profesores'])
     ->name('admin.horarios.show_reserva_profesores');
Route::get('/admin/show_reservas/{id}', [HomeController::class, 'show_reservas'])->name('admin.show_reservas')->middleware('auth','can:admin.show_reservas');
Route::get('/admin/horarios/curso/{id}', [HorarioController::class, 'show_datos_cursos'])->name('admin.horarios.show_datos_cursos')->middleware('auth');
// Route::get('/admin/profesores/reportes', [ProfesorController::class, 'reportes'])->name('admin.profesores.reportes');

// CHATGPT
Route::middleware('auth')->group(function () {
     // Rutas para profesores
     Route::get('/admin/profesor/asistencia', [AsistenciaController::class, 'index'])->name('admin.profesores.asistencia');//Registrar Asistencia
     Route::post('/admin/asistencia/registrar', [AsistenciaController::class, 'create'])->name('asistencia.registrar');
 
     // Rutas para secretarias
     Route::get('/admin/secretaria/inasistencias', [AsistenciaController::class, 'show'])->name('admin.secretarias.inasistencias');//Listado de Inacistencias
     Route::post('/admin/asistencia/habilitar/{id}', [AsistenciaController::class, 'habilitarCliente'])->name('asistencia.habilitar');
 });
 