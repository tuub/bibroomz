@php
    $confirmer = $happening?->confirmer;
@endphp
ID: {{ $happening->id }}
Start: {{ $happening->start }}
End: {{ $happening->end }}
{{ $confirmer ? 'Confirmer: ' . $confirmer : '' }}
