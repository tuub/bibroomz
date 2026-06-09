<?php

use App\Http\Requests\CalendarEntriesRequest;

covers(CalendarEntriesRequest::class);

test('calendar resource titles are written as text not html', function (): void {
    $source = (string) file_get_contents(base_path('resources/js/Composables/Calendar.js'));

    expect($source)
        ->toContain('title.textContent = translate(resourceInfo.resource.extendedProps.translations.title);')
        ->not->toContain('title.innerHTML = translate(resourceInfo.resource.extendedProps.translations.title);');
});
