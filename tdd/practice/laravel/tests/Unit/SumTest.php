<?php

use App\Helpers\General;

describe('sum', function () {
    it('may sum integers', function () {
        expect(General::sum(1, 2))->toBe(3);
    });

    it('may sum floats', function () {
        expect(General::sum(1.7, 1.3))->toBe(3.0);
    });
});
