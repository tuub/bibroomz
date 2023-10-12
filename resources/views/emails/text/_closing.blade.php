@php
    use App\Models\Institution;
    use App\Models\Resource;

    $closable = $closing->closable;
    $institution = $closable instanceof Institution ? $closable : $closable->resource_group->institution;
@endphp

- @lang('email.closing.description'): {{ $closing->description }}
- @lang('email.general.start.label'): {{ \Carbon\Carbon::parse($closing->start)->format('d.m.Y H:i') }}
- @lang('email.general.end.label'): {{ \Carbon\Carbon::parse($closing->end)->format('d.m.Y H:i') }}
- @lang('email.general.institution.label'): {{ $institution->title }}
@if ($closable instanceof Resource)
    - @lang('email.general.resource.label'): {{ $closable->title }}
@endif

## @lang('email.closing.affected_happenings')

@foreach ($happenings as $happening)
    @include('emails.text._happening')
@endforeach
{{-- Empty Line --}}
