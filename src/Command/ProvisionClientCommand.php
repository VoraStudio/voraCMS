<?php

/* ═══════════════════════════════════════════════════════════════════════
   ProvisionClientCommand — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Comanda de consola amb dos modes:

   voracms:client:provision {slug}
      Provisiona els content types base per a un client existent.

   voracms:client:create {name} {slug}
      Crea un client nou i el provisiona amb els content types base.
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\Command;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\ClientProvisioner;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'voracms:client:provision',
    description: 'Provisiona els content types base per a un client existent.',
    aliases: ['voracms:client:create'],
)]
class ProvisionClientCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clientRepository,
        private readonly ClientProvisioner $provisioner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('slug', InputArgument::REQUIRED, 'Slug del client')
            ->addArgument('name', InputArgument::OPTIONAL, 'Nom del client (només per al mode create)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug = $input->getArgument('slug');
        $name = $input->getArgument('name');
        $commandName = $input->getFirstArgument() ?? '';

        $isCreateMode = str_contains($commandName, ':create') || $name !== null;

        if ($isCreateMode && $name === null) {
            $output->writeln('<error>El mode create requereix l\'argument "name".</error>');
            return Command::FAILURE;
        }

        /* ----- Mode: provision — buscar client existent ----- */
        if (!$isCreateMode) {
            return $this->handleProvision($slug, $output);
        }

        /* ----- Mode: create — crear i provisionar ----- */
        return $this->handleCreate($name, $slug, $output);
    }

    /* ═══ Provisionar client existent ═══ */
    private function handleProvision(string $slug, OutputInterface $output): int
    {
        $client = $this->clientRepository->findBySlug($slug);

        if (!$client) {
            $output->writeln(sprintf('<error>Client amb slug "%s" no trobat.</error>', $slug));
            return Command::FAILURE;
        }

        $this->provisioner->provision($client);

        $output->writeln(sprintf(
            '<info>Client "%s" provisionat amb èxit (ID: %d).</info>',
            $client->getName(),
            $client->getId(),
        ));

        return Command::SUCCESS;
    }

    /* ═══ Crear client nou i provisionar ═══ */
    private function handleCreate(string $name, string $slug, OutputInterface $output): int
    {
        $client = new Client();
        $client->setName($name);
        $client->setSlug($slug);
        $client->setActive(true);

        $this->em->persist($client);
        $this->em->flush();

        $this->provisioner->provision($client);

        $output->writeln(sprintf(
            '<info>Client "%s" creat amb èxit (ID: %d). Content types base provisionats.</info>',
            $client->getName(),
            $client->getId(),
        ));

        return Command::SUCCESS;
    }
}
