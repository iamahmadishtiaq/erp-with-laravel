<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Mark;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('AdminToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $admin,
            'token' => $token
        ], 200);
    }

    public function listTeachers(Request $request)
    {
        $teachers = Teacher::all();
        return response()->json(['message' => 'Teachers retrieved', 'teachers' => $teachers], 200);
    }

    public function createTeacher(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'father_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:teachers',
            'address' => 'required',
            'subject' => 'required',
            'class' => 'required',
            'profile_pic' => 'nullable',
            'password' => 'required|min:6',
        ]);

        $data['password'] = Hash::make($data['password']);
        $teacher = Teacher::create($data);
        return response()->json(['message' => 'Teacher created', 'teacher' => $teacher], 201);
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes',
            'father_name' => 'sometimes',
            'phone_number' => 'sometimes',
            'email' => 'sometimes|email|unique:teachers,email,' . $id,
            'address' => 'sometimes',
            'subject' => 'sometimes',
            'class' => 'sometimes',
            'profile_pic' => 'sometimes',
            'password' => 'sometimes|min:6',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $teacher->update($data);
        return response()->json(['message' => 'Teacher updated', 'teacher' => $teacher], 200);
    }

    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        return response()->json(['message' => 'Teacher deleted'], 200);
    }

    public function listStudents(Request $request)
    {
        $students = Student::all();
        return response()->json(['message' => 'Students retrieved', 'students' => $students], 200);
    }

    public function createStudent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'father_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:students',
            'address' => 'required',
            'section' => 'required',
            'roll_number' => 'required|unique:students',
            'class' => 'required',
            'profile_pic' => 'nullable',
            'password' => 'required|min:6',
        ]);

        $data['password'] = Hash::make($data['password']);
        $student = Student::create($data);
        return response()->json(['message' => 'Student created', 'student' => $student], 201);
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes',
            'father_name' => 'sometimes',
            'phone_number' => 'sometimes',
            'email' => 'sometimes|email|unique:students,email,' . $id,
            'address' => 'sometimes',
            'section' => 'sometimes',
            'roll_number' => 'sometimes|unique:students,roll_number,' . $id,
            'class' => 'sometimes',
            'profile_pic' => 'sometimes',
            'password' => 'sometimes|min:6',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $student->update($data);
        return response()->json(['message' => 'Student updated', 'student' => $student], 200);
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id); // Fixed: Changed Teacher to Student
        $student->delete();
        return response()->json(['message' => 'Student deleted'], 200);
    }

    public function assignCourse(Request $request, $teacher_id)
    {
        $data = $request->validate([
            'name' => 'required',
            'code' => 'required|unique:courses',
        ]);

        $course = Course::create($data);
        $teacher = Teacher::findOrFail($teacher_id);
        $teacher->update(['subject' => $course->name]);
        return response()->json(['message' => 'Course assigned', 'course' => $course], 200);
    }

    public function assignSection(Request $request, $course_id)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);

        $data['course_id'] = $course_id;
        $section = Section::create($data);
        return response()->json(['message' => 'Section assigned', 'section' => $section], 200);
    }

    public function markAttendance(Request $request)
    {
        $data = $request->validate([
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teachers,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late',
        ]);

        $attendance = Attendance::create($data);
        return response()->json(['message' => 'Attendance marked', 'attendance' => $attendance], 201);
    }

    public function updateMarks(Request $request, $id)
    {
        $mark = Mark::findOrFail($id);
        $data = $request->validate([
            'marks' => 'required|integer',
            'grade' => 'required',
        ]);

        $mark->update($data);
        return response()->json(['message' => 'Marks updated', 'mark' => $mark], 200);
    }
}