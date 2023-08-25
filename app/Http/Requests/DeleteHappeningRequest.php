<?php

namespace App\Http\Requests;

use App\Models\Happening;
use Illuminate\Foundation\Http\FormRequest;

class DeleteHappeningRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var User */
        $user = auth()->user();

        /** @var Happening */
        $happening = Happening::findOrFail($this->id);

        return $user->can('delete', $happening);
    }
}
