<?php

namespace App\Security\User;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResult;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
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

    public function authenticate(RegistrationInterface $registration, string $loginHint): UserAuthenticationResultInterface
    {
        $hint = json_decode($loginHint, true);

        if ($hint['type'] === 'list') {

            $userData = $this->parameterBag->get('users')[$hint['user_id']] ?? [];

            return new UserAuthenticationResult(
                true,
                $this->factory->create(
                    $hint['user_id'],
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

        if ($hint['type'] === 'custom') {
            return new UserAuthenticationResult(
                true,
                $this->factory->create(
                    $hint['user_id'],
                    $hint['user_name'] ?? null,
                    $hint['user_email'] ?? null,
                    null,
                    null,
                    null,
                    $hint['user_locale'] ?? null
                )
            );
        }

        return new UserAuthenticationResult(true);
    }
}
