<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Auth;

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

    public function markAttendance(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late',
        ]);

        $exists = Attendance::where('student_id', $data['student_id'])->where('date', $data['date'])->exists();
        if ($exists) {
            return response()->json(['error' => 'Attendance already marked'], 400);
        }

        $data['teacher_id'] = $teacher->id;
        $attendance = Attendance::create($data);
        return response()->json(['message' => 'Attendance marked', 'attendance' => $attendance], 201);
    }

    public function createTest(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required',
            'marks' => 'required|integer',
            'grade' => 'required',
        ]);

        $data['teacher_id'] = $teacher->id;
        $mark = Mark::create($data);
        return response()->json(['message' => 'Test created', 'mark' => $mark], 201);
    }

    public function updateTest(Request $request, $id)
    {
        $teacher = Auth::guard('teacher')->user();
        $mark = Mark::where('id', $id)->where('teacher_id', $teacher->id)->firstOrFail();
        $data = $request->validate([
            'marks' => 'required|integer',
            'grade' => 'required',
        ]);

        $mark->update($data);
        return response()->json(['message' => 'Test updated', 'mark' => $mark], 200);
    }

    public function viewStudentDetails($student_id)
    {
        $teacher = Auth::guard('teacher')->user();
        $student = Student::where('id', $student_id)->where('class', $teacher->class)->firstOrFail();
        $marks = Mark::where('student_id', $student_id)->where('teacher_id', $teacher->id)->get();
        return response()->json(['student' => $student, 'marks' => $marks], 200);
    }
}