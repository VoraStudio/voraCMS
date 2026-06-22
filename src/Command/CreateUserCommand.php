<?php

namespace App\Command;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'voracms:user:create',
    description: 'Crea un usuari per un client existent.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('slug', InputArgument::OPTIONAL, 'Slug del client', 'victoria-taylor')
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'usuari', 'victoria@victoriataylor.com')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password', 'victoria123')
            ->addArgument('role', InputArgument::OPTIONAL, 'Rol (ROLE_ADMIN, ROLE_MOD, ROLE_USUARIO)', 'ROLE_ADMIN');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug = $input->getArgument('slug');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');

        $client = $this->em->getRepository(Client::class)->findOneBy(['slug' => $slug]);
        if (!$client) {
            $output->writeln(sprintf('<error>Client "%s" no trobat.</error>', $slug));
            return Command::FAILURE;
        }

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $output->writeln(sprintf('<error>L\'usuari "%s" ja existeix.</error>', $email));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName(explode('@', $email)[0]);
        $user->setRoles([$role]);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setClient($client);
        $user->setActive(true);
        $user->setLocale('ca');

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('<info>Usuari creat: %s / %s (%s)</info>', $email, $password, $role));

        return Command::SUCCESS;
    }
}
