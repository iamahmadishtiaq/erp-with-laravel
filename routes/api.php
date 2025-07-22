<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    Route::middleware('admin')->group(function () {
        Route::get('/teachers', [AdminController::class, 'listTeachers']);
        Route::post('/teacher', [AdminController::class, 'createTeacher']);
        Route::put('/teacher/{id}', [AdminController::class, 'updateTeacher']);
        Route::delete('/teacher/{id}', [AdminController::class, 'deleteTeacher']);
        Route::get('/students', [AdminController::class, 'listStudents']);
        Route::post('/student', [AdminController::class, 'createStudent']);
        Route::put('/student/{id}', [AdminController::class, 'updateStudent']);
        Route::delete('/student/{id}', [AdminController::class, 'deleteStudent']);
        Route::post('/course/{teacher_id}', [AdminController::class, 'assignCourse']);
        Route::post('/section/{course_id}', [AdminController::class, 'assignSection']);
        Route::post('/attendance', [AdminController::class, 'markAttendance']);
        Route::put('/marks/{id}', [AdminController::class, 'updateMarks']);
    });
});

Route::prefix('teacher')->group(function () {
    Route::post('/login', [TeacherController::class, 'login']);
    Route::middleware('auth:teacher')->group(function () {
        Route::post('/attendance', [TeacherController::class, 'markAttendance']);
        Route::post('/test', [TeacherController::class, 'createTest']);
        Route::put('/test/{id}', [TeacherController::class, 'updateTest']);
        Route::get('/student/{student_id}', [TeacherController::class, 'viewStudentDetails']);
    });
});

Route::prefix('student')->group(function () {
    Route::post('/login', [StudentController::class, 'login']);
    Route::middleware('auth:student')->group(function () {
        Route::get('/details', [StudentController::class, 'viewDetails']);
    });
});