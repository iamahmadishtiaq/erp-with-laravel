<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\TeacherAttendance;
use App\Models\StudentAttendance;
use App\Models\Course;
use App\Models\Mark;
use App\Models\Class11Student;
use App\Models\Class12Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'class' => 'required|in:11,12',
            'subjects' => 'required|array',
            'subjects.*' => 'required|string',
            'password' => 'required|min:6',
            'profile_pic' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Check unique class-subject combination
        $existing = Teacher::where('class', $data['class'])
            ->whereJsonContains('subjects', array_intersect($data['subjects'], json_decode($existing->subjects ?? '[]', true)))
            ->exists();
        if ($existing) {
            return response()->json(['error' => 'This class-subject combination is already assigned to another teacher'], 400);
        }

        if ($request->hasFile('profile_pic')) {
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $data['profile_pic'] = $path;
        }

        $data['password'] = Hash::make($data['password']);
        $data['subjects'] = json_encode($data['subjects']);
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
            ['teacher_id' => $teacher->id, 'date' => $data['date']],
            [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'email' => $data['email'],
                'status' => $data['status']
            ]
        );

        $attendance->percentage = $this->calculateTeacherAttendancePercentage($teacher->id);
        $attendance->save();

        return response()->json(['message' => 'Teacher attendance marked', 'attendance' => $attendance], 201);
    }

    private function calculateTeacherAttendancePercentage($teacher_id)
    {
        $totalDays = TeacherAttendance::where('teacher_id', $teacher_id)->count();
        $presentDays = TeacherAttendance::where('teacher_id', $teacher_id)->where('status', 'present')->count();
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
            'name' => 'sometimes',
            'father_name' => 'sometimes',
            'phone_number' => 'sometimes',
            'email' => 'sometimes|email|unique:teachers,email,' . $id,
            'address' => 'sometimes',
            'class' => 'sometimes|in:11,12',
            'subjects' => 'sometimes|array',
            'subjects.*' => 'sometimes|string',
            'password' => 'sometimes|min:6',
            'profile_pic' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'sometimes' => 'This field is optional.',
        ]);

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['father_name'])) $updateData['father_name'] = $data['father_name'];
        if (isset($data['phone_number'])) $updateData['phone_number'] = $data['phone_number'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['class'])) $updateData['class'] = $data['class'];
        if (isset($data['subjects'])) {
            $existingSubjects = json_decode($teacher->subjects ?? '[]', true);
            $newSubjects = $data['subjects'];
            $existing = Teacher::where('class', $data['class'] ?? $teacher->class)
                ->where('id', '!=', $id)
                ->whereJsonContains('subjects', array_intersect($newSubjects, $existingSubjects))
                ->exists();
            if ($existing) {
                return response()->json(['error' => 'This class-subject combination is already assigned to another teacher'], 400);
            }
            $updateData['subjects'] = json_encode($newSubjects);
        }
        if (isset($data['password'])) $updateData['password'] = Hash::make($data['password']);
        if ($request->hasFile('profile_pic')) {
            if ($teacher->profile_pic) Storage::disk('public')->delete($teacher->profile_pic);
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $updateData['profile_pic'] = $path;
        }

        $teacher->update($updateData);

        if (isset($data['email']) && $oldEmail !== $data['email']) {
            TeacherAttendance::where('email', $oldEmail)->update([
                'email' => $data['email'],
                'teacher_name' => $updateData['name'] ?? $teacher->name,
                'teacher_id' => $teacher->id
            ]);
            TeacherAttendance::where('teacher_id', $teacher->id)->update([
                'percentage' => $this->calculateTeacherAttendancePercentage($teacher->id)
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
        TeacherAttendance::where('teacher_id', $id)->delete();
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
            'roll_number' => 'required|regex:/^SU[0-9]{4}$/|unique:students,roll_number|unique:class_11_students,roll_number|unique:class_12_students,roll_number',
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

        if ($data['class'] == 11) {
            Class11Student::create(['name' => $data['name'], 'roll_number' => $data['roll_number']]);
        } elseif ($data['class'] == 12) {
            Class12Student::create(['name' => $data['name'], 'roll_number' => $data['roll_number']]);
        }

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
            'roll_number' => 'sometimes|regex:/^SU[0-9]{4}$/|unique:students,roll_number,' . $id . '|unique:class_11_students,roll_number|unique:class_12_students,roll_number',
            'class' => 'sometimes|in:11,12',
            'profile_pic' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'password' => 'sometimes|min:6',
        ], [
            'sometimes' => 'This field is optional.',
        ]);

        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['father_name'])) $updateData['father_name'] = $data['father_name'];
        if (isset($data['phone_number'])) $updateData['phone_number'] = $data['phone_number'];
        if (isset($data['email'])) $updateData['email'] = $data['email'];
        if (isset($data['address'])) $updateData['address'] = $data['address'];
        if (isset($data['roll_number'])) $updateData['roll_number'] = $data['roll_number'];
        if (isset($data['class'])) $updateData['class'] = $data['class'];
        if (isset($data['password'])) $updateData['password'] = Hash::make($data['password']);
        if ($request->hasFile('profile_pic')) {
            if ($student->profile_pic) Storage::disk('public')->delete($student->profile_pic);
            $path = $request->file('profile_pic')->store('profile_pics', 'public');
            $updateData['profile_pic'] = $path;
        }

        $student->update($updateData);

        if (isset($data['class']) && $data['class'] != $student->class) {
            if ($student->class == 11) {
                Class11Student::where('roll_number', $student->roll_number)->delete();
                Class12Student::create(['name' => $updateData['name'] ?? $student->name, 'roll_number' => $updateData['roll_number'] ?? $student->roll_number]);
            } elseif ($student->class == 12) {
                Class12Student::where('roll_number', $student->roll_number)->delete();
                Class11Student::create(['name' => $updateData['name'] ?? $student->name, 'roll_number' => $updateData['roll_number'] ?? $student->roll_number]);
            }
        } elseif (isset($data['name']) || isset($data['roll_number'])) {
            if ($student->class == 11) {
                Class11Student::where('roll_number', $student->roll_number)->update([
                    'name' => $updateData['name'] ?? $student->name,
                    'roll_number' => $updateData['roll_number'] ?? $student->roll_number,
                ]);
            } elseif ($student->class == 12) {
                Class12Student::where('roll_number', $student->roll_number)->update([
                    'name' => $updateData['name'] ?? $student->name,
                    'roll_number' => $updateData['roll_number'] ?? $student->roll_number,
                ]);
            }
        }

        return response()->json(['message' => 'Student updated', 'student' => $student], 200);
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        if ($student->class == 11) {
            Class11Student::where('roll_number', $student->roll_number)->delete();
        } elseif ($student->class == 12) {
            Class12Student::where('roll_number', $student->roll_number)->delete();
        }
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

    public function listClass11Students(Request $request)
    {
        $students = Class11Student::all();
        return response()->json(['message' => 'Class 11 students retrieved', 'students' => $students], 200);
    }

    public function listClass12Students(Request $request)
    {
        $students = Class12Student::all();
        return response()->json(['message' => 'Class 12 students retrieved', 'students' => $students], 200);
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
        DB::table('class_11_students')->truncate();
        DB::table('class_12_students')->truncate();
        return response()->json(['message' => 'All data reset except admin'], 200);
    }
}