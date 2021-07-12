<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

class SecurityController extends AbstractController
{

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/register", name="app_register")
     */
    public function register(
        Request $request,
        Security $security,
        UserPasswordEncoderInterface $passwordEncoder,
        EntityManagerInterface $entityManager,
        LoginFormAuthenticator $loginAuthenticator,
        GuardAuthenticatorHandler $guard
    )
    {
            $email = $request->get('email');
            $error = array();
            $password = $request->get('password');

            if($email && $password) {

                $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $email]);
                if($user) {
                    $error = 'User exists';
                } else {

                    $newUser = new User();
                    $newUser->setUsername($email);
                    $encodedPassword = $passwordEncoder->encodePassword($newUser, $password);
                    $newUser->setPassword($encodedPassword);
                    $newUser->setRoles(['ROLE_USER']);

                    $entityManager->persist($newUser);

                    // actually executes the queries (i.e. the INSERT query)
                    $entityManager->flush();
                    //return new RedirectResponse('/');
                    return $guard->authenticateUserAndHandleSuccess(
                        $newUser,
                        $request,
                        $loginAuthenticator,
                        'main'
                    );

                }


            }
            return $this->render('security/register.html.twig', [ 'error' => $error]);
    }

    /**
     * @Route("/login-register", name="login_register")
     */
    public function loginRegister(Security $security)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return $this->render('security/login-register.html.twig', [ 'error' => $error]);
        } else {
            return new RedirectResponse('/');
        }
    }

}
