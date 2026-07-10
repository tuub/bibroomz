<?php

namespace App\Services\Admin;

use App\Models\Institution;
use App\Models\MailContent;
use App\Models\User;
use App\Services\AdminLoggingService;

class MailAdminService
{
    public function __construct(
        private readonly AdminLoggingService $adminLoggingService,
        private readonly MissingMailTypesQuery $missingMailTypesQuery,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getIndexData(Institution $institution): array
    {
        $user = auth()->user();
        $mails = MailContent::query()
            ->with(['mail_type', 'institution'])
            ->where('institution_id', $institution->id)
            ->get();

        if ($user instanceof User) {
            $mails = $mails
                ->filter(fn (MailContent $mail): bool => $mail->isViewableByUser($user))
                ->values();
        }

        return [
            'institution' => $institution,
            'mails' => $mails,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCreateFormData(Institution $institution): array
    {
        return [
            'institution' => $institution,
            'institution_id' => $institution->id,
            'mail_types' => $this->missingMailTypesQuery->execute($institution->id),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEditFormData(MailContent $mail): array
    {
        return [
            'mail' => $mail->loadMissing('mail_type'),
            'institution' => $mail->institution,
            'institution_id' => $mail->institution_id,
            'mail_types' => $this->missingMailTypesQuery->execute($mail->institution_id),
            'languages' => config('app.supported_locales'),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): MailContent
    {
        $mail = MailContent::create($attributes);

        $this->adminLoggingService->log('created', $mail);

        return $mail;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(MailContent $mail, array $attributes): MailContent
    {
        $mail->update($attributes);

        $this->adminLoggingService->log('updated', $mail);

        return $mail;
    }

    public function delete(MailContent $mail): void
    {
        $mail->delete();

        $this->adminLoggingService->log('deleted', $mail);
    }
}
