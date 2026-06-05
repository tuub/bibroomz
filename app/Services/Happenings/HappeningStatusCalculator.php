<?php

namespace App\Services\Happenings;

use App\Models\Happening;
use App\Models\User;

class HappeningStatusCalculator
{
    /**
     * @return array{
     *   type: 'booking'|'reservation'|'user-booking'|'user-reservation'|'user-to-verify',
     *   user: array{}|array{reservation: string, verification: string}
     * }
     */
    public function calculate(Happening $happening, ?User $viewer): array
    {
        $user1 = $happening->user1;
        $reservationName = $user1 instanceof User ? $user1->name : '';
        $verificationUser = $happening->user2;
        $verificationName = is_string($happening->verifier) ? $happening->verifier : '';

        $status = [
            'type' => $happening->isVerified() ? 'booking' : 'reservation',
            'user' => [],
        ];

        if ($viewer) {
            if ($happening->isVerified()) {
                if ($this->isMine($happening, $viewer)) {
                    $status['type'] = 'user-booking';
                    $status['user']['reservation'] = $reservationName;
                    $status['user']['verification'] = $verificationUser instanceof User ? $verificationUser->name : '';
                } else {
                    $status['type'] = 'booking';
                }
            } else {
                if ($this->isMine($happening, $viewer)) {
                    $status['type'] = 'user-reservation';
                    $status['user']['reservation'] = $reservationName;
                    $status['user']['verification'] = $verificationName;
                } elseif ($this->isMyToVerify($happening, $viewer)) {
                    $status['type'] = 'user-to-verify';
                    $status['user']['reservation'] = $reservationName;
                    $status['user']['verification'] = $verificationName;
                } else {
                    $status['type'] = 'reservation';
                }
            }
        }

        return $status;
    }

    private function isMine(Happening $happening, User $viewer): bool
    {
        return $viewer->id === $happening->user_id_01 || $viewer->id === $happening->user_id_02;
    }

    private function isMyToVerify(Happening $happening, User $viewer): bool
    {
        return $viewer->name === $happening->verifier;
    }
}
