{{ $content->title }}

{{ $content->salutation }}

{{ $content->intro }}

@if (str_starts_with($content->mail_type->key, 'closing'))
@include('emails.text._closing')
@else
@include('emails.text._happening')
@endif

{{ $content->outro }}

{{ $content->farewell }}
