<!doctype html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $application->number }}</title>
<style>body{font-family:Arial,sans-serif;max-width:850px;margin:30px auto;padding:20px;line-height:1.6}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ccc;padding:8px;text-align:left}@media print{nav{display:none}body{margin:0}}</style></head><body>
<nav><button onclick="window.print()">{{ __('Print') }}</button> <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('admissions.receipt', \Carbon\Carbon::createFromTimestamp((int) request('expires')), ['application' => $application->id, 'download' => 1]) }}">{{ __('Download application') }}</a></nav>
<h1>Nasirabad Girls’ High School</h1><h2>{{ __('Admission application') }} — {{ $application->number }}</h2>
<p>{{ __('Keep this private receipt link. It expires after seven days; contact the school office for a copy later.') }}</p>
<table><tbody>
@foreach (['Academic year' => $application->academicYear->year, 'Class' => $application->grade, 'Name in Bangla' => $application->name_bn, 'Name in English' => $application->name_en, 'Preferred shift' => __($application->preferred_shift), 'Preferred group' => __($application->preferred_group ?? 'Not applicable')] as $label => $value)
<tr><th>{{ __($label) }}</th><td>{{ $value }}</td></tr>
@endforeach
@foreach ($application->details as $label => $value)<tr><th>{{ __(ucwords(str_replace('_', ' ', $label))) }}</th><td>{{ $value }}</td></tr>@endforeach
@foreach ($application->guardian as $label => $value)<tr><th>{{ __('Guardian') }} — {{ __(ucwords(str_replace('_', ' ', $label))) }}</th><td>{{ $value }}</td></tr>@endforeach
</tbody></table></body></html>
