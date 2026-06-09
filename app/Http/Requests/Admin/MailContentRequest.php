<?php

namespace App\Http\Requests\Admin;

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\User;

class MailContentRequest extends AdminRouteRequest
{
    public function authorize(): bool
    {
        $user = $this->userModel();
        $mail = $this->mailContentOrNull();

        if (! $user instanceof User) {
            return false;
        }

        if ($mail instanceof MailContent) {
            return $user->can('edit', $mail);
        }

        $institution = $this->institutionOrNull();

        return $institution instanceof Institution && $user->can('create', [MailContent::class, $institution]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid', 'exists:mail_contents,id'],
            'institution_id' => ['required', 'uuid', 'exists:institutions,id'],
            'mail_type_id' => ['required', 'exists:mail_types,id'],
            'subject' => ['required'],
            'title' => [],
            'salutation' => [],
            'intro' => [],
            'outro' => [],
            'action_uri' => [],
            'action_uri_label' => ['required_with:action_uri'],
            'farewell' => [],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function institution(): Institution
    {
        return $this->findModelOrFail(Institution::class, 'institution_id');
    }

    public function institutionOrNull(): ?Institution
    {
        return $this->findModel(Institution::class, 'institution_id');
    }

    public function mailContent(): MailContent
    {
        return $this->findModelOrFail(MailContent::class);
    }

    public function mailContentOrNull(): ?MailContent
    {
        return $this->findModel(MailContent::class);
    }
}
