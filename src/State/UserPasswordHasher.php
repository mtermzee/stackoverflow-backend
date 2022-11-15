<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHasher implements ProcessorInterface
{
    private ProcessorInterface $decorated;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(ProcessorInterface $decorated, UserPasswordHasherInterface $passwordHasher)
    {
        $this->decorated = $decorated;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @param User $data
     */
    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data->getPlainPassword()) {
            return $this->decorated->process($data, $operation, $uriVariables, $context);
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $data,
            $data->getPlainPassword()
        );
        $data->setPassword($hashedPassword);
        $data->eraseCredentials();

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
