<?php

namespace App\Http\Requests\Admin;

use App\Models\MailContent;

class DeleteMailContentRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $mailContent = $this->findModel(MailContent::class);
        $user = $this->userModel();

        return $mailContent !== null && $user !== null && $user->can('delete', $mailContent);
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
