<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

class AuthenticationController extends AbstractController
{
    #[Route('/login', name: 'login_page', )]
    public function loginPage(AuthenticationUtils $authenticationUtils) {
        return $this->render('pages/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'lastUsername' => $authenticationUtils->getLastUsername(),
        ]);
    }

    // Security listener intercepts this request and handles logout.
    // Although this route may seem unnecessary, without it the routing system 
    // will trigger a 404 error before the listener can intercept the request.
    #[Route('/logout', name: 'logout', )]
    public function logout() {
        throw new \Exception('Logout function should never be reached');
    }

    #[Route('/register', name: 'register_page')]
    public function register(
        EntityManagerInterface $entityManager,
        FormLoginAuthenticator $formLoginAuthenticator,
        Request $request,
        UserAuthenticatorInterface $userAuthenticator,
        UserPasswordHasherInterface $userPasswordHasher,
    ) {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            
            // Authenticate user and redirect to homepage
            return $userAuthenticator->authenticateUser(
                $user,
                $formLoginAuthenticator,
                $request,
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
