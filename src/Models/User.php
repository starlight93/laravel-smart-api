<?php

namespace Starlight93\LaravelSmartApi\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\User as DefaultUser;

class User extends DefaultUser implements JWTSubject
{

    public function getTable()
    {
        return config('api.user_table');
    }

    protected $hidden = [
        'password',
    ];
    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

}