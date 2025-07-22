<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Attendance;
use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $student = Student::where('email', $request->email)->first();

    if (!$student || !Hash::check($request->password, $student->password)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $token = $student->createToken('StudentToken')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => $student,
        'token' => $token
    ], 200);
}

    public function viewDetails()
    {
        $student = Auth::guard('student')->user();
        $attendances = Attendance::where('student_id', $student->id)->get();
        $marks = Mark::where('student_id', $student->id)->get();
        return response()->json(['student' => $student, 'attendances' => $attendances, 'marks' => $marks], 200);
    }
}