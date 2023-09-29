@php
use App\Models\Institution;
use App\Models\Resource;

$closable = $closing->closable;
$institution = $closable instanceof Institution ? $closable : $closable->resource_group->institution;
@endphp

<ul>
<li>@lang('email.general.closing.description'): {{ $closing->description }}
<li>@lang('email.general.start.label'): {{ \Carbon\Carbon::parse($closing->start)->format('d.m.Y H:i') }}
<li>@lang('email.general.end.label'): {{ \Carbon\Carbon::parse($closing->end)->format('d.m.Y H:i') }}
<li>@lang('email.general.institution.label'): {{ $institution->title }}

@if ($closable instanceof Resource)
<li>@lang('email.general.resource.label'): {{ $closable->title }}
@endif

</ul>

## @lang('email.closing.affected_happenings')

@foreach ($happenings as $happening)
@include('emails.markdown._happening')
@endforeach

{{-- Empty Line --}}
