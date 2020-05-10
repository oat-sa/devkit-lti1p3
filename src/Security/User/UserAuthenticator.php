<?php

namespace App\Security\User;

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

class UserAuthenticator implements UserAuthenticatorInterface
{
    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        return new UserAuthenticationResult(
            true,
            $loginHint !== 'anonymous' ? new UserIdentity($loginHint) : null
        );
    }
}
