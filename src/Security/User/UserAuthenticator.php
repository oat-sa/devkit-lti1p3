<?php

namespace App\Security\User;

use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityFactoryInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserAuthenticator implements UserAuthenticatorInterface
{
    /** UserIdentityFactoryInterface */
    private $factory;

    /** @var ParameterBagInterface */
    private $parameterBag;

    public function __construct(UserIdentityFactoryInterface $factory, ParameterBagInterface $parameterBag)
    {
        $this->factory = $factory;
        $this->parameterBag = $parameterBag;
    }

    public function authenticate(string $loginHint): UserAuthenticationResultInterface
    {
        if ($loginHint === 'anonymous') {
            return new UserAuthenticationResult(true);
        }

        $userData = $this->parameterBag->get('users')[$loginHint] ?? [];

        return new UserAuthenticationResult(
            true,
            $this->factory->create(
                $loginHint,
                $userData['name'] ?? null,
                $userData['email'] ?? null,
                $userData['givenName'] ?? null,
                $userData['familyName'] ?? null,
                $userData['middleName'] ?? null,
                $userData['locale'] ?? null,
                $userData['picture'] ?? null
            )
        );
    }
}
