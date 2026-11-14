<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetAttendanceRoster;
use App\Actions\RecordAttendance;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request, GetAttendanceRoster $roster): View
    {
        $data = $request->validate(['classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
            'date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Dhaka')->toDateString()],
            'page' => ['nullable', 'integer', 'min:1']]);
        $date = $data['date'] ?? now('Asia/Dhaka')->toDateString();
        $classroom = isset($data['classroom_id']) ? Classroom::with('academicYear')->findOrFail($data['classroom_id']) : null;
        if ($classroom && substr($date, 0, 4) !== (string) $classroom->academicYear->year) {
            throw ValidationException::withMessages(['date' => __('The attendance date must be within the classroom academic year.')]);
        }
        $rows = $classroom ? $roster->handle($classroom, $date) : collect();
        $page = $request->integer('page', 1);
        $students = new LengthAwarePaginator($rows->forPage($page, 100)->values(), $rows->count(), 100, $page,
            ['path' => $request->url(), 'query' => $request->query()]);

        return view('attendance.index', ['classroom' => $classroom, 'date' => $date, 'students' => $students,
            'classrooms' => Classroom::with('academicYear')->orderBy('academic_year_id')->orderBy('grade')->orderBy('shift')->orderBy('section')->get()]);
    }

    public function store(Request $request, RecordAttendance $record): RedirectResponse
    {
        $data = $request->validate([
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('Asia/Dhaka')->toDateString()],
            'entries' => ['required', 'array', 'min:1', 'max:100'],
            'entries.*' => ['required', 'array:enrollment_id,status'],
            'entries.*.enrollment_id' => ['required', 'integer', 'distinct'],
            'entries.*.status' => ['nullable', Rule::in(Attendance::STATUSES)],
        ]);
        $record->handle($request->user(), Classroom::findOrFail($data['classroom_id']), $data['date'], $data['entries']);

        return redirect()->route('attendance.index', ['classroom_id' => $data['classroom_id'], 'date' => $data['date']])
            ->with('success', __('Attendance saved. Unmarked students remain unrecorded.'));
    }

    public function show(Attendance $attendance): View
    {
        return view('attendance.show', ['attendance' => $attendance->load(['student', 'classroom.academicYear', 'recordedBy']),
            'corrections' => $attendance->corrections()->with('correctedBy')->latest('version')->paginate(20)]);
    }

    public function update(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(Attendance::STATUSES)],
            'expected_version' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $attendance, $data): void {
            $current = Attendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();
            if ($current->version !== (int) $data['expected_version']) {
                throw ValidationException::withMessages(['expected_version' => __('Attendance has changed. Reload the record before correcting it.')]);
            }
            if ($current->status === $data['status']) {
                throw ValidationException::withMessages(['status' => __('Choose a different attendance status.')]);
            }
            $oldStatus = $current->status;
            $current->update(['status' => $data['status'], 'version' => $current->version + 1]);
            $correction = $current->corrections()->create(['old_status' => $oldStatus, 'new_status' => $current->status,
                'version' => $current->version, 'reason' => $data['reason'], 'corrected_by' => $request->user()->id]);
            activity('attendance')->causedBy($request->user())->performedOn($current)
                ->withProperties($correction->only(['old_status', 'new_status', 'reason', 'version']) + ['correction_id' => $correction->id])->log('Attendance corrected');
        }, 3);

        return redirect()->route('attendance.show', $attendance)->with('success', __('Attendance corrected and history saved.'));
    }
}
