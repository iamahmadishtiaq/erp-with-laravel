<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\TeacherAttendance;
use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TeacherController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $teacher = Teacher::where('email', $request->email)->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $teacher->createToken('TeacherToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $teacher,
            'token' => $token
        ], 200);
    }

    public function markStudentAttendance(Request $request)
    {
        $data = $request->validate([
            'roll_number' => 'required|exists:students,roll_number',
            'date' => 'required|date',
            'status' => 'required|in:present,absent',
        ]);

        $student = Student::where('roll_number', $data['roll_number'])->first();
        $attendance = StudentAttendance::updateOrCreate(
            ['roll_number' => $data['roll_number'], 'date' => $data['date']],
            [
                'student_name' => $student->name,
                'status' => $data['status'],
                'percentage' => $this->calculateStudentAttendancePercentage($data['roll_number'])
            ]
        );

        return response()->json(['message' => 'Student attendance marked', 'attendance' => $attendance], 201);
    }

    public function viewAttendance(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();
        $attendances = TeacherAttendance::where('email', $teacher->email)->get();
        $overallPercentage = $this->calculateTeacherAttendancePercentage($teacher->email);
        return response()->json([
            'message' => 'Teacher attendance retrieved',
            'attendances' => $attendances,
            'overall_percentage' => $overallPercentage
        ], 200);
    }

    private function calculateTeacherAttendancePercentage($email)
    {
        $totalDays = TeacherAttendance::where('email', $email)->count();
        $presentDays = TeacherAttendance::where('email', $email)->where('status', 'present')->count();
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    private function calculateStudentAttendancePercentage($roll_number)
    {
        $totalDays = StudentAttendance::where('roll_number', $roll_number)->count();
        $presentDays = StudentAttendance::where('roll_number', $roll_number)->where('status', 'present')->count();
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    public function createTest(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required',
            'marks' => 'required|integer',
            'grade' => 'required',
        ]);

        $data['teacher_id'] = Auth::guard('teacher')->user()->id;
        $mark = Mark::create($data);
        return response()->json(['message' => 'Test created', 'mark' => $mark], 201);
    }

    public function updateTest(Request $request, $id)
    {
        $mark = Mark::findOrFail($id);
        $data = $request->validate([
            'marks' => 'required|integer',
            'grade' => 'required',
        ]);

        $mark->update($data);
        return response()->json(['message' => 'Test updated', 'mark' => $mark], 200);
    }

    public function viewStudentDetails($student_id)
    {
        $student = Student::findOrFail($student_id);
        return response()->json(['message' => 'Student details retrieved', 'student' => $student], 200);
    }
}