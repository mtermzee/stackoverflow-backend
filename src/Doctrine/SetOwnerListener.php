<?php

namespace App\Doctrine;

use Symfony\Component\Security\Core\Security;

class SetOwnerListener
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    // methods: postPersist, preUpdate, preRemove
    public function prePersist($entity)
    {
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $entity->setOwner($user);
    }
}
