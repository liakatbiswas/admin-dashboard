<?php

namespace App\Actions;

use App\Models\AdmissionApplication;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EnrollStudent
{
    /** @return array{student: Student, password: ?string} */
    public function handle(User $actor, array $data, array $placement, ?AdmissionApplication $application = null, ?Student $existing = null): array
    {
        abort_unless($actor->can('students.rolls.manage'), 403);

        return DB::transaction(function () use ($actor, $data, $placement, $application, $existing): array {
            $classroom = Classroom::with('academicYear')->whereKey($placement['classroom_id'])->lockForUpdate()->firstOrFail();
            $this->check($classroom->is_active, 'classroom_id', 'This classroom is inactive.');
            $this->check($classroom->allowsGroup($placement['group'] ?? null), 'group', 'Select a valid group for this class.');
            $this->check(! $classroom->enrollments()->where('roll', $placement['roll'])->exists(), 'roll', 'This roll is already assigned in the classroom.');
            $full = $classroom->enrollments()->count() >= $classroom->capacity;
            $this->check(! $full || ($actor->hasRole('super-admin') && filled($placement['override_reason'] ?? null)), 'classroom_id', 'This classroom is full. A Super Admin must supply an override reason.');
            if ($application) {
                $application = AdmissionApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
                $this->check($application->status === 'selected' && $application->published_at !== null, 'application', 'Publish the selected application before confirming admission.');
                $this->check($application->academic_year_id === $classroom->academic_year_id && $application->grade === $classroom->grade
                    && $application->selected_shift === $classroom->shift, 'classroom_id', 'The classroom must match the application year, class and selected shift.');
                $data = $application->only(['name_bn', 'name_en', 'details', 'guardian', 'photo_path']);
                $year = $classroom->academicYear->year;
                $grade = $classroom->grade;
            } else {
                $year = (int) $placement['admission_year'];
                $grade = (int) $placement['admission_grade'];
                $this->check($year <= $classroom->academicYear->year, 'admission_year', 'Admission year cannot be later than the enrollment year.');
            }
            if ($existing) {
                $student = Student::whereKey($existing->id)->lockForUpdate()->firstOrFail();
                $this->check($student->admission_year == $year && $student->admission_grade == $grade
                    && $student->admission_serial == $placement['admission_serial'], 'existing_student_id', 'The supplied admission identity does not match the existing student.');
            } else {
                DB::table('student_sequences')->insertOrIgnore(['year' => $year, 'grade' => $grade, 'last_number' => 0]);
                $sequence = DB::table('student_sequences')->where(['year' => $year, 'grade' => $grade])->lockForUpdate()->first();
                $serial = $application ? $sequence->last_number + 1 : (int) $placement['admission_serial'];
                $this->check($serial >= 1 && $serial <= 99999, 'admission_serial', 'Admission serial must be between 1 and 99999.');
                $number = sprintf('NGHS%04d%02d%05d', $year, $grade, $serial);
                $this->check(! Student::where('student_number', $number)->exists(), 'admission_serial', 'This Student ID exists. Select the existing student record.');
                DB::table('student_sequences')->where('id', $sequence->id)->update(['last_number' => max($serial, $sequence->last_number)]);
                $student = Student::create(collect($data)->only(['name_bn', 'name_en', 'details', 'guardian', 'photo_path'])->all() + [
                    'student_number' => $number, 'admission_year' => $year, 'admission_grade' => $grade,
                    'admission_serial' => $serial, 'admission_application_id' => $application?->id,
                ]);
            }
            $this->check(! $student->enrollments()->where('academic_year_id', $classroom->academic_year_id)->exists(), 'existing_student_id', 'This student is already enrolled for the academic year.');
            $password = null;
            if (! $student->user_id) {
                $account = User::withTrashed()->where('student_id', $student->student_number)->lockForUpdate()->first();
                if ($account) {
                    $this->check(! $account->trashed() && $account->is_active && $account->hasRole('student'), 'existing_student_id', 'The existing Student ID account cannot be linked.');
                } else {
                    $password = Str::password(20);
                    $account = (new User)->forceFill(['name' => $student->name_en, 'email' => null, 'student_id' => $student->student_number,
                        'password' => $password, 'is_active' => true, 'must_change_password' => true, 'temporary_password_expires_at' => now()->addHours(24)]);
                    $account->save();
                    $account->assignRole('student');
                }
                $student->update(['user_id' => $account->id]);
            }
            $enrollment = Enrollment::create([
                'student_id' => $student->id, 'academic_year_id' => $classroom->academic_year_id,
                'classroom_id' => $classroom->id, 'roll' => $placement['roll'], 'group' => $placement['group'] ?? null,
                'created_by' => $actor->id, 'override_reason' => $full ? $placement['override_reason'] : null,
            ]);
            if ($application) {
                $application->update(['status' => 'admitted']);
            }
            activity('admissions')->causedBy($actor)->performedOn($student)->withProperties([
                'enrollment_id' => $enrollment->id, 'application_id' => $application?->id,
                'roll' => $enrollment->roll, 'classroom_id' => $classroom->id,
                'academic_year_id' => $classroom->academic_year_id, 'student_number' => $student->student_number,
                'reason' => $placement['reason'], 'override_reason' => $enrollment->override_reason,
            ])->log($application ? 'Admission confirmed' : 'Existing student enrolled');

            return ['student' => $student, 'password' => $password];
        }, 3);
    }

    private function check(bool $condition, string $field, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => __($message)]);
        }
    }
}
