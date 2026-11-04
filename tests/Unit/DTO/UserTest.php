<?php

use InterWorks\PowerBI\DTO\User;

test('maps every field from an API item', function () {
    $user = User::fromItem([
        'emailAddress' => 'pat@example.com',
        'displayName' => 'Pat',
        'identifier' => 'user-id',
        'userPrincipalName' => 'pat@example.onmicrosoft.com',
    ]);

    expect($user->emailAddress)->toBe('pat@example.com')
        ->and($user->displayName)->toBe('Pat')
        ->and($user->identifier)->toBe('user-id')
        ->and($user->userPrincipalName)->toBe('pat@example.onmicrosoft.com');
});

test('every field is null when the API omits it', function () {
    $user = User::fromItem([]);

    expect($user->emailAddress)->toBeNull()
        ->and($user->displayName)->toBeNull()
        ->and($user->identifier)->toBeNull()
        ->and($user->userPrincipalName)->toBeNull();
});
