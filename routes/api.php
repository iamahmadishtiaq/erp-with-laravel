<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\StudentController;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    Route::middleware('admin')->group(function () {
        Route::post('/teacher', [AdminController::class, 'createTeacher']);
        Route::get('/teachers', [AdminController::class, 'listTeachers']);
        Route::put('/teacher/{id}', [AdminController::class, 'updateTeacher']);
        Route::delete('/teacher/{id}', [AdminController::class, 'deleteTeacher']);
        Route::get('/students', [AdminController::class, 'listStudents']);
        Route::post('/student', [AdminController::class, 'createStudent']);
        Route::put('/student/{id}', [AdminController::class, 'updateStudent']);
        Route::delete('/student/{id}', [AdminController::class, 'deleteStudent']);
        Route::post('/course/{teacher_id}', [AdminController::class, 'assignCourse']);
        Route::post('/section/{course_id}', [AdminController::class, 'assignSection']);
        Route::post('/teacher-attendance', [AdminController::class, 'markTeacherAttendance']);
        Route::put('/marks/{id}', [AdminController::class, 'updateMarks']);
    });
});

Route::prefix('teacher')->group(function () {
    Route::post('/login', [TeacherController::class, 'login']);
    Route::middleware('auth:teacher')->group(function () {
        Route::post('/student-attendance', [TeacherController::class, 'markStudentAttendance']);
        Route::get('/attendance', [TeacherController::class, 'viewAttendance']);
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