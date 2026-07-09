<?php

namespace App\Http\Requests;

use App\Library\Utility;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class AddHappeningRequest extends FormRequest
{
    private ?Resource $resourceModel = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Happening::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $resource = $this->resource();
        $user = $this->user();
        $isVerificationRequired = $resource->isVerificationRequired()
            && $user instanceof User
            && ! $user->hasPermission('no_verifier', $resource->resource_group->institution);
        $normalizedUserName = $user instanceof User
            ? Utility::normalizeLoginName($user->name)
            : null;

        return [
            'resource' => ['required', 'array'],
            'resource.id' => ['required', 'uuid', 'exists:resources,id'],
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'verifier' => [
                $isVerificationRequired ? 'required' : 'nullable',
                'string',
                'not_in:'.$normalizedUserName,
            ],
            'label' => ['nullable'],
            'user_id_01' => [$user instanceof User && $user->isAdmin() ? 'nullable' : 'prohibited', 'uuid', 'exists:users,id'],
        ];
    }

    public function resource(): Resource
    {
        if ($this->resourceModel instanceof Resource) {
            return $this->resourceModel;
        }

        $resourceId = $this->input('resource.id');

        return $this->resourceModel = Resource::query()
            ->with('resource_group.institution')
            ->findOrFail(is_string($resourceId) ? $resourceId : null);
    }

    public function startAt(): CarbonImmutable
    {
        $start = $this->input('start');

        return CarbonImmutable::parse(is_string($start) ? $start : null);
    }

    public function endAt(): CarbonImmutable
    {
        $end = $this->input('end');

        return CarbonImmutable::parse(is_string($end) ? $end : null);
    }

    public function label(): mixed
    {
        return $this->input('label');
    }

    public function verifier(): ?string
    {
        $verifier = $this->input('verifier');

        return is_string($verifier) && $verifier !== '' ? $verifier : null;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $verifier = $this->input('verifier');

        $this->merge([
            'verifier' => is_string($verifier) ? Utility::normalizeLoginName($verifier) : null,
        ]);
    }
}
