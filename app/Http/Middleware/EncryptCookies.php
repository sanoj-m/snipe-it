<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        // [killa-v2 design-toggle] BEGIN — KCP-054
        // Plain JS-set cookie read server-side by layouts/default.blade.php
        // to gate the v2 overlay assets and sidebar markup. Must stay
        // unencrypted — Laravel would otherwise decrypt-fail it to null.
        'killa_design',
        // [killa-v2 design-toggle] END
    ];

    /**
     * Indicates if cookies should be serialized.
     *
     * @var bool
     */
    protected static $serialize = false;
}
