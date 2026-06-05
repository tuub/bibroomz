<?php

namespace App\Http\Requests\Admin;

use App\Models\UserGroup;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterval;
use Carbon\Exceptions\InvalidFormatException;
use Closure;

class ImportUsersRequest extends AdminRouteRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $date = function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value)) {
                $fail("The {$attribute} is invalid.");

                return;
            }

            try {
                CarbonImmutable::parseFromLocale($value, app()->getLocale());
            } catch (InvalidFormatException) {
                $fail("The {$attribute} is invalid.");
            }
        };

        return [
            'id' => ['required', 'uuid', 'exists:user_groups,id'],
            'users' => ['required', 'array'],
            'users.*.name' => ['required', 'string'],

            'valid_from_date' => ['nullable', 'date', 'prohibits:valid_from_text'],
            'valid_until_date' => ['nullable', 'date', 'prohibits:valid_until_text'],

            'valid_from_text' => ['nullable', $date, 'prohibits:valid_from_date'],
            'valid_until_text' => ['nullable', $date, 'prohibits:valid_until_date'],
        ];
    }

    protected function passedValidation(): void
    {
        $locale = app()->getLocale();

        $validFromDate = $this->inputString('valid_from_date');
        $validUntilDate = $this->inputString('valid_until_date');

        $validFromText = $this->inputString('valid_from_text');
        $validUntilText = $this->inputString('valid_until_text');

        if ($validFromDate !== null) {
            $validFrom = CarbonImmutable::parse($validFromDate);
        } elseif ($validFromText !== null) {
            $validFrom = CarbonImmutable::parseFromLocale($validFromText, $locale);
        } else {
            $validFrom = CarbonImmutable::now();
        }

        if ($validUntilDate !== null) {
            $validUntil = CarbonImmutable::parse($validUntilDate);
        } elseif ($validUntilText !== null) {
            $interval = CarbonInterval::parseFromLocale($validUntilText, $locale);
            $validUntil = $validFrom->add($interval);
        } else {
            $validUntil = null;
        }

        $this->merge([
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
        ]);
    }

    public function authorize(): bool
    {
        $user = $this->userModel();
        $userGroup = $this->userGroupOrNull();

        return $user !== null && $userGroup !== null && $user->can('import', $userGroup);
    }

    public function userGroup(): UserGroup
    {
        return $this->findModelOrFail(UserGroup::class);
    }

    public function userGroupOrNull(): ?UserGroup
    {
        return $this->findModel(UserGroup::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function importData(): array
    {
        return $this->normalizeStringKeyedArray($this->safe()
            ->merge($this->only(['valid_from', 'valid_until']))
            ->all());
    }
}
