<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeacherController;

Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminController::class, 'login']);
    Route::post('/teacher', [AdminController::class, 'createTeacher']);
    Route::post('/teacher-attendance', [AdminController::class, 'markTeacherAttendance']);
    Route::get('/teachers', [AdminController::class, 'listTeachers']);
    Route::put('/teacher/{id}', [AdminController::class, 'updateTeacher']);
    Route::delete('/teacher/{id}', [AdminController::class, 'deleteTeacher']);
    Route::post('/student', [AdminController::class, 'createStudent']);
    Route::put('/student/{id}', [AdminController::class, 'updateStudent']);
    Route::delete('/student/{id}', [AdminController::class, 'deleteStudent']);
    Route::get('/students', [AdminController::class, 'listStudents']);
    Route::post('/assign-course/{teacher_id}', [AdminController::class, 'assignCourse']);
    Route::put('/marks/{id}', [AdminController::class, 'updateMarks']);
    Route::post('/reset-all-data', [AdminController::class, 'resetAllData']);
    Route::get('/class-11-students', [AdminController::class, 'listClass11Students']);
    Route::get('/class-12-students', [AdminController::class, 'listClass12Students']);
});

Route::prefix('teacher')->group(function () {
    Route::post('/login', [TeacherController::class, 'login']);
    Route::post('/student-attendance', [TeacherController::class, 'markStudentAttendance']);
    Route::get('/attendance', [TeacherController::class, 'viewAttendance']);
    Route::post('/create-test', [TeacherController::class, 'createTest']);
    Route::put('/update-test/{id}', [TeacherController::class, 'updateTest']);
    Route::get('/student-details/{student_id}', [TeacherController::class, 'viewStudentDetails']);
});