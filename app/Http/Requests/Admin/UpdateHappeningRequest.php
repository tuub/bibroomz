<?php

namespace App\Http\Requests\Admin;

class UpdateHappeningRequest extends HappeningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
