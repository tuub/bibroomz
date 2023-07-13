@include('emails.closing')


Happenings affected:
@foreach ($happenings as $happening)
    @include('emails/happening')
@endforeach

Previously affected:
@foreach ($previously as $happening)
    @include('emails/happening')
@endforeach
