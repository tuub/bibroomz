@lang('email.happening.created.title')

{{ $content->title }}

{{ $content->salutation }}

{{ $content->intro }}

@if (str_starts_with($class, 'Closing'))
    @include('emails.text._closing')
@else
    @include('emails.text._happening')
@endif

{{ $content->outro }}

{{ $content->farewell }}
