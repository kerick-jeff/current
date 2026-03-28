<?php

use App\Models\User;
use Illuminate\Support\Facades\Log;

beforeEach(function () { // Runs before each group of related tests in the file
    test()->user = User::factory()->create();
});

describe('create', function () {
    beforeEach(function () { // Runs before each test in the group
        Log::info('Something that runs before the tests');
    });

    it('may create a user', function () {
        expect(test()->user)->toBeInstanceOf(User::class);
    });

    afterEach(function () { // Runs after each tests in the group
        Log::info('Something that runs after the tests');
    });
});

afterEach(function () { // Runs after each group of related tests in the file
    test()->user = null;
});
