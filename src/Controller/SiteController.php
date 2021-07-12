<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;


class SiteController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(Request $request, Security $security)
    {
            $userOb = $security->getUser();
            if(!$userOb) {
                return new RedirectResponse('login-register');
            } else {
                return new RedirectResponse('contacts');
            }

    }

}
