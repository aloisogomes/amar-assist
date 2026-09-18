<?php

use Dedoc\Scramble\Generator;

it('documents the public and authenticated api endpoints', function () {
    $spec = app(Generator::class)();

    expect($spec['paths'])->toHaveKeys([
        '/register',
        '/login',
        '/user',
        '/logout',
        '/finances',
        '/finances/{finance}',
        '/finances/dashboard',
        '/finances/template',
        '/finances/import',
    ])->not->toHaveKey('/broadcasting/auth');

    expect($spec['components']['securitySchemes']['http'] ?? [])
        ->toMatchArray([
            'type' => 'http',
            'scheme' => 'bearer',
        ]);

    expect($spec['security'])->toContain(['http' => []])
        ->and($spec['paths']['/register']['post']['security'])->toBe([])
        ->and($spec['paths']['/login']['post']['security'])->toBe([]);
});
