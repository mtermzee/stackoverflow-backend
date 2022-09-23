<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class UserController extends UserHelberController
{
    #[Route('/me', name: 'user')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function apiMe()
    {
        return $this->json($this->getUser(), 200, [], ['groups' => 'user:read']);
    }
}
