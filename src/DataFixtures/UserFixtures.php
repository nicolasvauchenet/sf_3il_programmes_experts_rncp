<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->createUser(
            $manager,
            'vauche@3il.fr',
            'test',
            'Nicolas Vauché',
            ['ROLE_ADMIN'],
        );

        $this->createUser(
            $manager,
            'user@3il.fr',
            'test',
            'Utilisateur test',
            ['ROLE_USER'],
        );

        $manager->flush();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(ObjectManager $manager, string $email, string $password, string $fullName, array $roles): void
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => strtolower($email)]);

        if (!$user instanceof User) {
            $user = new User();
            $manager->persist($user);
        }

        $user
            ->setEmail($email)
            ->setFullName($fullName)
            ->setRoles($roles)
            ->setIsActive(true)
            ->setIsAccepted(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
    }
}
