<?php

namespace App\Http\Controllers\Admin;

use App\Actions\EnrollStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdmissionRequest;
use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdmissionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return view('admissions.index', ['applications' => AdmissionApplication::with('academicYear')
            ->when($request->filled('q'), fn ($query) => $query->where('number', 'like', '%'.$request->input('q').'%'))
            ->latest()->paginate(20)->withQueryString()]);
    }

    public function show(AdmissionApplication $application): View
    {
        return view('admissions.review', ['application' => $application->load('academicYear'),
            'years' => AcademicYear::orderByDesc('year')->get(),
            'classrooms' => Classroom::with('academicYear')->where('academic_year_id', $application->academic_year_id)->where('grade', $application->grade)->where('is_active', true)->get()]);
    }

    public function update(AdmissionRequest $request, AdmissionApplication $application): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:1000']])['reason'];
        $photo = $request->file('photo')?->store('admission-photos', 'local');
        if ($request->hasFile('photo') && ! $photo) {
            throw ValidationException::withMessages(['photo' => __('The photo could not be uploaded.')]);
        }
        try {
            DB::transaction(function () use ($application, $request, $reason, $photo): void {
                $application = AdmissionApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
                abort_if($application->status === 'admitted' || $application->published_at !== null, 422, __('Published or admitted applications cannot be edited.'));
                if ($request->integer('academic_year_id') !== $application->academic_year_id) {
                    throw ValidationException::withMessages(['academic_year_id' => __('The application year cannot change after its number has been issued.')]);
                }
                $old = $application->only(['academic_year_id', 'grade', 'name_bn', 'name_en', 'details', 'guardian']);
                $application->fill($request->recordData());
                if ($photo) {
                    $application->photo_path = $photo;
                }
                $application->status = 'submitted';
                $application->selected_shift = null;
                $application->save();
                activity('admissions')->causedBy($request->user())->performedOn($application)->withProperties(['reason' => $reason, 'old' => $old])->log('Application corrected');
            });
        } catch (\Throwable $exception) {
            if ($photo) {
                Storage::disk('local')->delete($photo);
            }
            throw $exception;
        }

        return back()->with('success', __('Application updated.'));
    }

    public function select(Request $request, AdmissionApplication $application): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['selected', 'rejected'])],
            'selected_shift' => ['required_if:status,selected', 'nullable', Rule::in(Classroom::SHIFTS)], 'reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $application, $data): void {
            $application = AdmissionApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_if($application->status === 'admitted', 422);
            $application->update(['status' => $data['status'], 'selected_shift' => $data['status'] === 'selected' ? $data['selected_shift'] : null, 'published_at' => null]);
            activity('admissions')->causedBy($request->user())->performedOn($application)->withProperties($data)->log('Application selection changed');
        });

        return back()->with('success', __('Selection saved.'));
    }

    public function publish(Request $request, AdmissionApplication $application): RedirectResponse
    {
        DB::transaction(function () use ($request, $application): void {
            $application = AdmissionApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_unless($application->status === 'selected', 422);
            $application->update(['published_at' => now()]);
            activity('admissions')->causedBy($request->user())->performedOn($application)->log('Selection published');
        });

        return back()->with('success', __('Selection published.'));
    }

    public function admit(Request $request, AdmissionApplication $application, EnrollStudent $enroll): Response
    {
        $placement = StudentController::placement($request);
        $result = $enroll->handle($request->user(), [], $placement, $application);
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return response()->view('students.credentials', $result + ['user' => $result['student']->user])->header('Cache-Control', 'no-store, private');
    }

    public function photo(AdmissionApplication $application): BinaryFileResponse
    {
        abort_unless($application->photo_path && Storage::disk('local')->exists($application->photo_path), 404);

        return response()->file(Storage::disk('local')->path($application->photo_path), ['Cache-Control' => 'no-store, private']);
    }
}
