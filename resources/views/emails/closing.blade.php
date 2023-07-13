Closing:
@php
    use App\Models\Institution;
    use App\Models\Resource;

    $closable = $closing->closable;
    $institution = $closable instanceof Institution ? $closable : $closable->institution;
@endphp
    ID: {{ $closing->id }}
    Institution: {{ $institution->title }} ({{ $institution->short_title }})
@if ($closable instanceof Resource)
    Resource: {{ $closable->title }}
@endif
    Start: {{ $closing->start }}
    End: {{ $closing->end }}
