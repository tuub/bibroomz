<?php

namespace App\Http\Requests\Admin;

use App\Models\UserGroup;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class ImportUsersRequest extends FormRequest
{
    public function rules()
    {
        $date = function (string $attribute, mixed $value, Closure $fail) {
            try {
                CarbonImmutable::parseFromLocale($value, app()->getLocale());
            } catch (InvalidFormatException) {
                $fail("The {$attribute} is invalid.");
            }
        };

        return [
            'users' => ['required', 'array'],
            'users.*.name' => ['required', 'string'],

            'valid_from_date' => ['nullable', 'date', 'prohibits:valid_from_text'],
            'valid_until_date' => ['nullable', 'date', 'prohibits:valid_until_text'],

            'valid_from_text' => ['nullable', $date, 'prohibits:valid_from_date'],
            'valid_until_text' => ['nullable', $date, 'prohibits:valid_until_date'],
        ];
    }

    protected function passedValidation()
    {
        $locale = app()->getLocale();

        $valid_from_date = $this->input('valid_from_date');
        $valid_until_date = $this->input('valid_until_date');

        $valid_from_text = $this->input('valid_from_text');
        $valid_until_text = $this->input('valid_until_text');

        if ($valid_from_date) {
            $valid_from = CarbonImmutable::parse($valid_from_date);
        } elseif ($valid_from_text) {
            $valid_from = CarbonImmutable::parseFromLocale($valid_from_text, $locale);
        } else {
            $valid_from = CarbonImmutable::now();
        }

        if ($valid_until_date) {
            $valid_until = CarbonImmutable::parse($valid_until_date);
        } elseif ($valid_until_text) {
            $interval = CarbonInterval::parseFromLocale($valid_until_text, $locale);
            $valid_until = $valid_from->locale($locale)->add($interval);
        } else {
            $valid_until = null;
        }

        $this->merge([
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
        ]);
    }

    public function authorize()
    {
        return $this->user()->can('import', UserGroup::find($this->id));
    }
}
