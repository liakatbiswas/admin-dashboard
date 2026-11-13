<?php

namespace App\Http\Controllers\Admin;

use App\Actions\EnrollStudent;
use App\Actions\LinkGuardian;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdmissionRequest;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return view('students.index', ['students' => Student::with(['user', 'guardianUser'])
            ->when($request->filled('q'), fn ($query) => $query->where('student_number', 'like', '%'.$request->input('q').'%'))
            ->latest()->paginate(20)->withQueryString()]);
    }

    public function create(): View
    {
        return view('students.create', ['years' => AcademicYear::orderByDesc('year')->get(),
            'classrooms' => Classroom::with('academicYear')->where('is_active', true)->get()]);
    }

    public function show(Student $student): View
    {
        return view('students.show', ['student' => $student->load(['user', 'guardianUser', 'enrollments.classroom.academicYear']),
            'classrooms' => Classroom::with('academicYear')->where('is_active', true)->get()]);
    }

    public function store(AdmissionRequest $request, EnrollStudent $enroll): Response
    {
        $placement = self::placement($request) + $request->validate([
            'admission_year' => ['required', 'integer', 'between:1900,2200'],
            'admission_grade' => ['required', 'integer', Rule::in(Classroom::GRADES)],
            'admission_serial' => ['required', 'integer', 'between:1,99999'],
        ]);
        $classroom = Classroom::findOrFail($placement['classroom_id']);
        abort_unless($classroom->academic_year_id === $request->integer('academic_year_id') && $classroom->grade === $request->integer('grade'), 422);
        $data = $request->recordData();
        $photo = $request->file('photo')?->store('admission-photos', 'local');
        if ($request->hasFile('photo') && ! $photo) {
            throw ValidationException::withMessages(['photo' => __('The photo could not be uploaded.')]);
        }
        try {
            $result = $enroll->handle($request->user(), $data + ['photo_path' => $photo], $placement);
        } catch (\Throwable $exception) {
            if ($photo) {
                Storage::disk('local')->delete($photo);
            }
            throw $exception;
        }

        return $this->credentials($result['student'], $result['student']->user, $result['password']);
    }

    public function enroll(Request $request, Student $student, EnrollStudent $enroll): Response
    {
        $placement = self::placement($request) + $student->only(['admission_year', 'admission_grade', 'admission_serial']);
        $result = $enroll->handle($request->user(), [], $placement, existing: $student);

        return $this->credentials($result['student'], $result['student']->user, $result['password']);
    }

    public function guardian(Request $request, Student $student, LinkGuardian $link): Response
    {
        $data = $request->validate(['account_lookup' => ['nullable', 'string', 'max:255'],
            'verified' => ['accepted'], 'reason' => ['required', 'string', 'max:1000']]);
        $accountId = null;
        if (! empty($data['account_lookup'])) {
            $lookup = trim($data['account_lookup']);
            $matches = User::query()->where(fn ($query) => $query->where('email', Str::lower($lookup))->orWhere('phone', User::normalizePhone($lookup)))->limit(2)->get();
            if ($matches->count() !== 1) {
                throw ValidationException::withMessages(['account_lookup' => __('Enter the email or mobile of one existing account.')]);
            }
            $accountId = $matches->first()->id;
        }
        $result = $link->handle($student, $request->user(), $accountId, $data['reason']);

        return $this->credentials($student, $result['user'], $result['password']);
    }

    public function resetPassword(Request $request, Student $student): Response
    {
        $data = $request->validate(['account' => ['required', Rule::in(['student', 'guardian'])], 'reason' => ['required', 'string', 'max:1000']]);
        $targetId = $data['account'] === 'student' ? $student->user_id : $student->guardian_user_id;
        abort_unless($targetId, 422);
        $password = Str::password(20);
        $user = DB::transaction(function () use ($request, $targetId, $password, $data): User {
            $user = User::whereKey($targetId)->lockForUpdate()->firstOrFail();
            abort_if($user->is($request->user()) || $user->hasRole('super-admin'), 403);
            abort_unless($request->user()->hasRole('super-admin') || ! $user->requiresAdministrativeProfile(), 403);
            $user->forceFill(['password' => $password, 'must_change_password' => true, 'temporary_password_expires_at' => now()->addHours(24),
                'auth_version' => $user->auth_version + 1, 'remember_token' => null])->save();
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }
            if ($user->email) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }
            activity('security')->causedBy($request->user())->performedOn($user)->withProperties(['reason' => $data['reason']])->log('Student or guardian temporary password reset');

            return $user;
        });

        return $this->credentials($student, $user, $password);
    }

    public static function placement(Request $request): array
    {
        return $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'], 'roll' => ['required', 'integer', 'between:1,9999'],
            'group' => ['nullable', Rule::in(Classroom::GROUPS)], 'documents_checked' => ['accepted'],
            'reason' => ['required', 'string', 'max:1000'], 'override_reason' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function credentials(Student $student, User $user, #[\SensitiveParameter] ?string $password): Response
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return response()->view('students.credentials', compact('student', 'user', 'password'))->header('Cache-Control', 'no-store, private');
    }
}
