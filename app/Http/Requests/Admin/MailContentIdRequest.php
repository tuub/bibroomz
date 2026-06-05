<?php

namespace App\Http\Requests\Admin;

use App\Models\MailContent;

class MailContentIdRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'uuid', 'exists:mail_contents,id'],
        ];
    }

    public function mailContent(): MailContent
    {
        return $this->findModelOrFail(MailContent::class);
    }
}
