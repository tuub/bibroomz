@php
$status = $happening->isVerified() ? 'verified' : 'unverified';
$verifier = $happening->user2?->name ?? $happening->verifier;
@endphp

<ul>
<li>@lang('email.general.start.label'): {{ \Carbon\Carbon::parse($happening->start)->format('d.m.Y H:i') }}</li>
<li>@lang('email.general.end.label'): {{ \Carbon\Carbon::parse($happening->end)->format('d.m.Y H:i') }}</li>
<li>@lang('email.general.institution.label'): {{ $happening->resource->resource_group->institution->title }}</li>
<li>{{ $happening->resource->resource_group->term_singular }}: {{ $happening->resource->title }}</li>
<li>@lang('email.general.user.label'): {{ $happening->user1->name }}</li>

@if ($verifier)
<li>@lang('email.general.verifier.label'): {{ $verifier }}</li>
@endif

<li>@lang('email.general.status.label'): @lang('email.general.status.value.' . $status)</li>
</ul>
{{-- Empty Line --}}
