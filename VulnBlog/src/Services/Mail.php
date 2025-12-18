<?php

namespace App\Services;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Mail
{
    public function __construct(
        private string $sender,
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {}

public function sendReset(string $emailAddress, string $token): void
{
    // 1. Vérifier si l'utilisateur existe avant toute chose
    $user = $this->userRepository->findOneBy(['email' => $emailAddress]);
    
    if (!$user) {
        // on ne dit pas à l'attaquant que l'email n'existe pas
        return; 
    }

    // 2. Générer l'URL SANS l'email (Le token suffit s'il est unique et lié à l'user en DB)
    $url = $this->router->generate('app_reset_password', [
        'token' => $token
    ], UrlGeneratorInterface::ABSOLUTE_URL);

    // 3. Sécuriser l'affichage (Échapper les variables dans le HTML)
    $email = (new Email())
        ->from($this->sender)
        ->to($user->getEmail())
        ->subject('Réinitialisation de votre mot de passe')
        ->html("<p>Pour réinitialiser votre mot de passe, cliquez sur le lien suivant : <a href='".htmlspecialchars($url)."'>Lien</a></p>");

    $this->mailer->send($email);

    // 4. Stocker le token en base
    $user->setReset($token);
    // Optionnel : ajouter une date d'expiration au token (ex: +1h)
    // $user->setTokenCreatedAt(new \DateTime()); 
    
    $this->entityManager->flush();
}
}