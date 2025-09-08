<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;


class SecurityController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    // Constructeur pour injecter EntityManagerInterface
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // La méthode peut être vide, elle sera interceptée par le firewall de Symfony
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    // Route pour la page "Mot de passe oublié"
    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password')]
    public function forgotPassword(Request $request, UserRepository $userRepository, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            // Vérification du token CSRF
            $csrfToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('forgot_password', $csrfToken)) {
                throw $this->createAccessDeniedException('Token CSRF invalide');
            }

            $email = $request->request->get('email');

            // Vérification si l'utilisateur existe avec l'email fourni
            $user = $userRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32)); // Vous pouvez stocker ce token en BDD pour le valider plus tard
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt(new \DateTime('+1 hour')); // Expiration du token (1 heure)

                // Sauvegarder le token dans la base de données
                $this->entityManager->flush();

                // Envoi de l'email avec le lien de réinitialisation du mot de passe
                $email = (new Email())
                    ->from('support@leila-snacks.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html(
                        $this->renderView(
                            'emails/forgot_password.html.twig', // Créer un fichier de template pour l'email
                            ['token' => $token]
                        )
                    );

                $mailer->send($email);

                $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
            } else {
                $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    // #[Route('/reset-password/{token}', name: 'app_reset_password')]
    //     public function resetPassword(
    //         Request $request, 
    //         string $token, 
    //         UserRepository $userRepository, 
    //         UserPasswordHasherInterface $passwordHasher
    //     ): Response {
    //         // Check if token is valid
    //         $user = $userRepository->findOneBy(['resetToken' => $token]);

    //         if (!$user || $user->getResetTokenExpiresAt() < new \DateTime()) {
    //             throw $this->createNotFoundException('Token invalide ou expiré.');
    //         }

    //         // Handle the reset password form
    //         $form = $this->createForm(ResetPasswordType::class);
    //         $form->handleRequest($request);

    //         // Vérification du token CSRF avant de traiter la soumission du formulaire
    //         if ($form->isSubmitted() && $form->isValid()) {
    //             // Vérification du token CSRF
    //             $csrfToken = $request->request->get('_token');
    //             if (!$this->isCsrfTokenValid('reset_password', $csrfToken)) {
    //                 throw $this->createAccessDeniedException('Token CSRF invalide');
    //             }

    //             $newPassword = $form->get('password')->getData();

    //             // Hash du nouveau mot de passe
    //             $user->setPassword($passwordHasher->hashPassword($user, $newPassword));

    //             // Suppression du token de réinitialisation après l'utilisation
    //             $user->setResetToken(null);
    //             $user->setResetTokenExpiresAt(null);

    //             // Sauvegarde des changements dans la base de données
    //             $this->entityManager->flush();

    //             $this->addFlash('success', 'Votre mot de passe a été réinitialisé.');
    //             return $this->redirectToRoute('app_login');
    //         }

    //         return $this->render('security/reset_password.html.twig', [
    //             'form' => $form->createView(),
    //         ]);
    //     }
}