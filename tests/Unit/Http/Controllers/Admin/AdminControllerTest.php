<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminController;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(AdminController::class);

test('authenticatedUser aborts with 403 when no user is authenticated', function (): void {
    $controller = new class extends AdminController
    {
        public function callAuthenticatedUser(): User
        {
            return $this->authenticatedUser();
        }
    };

    try {
        $controller->callAuthenticatedUser();
        test()->fail('Expected HttpException');
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403);
    }
});
