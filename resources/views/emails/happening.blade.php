@php
    $user = $happening->user1->name;
    $confirmer = $happening->user2?->name ?? $happening->confirmer;

    $resource = $happening->resource;
    $institution = $resource->institution;
@endphp
    ID: {{ $happening->id }}
    Institution: {{ $institution->title }} ({{ $institution->short_title }})
    Resource: {{ $resource->title }}
    Start: {{ $happening->start }}
    End: {{ $happening->end }}
    User: {{ $user }}
@if ($confirmer)
    Confirmer: {{ $confirmer }}
@endif
    {{ $happening->isConfirmed() ? 'Confirmed' : 'Pending Confirmation' }}
    {{-- Empty Line --}}
