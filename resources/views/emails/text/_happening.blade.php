@php
    $status = $happening->isVerified() ? 'verified' : 'unverified';
    $verifier = $happening->user2?->name ?? $happening->verifier;
@endphp

- @lang('email.happening.data.start.label'): {{ \Carbon\Carbon::parse($happening->start)->format('d.m.Y H:i') }}
- @lang('email.happening.data.end.label'): {{ \Carbon\Carbon::parse($happening->end)->format('d.m.Y H:i') }}
- @lang('email.happening.data.institution.label'): {{ $happening->resource->institution->title }}
- @lang('email.happening.data.resource.label'): {{ $happening->resource->title }}
- @lang('email.happening.data.user.label'): {{ $happening->user1->name }}
@if ($verifier)
- @lang('email.happening.data.verifier.label'): {{ $verifier }}
@endif
- @lang('email.happening.data.status.label'): @lang('email.happening.data.status.value.' . $status)
{{-- Empty Line --}}
