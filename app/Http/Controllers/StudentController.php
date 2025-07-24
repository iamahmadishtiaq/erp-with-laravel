<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    public function viewDetails(Request $request)
    {
        $student = Auth::guard('student')->user();
        $attendances = StudentAttendance::where('roll_number', $student->roll_number)->get();
        $overallPercentage = $this->calculateStudentAttendancePercentage($student->roll_number);
        return response()->json([
            'message' => 'Student details retrieved',
            'student' => $student,
            'attendances' => $attendances,
            'overall_percentage' => $overallPercentage
        ], 200);
    }

    private function calculateStudentAttendancePercentage($roll_number)
    {
        $totalDays = StudentAttendance::where('roll_number', $roll_number)->count();
        $presentDays = StudentAttendance::where('roll_number', $roll_number)->where('status', 'present')->count();
        return $totalDays > 0 ? ($presentDays / $totalDays) * 100 : 0;
    }
}