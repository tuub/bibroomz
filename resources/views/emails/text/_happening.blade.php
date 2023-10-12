@php
    $status = $happening->isVerified() ? 'verified' : 'unverified';
    $verifier = $happening->user2?->name ?? $happening->verifier;
@endphp

- @lang('email.general.start.label'): {{ \Carbon\Carbon::parse($happening->start)->format('d.m.Y H:i') }}
- @lang('email.general.end.label'): {{ \Carbon\Carbon::parse($happening->end)->format('d.m.Y H:i') }}
- @lang('email.general.institution.label'): {{ $happening->resource->resource_group->institution->title }}
- {{ $happening->resource->resource_group->term_singular }}: {{ $happening->resource->title }}
- @lang('email.general.user.label'): {{ $happening->user1->name }}
@if ($verifier)
- @lang('email.general.verifier.label'): {{ $verifier }}
@endif
- @lang('email.general.status.label'): @lang('email.general.status.value.' . $status)
{{-- Empty Line --}}
