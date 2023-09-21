@php
    $action_uri = $content->action_uri
@endphp
<x-mail::message>

# {{ $content->title }}

{{ $content->salutation }},

{{ $content->intro }}

@if (str_starts_with($class, 'Closing'))
@include('emails.markdown._closing')
@else
@include('emails.markdown._happening')
@endif

{{ $content->outro }}

@if ($content->action_uri)
    <x-mail::button :url="$action_uri">
        {{ $content->action_uri_label }}
    </x-mail::button>
@endif

{{ $content->farewell }}

</x-mail::message>
