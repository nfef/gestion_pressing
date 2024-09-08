<?php


namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserChecker as BaseUserChecker;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class UserChecker extends BaseUserChecker
{
    public function checkPreAuth(UserInterface $user)
    {
        parent::checkPreAuth($user);

        // Ajoute tes vérifications personnalisées ici, par exemple :
        // if (!$user->isEnabled()) {
        //     throw new CustomUserMessageAuthenticationException('Your account is disabled.');
        // }
    }

    public function checkPostAuth(UserInterface $user)
    {
        parent::checkPostAuth($user);

        // Ajoute tes vérifications personnalisées ici, si nécessaire
    }
}
