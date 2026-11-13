<?php

namespace App\Http\Controllers;

use App\Actions\SelectionListPdf;
use App\Http\Requests\AdmissionRequest;
use App\Models\AcademicYear;
use App\Models\AdmissionApplication;
use App\Models\AdmissionOnOff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    public function create(): View
    {
        return view(AdmissionOnOff::isOn() ? 'admissions.create' : 'auth.registration-closed', [
            'years' => AcademicYear::orderByDesc('year')->get(),
        ]);
    }

    public function store(AdmissionRequest $request): RedirectResponse
    {
        $photo = $request->file('photo')?->store('admission-photos', 'local');
        if ($request->hasFile('photo') && ! $photo) {
            throw ValidationException::withMessages(['photo' => __('The photo could not be uploaded.')]);
        }
        try {
            $application = DB::transaction(function () use ($request, $photo): AdmissionApplication {
                $settings = AdmissionOnOff::whereKey(1)->lockForUpdate()->firstOrFail();
                abort_unless($settings->is_on && ($settings->deadline === null || now()->lt($settings->deadline)), 403, __('The registration period has ended.'));
                $data = $request->recordData();
                $year = AcademicYear::whereKey($data['academic_year_id'])->lockForUpdate()->firstOrFail();
                if (! $year->classrooms()->where('grade', $data['grade'])->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['grade' => __('No active class is available for this academic year.')]);
                }
                abort_if($year->application_sequence >= 99999, 422, __('Application sequence exhausted.'));
                $year->increment('application_sequence');

                return AdmissionApplication::create($data + ['number' => sprintf('NGHS-APP-%04d-%05d', $year->year, $year->application_sequence), 'photo_path' => $photo]);
            });
        } catch (\Throwable $exception) {
            if ($photo) {
                Storage::disk('local')->delete($photo);
            }
            throw $exception;
        }

        return redirect(URL::temporarySignedRoute('admissions.receipt', now()->addDays(7), ['application' => $application->id]));
    }

    public function receipt(Request $request, AdmissionApplication $application): Response
    {
        $response = response()->view('admissions.receipt', ['application' => $application->load('academicYear')])->header('Cache-Control', 'no-store, private');
        if ($request->boolean('download')) {
            $response->header('Content-Disposition', 'attachment; filename="'.$application->number.'.html"');
        }

        return $response;
    }

    public function selected(Request $request, SelectionListPdf $pdf): View|Response
    {
        $request->validate(['year' => ['nullable', 'integer', 'exists:academic_years,id']]);
        $query = AdmissionApplication::with('academicYear')->whereNotNull('published_at')->whereIn('status', ['selected', 'admitted'])
            ->when($request->filled('year'), fn ($query) => $query->where('academic_year_id', $request->integer('year')))->orderBy('number');
        if ($request->boolean('pdf')) {
            return response($pdf->render($query->get()), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="selected-students.pdf"']);
        }

        return view('admissions.selected', ['applications' => $query->paginate(50)->withQueryString(), 'years' => AcademicYear::orderByDesc('year')->get()]);
    }
}
