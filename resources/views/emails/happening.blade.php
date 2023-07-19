@php
    $user = $happening->user1->name;
    $verifier = $happening->user2?->name ?? $happening->verifier;

    $resource = $happening->resource;
    $institution = $resource->institution;
@endphp
    ID: {{ $happening->id }}
    Institution: {{ $institution->title }} ({{ $institution->short_title }})
    Resource: {{ $resource->title }}
    Start: {{ $happening->start }}
    End: {{ $happening->end }}
    User: {{ $user }}
@if ($verifier)
    Verifier: {{ $verifier }}
@endif
    {{ $happening->isVerified() ? 'Verified' : 'Pending Verification' }}
    {{-- Empty Line --}}
