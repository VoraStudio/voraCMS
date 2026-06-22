<?php
require_once __DIR__ . '/../vendor/autoload.php';

$kernel = new \App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine.orm.entity_manager');
$hasher = $container->get('Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface');

$client = $em->getRepository(\App\Entity\Client::class)->findOneBy(['slug' => 'victoria-taylor']);
if (!$client) {
    echo "ERROR: Client victoria-taylor not found\n";
    exit(1);
}

$user = new \App\Entity\User();
$user->setEmail('victoria@victoriataylor.com');
$user->setName('Victoria Admin');
$user->setRoles(['ROLE_ADMIN']);
$user->setPassword($hasher->hashPassword($user, 'victoria123'));
$user->setClient($client);
$user->setActive(true);
$user->setLocale('ca');

$em->persist($user);
$em->flush();

echo "OK: victoria@victoriataylor.com / victoria123\n";
