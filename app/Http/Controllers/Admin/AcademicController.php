<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AcademicController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['year' => ['nullable', 'integer', 'exists:academic_years,id']]);

        return view('academics.index', [
            'years' => AcademicYear::orderByDesc('year')->get(),
            'classrooms' => Classroom::with('academicYear')->withCount('enrollments')
                ->when($request->filled('year'), fn ($query) => $query->where('academic_year_id', $request->integer('year')))
                ->orderByDesc('academic_year_id')->orderBy('grade')->orderBy('shift')->orderBy('section')->paginate(25)->withQueryString(),
        ]);
    }

    public function year(Request $request): RedirectResponse
    {
        $data = $request->validate(['year' => ['required', 'integer', 'between:1900,2200', 'unique:academic_years,year']]);
        DB::transaction(function () use ($data, $request): void {
            $year = AcademicYear::create($data);
            activity('academics')->causedBy($request->user())->performedOn($year)->log('Academic year created');
        });

        return back()->with('success', __('Academic year created.'));
    }

    public function current(Request $request, AcademicYear $year): RedirectResponse
    {
        DB::transaction(function () use ($request, $year): void {
            AcademicYear::whereNotNull('is_current')->update(['is_current' => null]);
            $year->update(['is_current' => true]);
            activity('academics')->causedBy($request->user())->performedOn($year)->log('Current academic year changed');
        }, 3);

        return back()->with('success', __('Current academic year updated.'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $request): void {
            AcademicYear::whereKey($data['academic_year_id'])->lockForUpdate()->firstOrFail();
            if (Classroom::where(collect($data)->only(['academic_year_id', 'grade', 'shift', 'section'])->all())->exists()) {
                throw ValidationException::withMessages(['section' => __('This class, shift and section already exists for the year.')]);
            }
            $classroom = Classroom::create($data);
            activity('academics')->causedBy($request->user())->performedOn($classroom)->withProperties($data)->log('Classroom created');
        });

        return back()->with('success', __('Classroom saved.'));
    }

    public function update(Request $request, Classroom $classroom): RedirectResponse
    {
        $data = $request->validate(['capacity' => ['required', 'integer', 'between:1,5000'], 'is_active' => ['required', 'boolean']]);
        DB::transaction(function () use ($data, $classroom, $request): void {
            $classroom = Classroom::whereKey($classroom->id)->lockForUpdate()->firstOrFail();
            if ($data['capacity'] < $classroom->enrollments()->count()) {
                throw ValidationException::withMessages(['capacity' => __('Capacity cannot be lower than current enrollment.')]);
            }
            $old = $classroom->only(['capacity', 'is_active']);
            $classroom->update($data);
            activity('academics')->causedBy($request->user())->performedOn($classroom)
                ->withProperties(['old' => $old, 'new' => $data])->log('Classroom updated');
        });

        return back()->with('success', __('Classroom saved.'));
    }

    private function validated(Request $request): array
    {
        if (is_string($request->input('section'))) {
            $request->merge(['section' => strtoupper(trim($request->input('section')))]);
        }

        return $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'grade' => ['required', 'integer', Rule::in(Classroom::GRADES)],
            'shift' => ['required', Rule::in(Classroom::SHIFTS)],
            'section' => ['required', 'string', 'regex:/^[A-Z]{1,3}$/'],
            'capacity' => ['required', 'integer', 'between:1,5000'],
        ]);
    }
}
