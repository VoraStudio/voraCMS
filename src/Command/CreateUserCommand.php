<?php

namespace App\Command;

use App\Entity\User;
use App\Service\SlugGenerator;
use App\Service\TokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'voracms:user:create',
    description: 'Crea un usuari (tenant) nou.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly SlugGenerator $slugGenerator,
        private readonly TokenGenerator $tokenGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Email de l\'usuari', 'admin@vora.es')
            ->addArgument('password', InputArgument::OPTIONAL, 'Password', '123')
            ->addArgument('role', InputArgument::OPTIONAL, 'Rol (ROLE_ADMIN, ROLE_MOD, ROLE_USUARIO)', 'ROLE_ADMIN')
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom de l\'usuari', null)
            ->addArgument('company', InputArgument::OPTIONAL, 'Empresa', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');
        $role = $input->getArgument('role');
        $name = $input->getArgument('name') ?? explode('@', $email)[0];
        $company = $input->getArgument('company') ?? $name;

        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $output->writeln(sprintf('<error>L\'usuari "%s" ja existeix.</error>', $email));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setCompany($company);
        $user->setSlug($this->slugGenerator->generate($company));
        $user->setApiToken($this->tokenGenerator->generate(32));
        $user->setRoles([$role]);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setActive(true);
        $user->setLocale('ca');

        $this->em->persist($user);
        $this->em->flush();

        $output->writeln(sprintf('<info>Usuari creat: %s / %s (%s)</info>', $email, $password, $role));

        return Command::SUCCESS;
    }
}
