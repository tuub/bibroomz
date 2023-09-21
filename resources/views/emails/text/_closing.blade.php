@php
    use App\Models\Institution;
    use App\Models\Resource;

    $closable = $closing->closable;
    $institution = $closable instanceof Institution ? $closable : $closable->institution;
@endphp

- @lang('email.closing.data.start.label'): {{ \Carbon\Carbon::parse($closing->start)->format('d.m.Y H:i') }}
- @lang('email.closing.data.end.label'): {{ \Carbon\Carbon::parse($closing->end)->format('d.m.Y H:i') }}
- @lang('email.closing.data.institution.label'): {{ $institution->title }}
@if ($closable instanceof Resource)
    - @lang('email.closing.data.resource.label'): {{ $closable->title }}
@endif

## @lang('email.closing.affected')

@foreach ($happenings as $happening)
    @include('emails.text._happening')
@endforeach

{{-- Empty Line --}}
