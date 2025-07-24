<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\TeacherAttendance;
use App\Models\StudentAttendance;
use App\Models\Course;
use App\Models\Mark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

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

    public function createTeacher(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'father_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:teachers',
            'address' => 'required',
            'subject' => 'required',
            'class' => 'required|in:11,12',
            'password' => 'required|min:6',
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('profile_pic')) {
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $data['profile_pic'] = $path;
        }

        $data['password'] = Hash::make($data['password']);
        $teacher = Teacher::create($data);
        return response()->json(['message' => 'Teacher created', 'teacher' => $teacher], 201);
    }

    public function markTeacherAttendance(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|exists:teachers,email',
            'date' => 'required|date',
            'status' => 'required|in:present,absent',
        ]);

        $teacher = Teacher::where('email', $data['email'])->first();
        $attendance = TeacherAttendance::updateOrCreate(
            ['email' => $data['email'], 'date' => $data['date']],
            [
                'teacher_name' => $teacher->name,
                'status' => $data['status']
            ]
        );

        $attendance->percentage = $this->calculateTeacherAttendancePercentage($data['email']);
        $attendance->save();

        return response()->json(['message' => 'Teacher attendance marked', 'attendance' => $attendance], 201);
    }

    private function calculateTeacherAttendancePercentage($email)
    {
        $totalDays = TeacherAttendance::where('email', $email)->count();
        $presentDays = TeacherAttendance::where('email', $email)->where('status', 'present')->count();
        return $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
    }

    public function listTeachers(Request $request)
    {
        $teachers = Teacher::all();
        return response()->json(['message' => 'Teachers retrieved', 'teachers' => $teachers], 200);
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $oldEmail = $teacher->email;
        $data = $request->validate([
            'name' => 'required',
            'father_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:teachers,email,' . $id,
            'address' => 'required',
            'subject' => 'required',
            'class' => 'required|in:11,12',
            'password' => 'nullable|min:6',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($request->hasFile('profile_pic')) {
            if ($teacher->profile_pic) {
                Storage::disk('public')->delete($teacher->profile_pic);
            }
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $data['profile_pic'] = $path;
        }

        if ($data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $teacher->update($data);

        if ($oldEmail !== $data['email']) {
            TeacherAttendance::where('email', $oldEmail)->update([
                'email' => $data['email'],
                'teacher_name' => $data['name']
            ]);
            TeacherAttendance::where('email', $data['email'])->update([
                'percentage' => $this->calculateTeacherAttendancePercentage($data['email'])
            ]);
        }

        return response()->json(['message' => 'Teacher updated', 'teacher' => $teacher], 200);
    }

    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $email = $teacher->email;
        if ($teacher->profile_pic) {
            Storage::disk('public')->delete($teacher->profile_pic);
        }
        TeacherAttendance::where('email', $email)->delete();
        $teacher->delete();
        return response()->json(['message' => 'Teacher deleted'], 200);
    }

    public function createStudent(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'father_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:students',
            'address' => 'required',
            'roll_number' => 'required|unique:students',
            'class' => 'required|in:11,12',
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'required|min:6',
        ]);

        if ($request->hasFile('profile_pic')) {
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $data['profile_pic'] = $path;
        }

        $data['password'] = Hash::make($data['password']);
        $student = Student::create($data);
        return response()->json(['message' => 'Student created', 'student' => $student], 201);
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $data = $request->validate([
            'name' => 'required',
            'father_name' => 'required',
            'phone_number' => 'required',
            'email' => 'required|email|unique:students,email,' . $id,
            'address' => 'required',
            'roll_number' => 'required|unique:students,roll_number,' . $id,
            'class' => 'required|in:11,12',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'nullable|min:6',
        ]);

        if ($request->hasFile('profile_pic')) {
            if ($student->profile_pic) {
                Storage::disk('public')->delete($student->profile_pic);
            }
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $data['profile_pic'] = $path;
        }

        if ($data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $student->update($data);
        return response()->json(['message' => 'Student updated', 'student' => $student], 200);
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        if ($student->profile_pic) {
            Storage::disk('public')->delete($student->profile_pic);
        }
        $student->delete();
        return response()->json(['message' => 'Student deleted'], 200);
    }

    public function listStudents(Request $request)
    {
        $students = Student::all();
        return response()->json(['message' => 'Students retrieved', 'students' => $students], 200);
    }

    public function assignCourse(Request $request, $teacher_id)
    {
        $data = $request->validate([
            'course_name' => 'required',
        ]);

        $course = Course::create([
            'teacher_id' => $teacher_id,
            'course_name' => $data['course_name'],
        ]);

        return response()->json(['message' => 'Course assigned', 'course' => $course], 201);
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

    public function resetAllData(Request $request)
    {
        DB::table('teachers')->truncate();
        DB::table('students')->truncate();
        DB::table('teacher_attendances')->truncate();
        DB::table('student_attendances')->truncate();
        DB::table('courses')->truncate();
        DB::table('marks')->truncate();
        return response()->json(['message' => 'All data reset except admin'], 200);
    }
}