<?php

namespace App\Http\Requests;

use App\Models\Happening;
use Illuminate\Foundation\Http\FormRequest;

class VerifyHappeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var Happening */
        $happening = Happening::findOrFail($this->id);

        return $this->user()->can('verify', $happening);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'id' => ['required', 'uuid'],
            'start' => ['required'],
            'end' => ['required'],
        ];
    }
}
