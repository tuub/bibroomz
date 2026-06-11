<?php

declare(strict_types=1);

use App\Events\HappeningBroadcastEvent;
use App\Events\HappeningCreatedEvent;
use App\Events\HappeningDeletedEvent;
use App\Events\HappeningUpdatedEvent;
use App\Events\HappeningVerifiedEvent;
use App\Models\Happening;
use App\Models\Resource;
use App\Models\User;
use App\Services\Happenings\HappeningNotificationTypeResolver;

covers(HappeningNotificationTypeResolver::class);

function makeHappeningWithResource(bool $isVerificationRequired = false): Happening
{
    $resource = new Resource;
    $resource->is_verification_required = $isVerificationRequired;
    $happening = new Happening;
    $happening->setRelation('resource', $resource);

    return $happening;
}

test('resolves happening_created when HappeningCreatedEvent and no verification required', function (): void {
    $happening = makeHappeningWithResource(false);
    $user = new User;

    $event = new HappeningCreatedEvent($happening, $user);
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_created');
});

test('resolves happening_created_with_verification when HappeningCreatedEvent and verification required', function (): void {
    $happening = makeHappeningWithResource(true);
    $user = new User;

    $event = new HappeningCreatedEvent($happening, $user);
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_created_with_verification');
});

test('resolves happening_verified for HappeningVerifiedEvent', function (): void {
    $happening = makeHappeningWithResource();
    $user = new User;

    $event = new HappeningVerifiedEvent($happening, $user);
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_verified');
});

test('resolves happening_updated for HappeningUpdatedEvent', function (): void {
    $happening = makeHappeningWithResource();
    $user = new User;

    $event = new HappeningUpdatedEvent($happening, $user);
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_updated');
});

test('resolves happening_deleted for HappeningDeletedEvent', function (): void {
    $happening = makeHappeningWithResource();
    $user = new User;

    $event = new HappeningDeletedEvent($happening, $user);
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_deleted');
});

test('HappeningDeletedEvent with verification required still resolves to happening_deleted not happening_created_with_verification', function (): void {
    $happening = makeHappeningWithResource(true);
    $user = new User;

    $event = new HappeningDeletedEvent($happening, $user);
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_deleted');
});

test('unknown HappeningBroadcastEvent subtype resolves to happening_updated default', function (): void {
    $happening = makeHappeningWithResource();
    $user = new User;

    $event = new class($happening, $user) extends HappeningBroadcastEvent {};
    $resolver = new HappeningNotificationTypeResolver;

    expect($resolver->resolve($event))->toBe('happening_updated');
});
