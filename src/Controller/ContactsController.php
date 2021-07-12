<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\Contacts;
use App\Services\Csv;
use Symfony\Component\HttpFoundation\Session\Session;


class ContactsController extends AbstractController
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/contacts", name="contacts")
     */
    public function contacts(Request $request, Security $security, Session $session)
    {
            $error = '';
            $userOb = $security->getUser();
            if(!$userOb) {
                return new RedirectResponse('/');
            } else {

                $recordsCount = count($this->entityManager->getRepository(Contacts::class)->findByUser(['user_id' => $userOb->getUserId()], $orderBy = null));

                $rowsCount = $request->get('rowsCount');
                $page = $request->get('page');

                if(!$rowsCount && !$session->get('rowsCount')) $rowsCount = 5;
                if(!$page && !$session->get('page')) $page = 1;

                /* session */
                if($page) {
                    $session->set('page', $page);
                } else {
                    $page = $session->get('page');
                }

                if($rowsCount) {
                    $session->set('rowsCount', $rowsCount);
                } else {
                    $rowsCount = $session->get('rowsCount');
                }
                
                /* Pagination */
                $paginationArr = array();
                $from = 1;
                $to = 1;
                
                $pagesCount = (int)($recordsCount/$rowsCount)+1;
                
                if($recordsCount > 0 && $recordsCount % $rowsCount == 0) $pagesCount = $pagesCount - 1;

                if ($page < 10) $from = 1;
                else $from = $page - 10;
        
                if ($page > $pagesCount - 10) $to = $pagesCount;
                else $to = $page + 10;
                
                for ($i=$from;$i<=$to;$i++){
                    $paginationArr[$i]=$i;
                }
                

                $tasksArrOb = $this->entityManager->getRepository(Contacts::class)->findByUser(['user_id' => $userOb->getUserId()], $orderBy = null, $limit = $rowsCount, $offset = $rowsCount*($page-1));

                return $this->render('contacts/contacts.html.twig', ['pagesCount'=>$pagesCount, 'rowsCount'=>$rowsCount, 'page'=>$page, 'paginationArr'=>$paginationArr, 'tasksArrOb' => $tasksArrOb, 'error' => $error, 'username' => $userOb->getUserName()]); 
        
            }
            
    }

    /**
     * @Route("/contact-create", name="contact_create")
     */
    public function contactCreate(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return new RedirectResponse('/');
        } else {
            $error = '';
            return $this->render('contacts/contact-create.html.twig', ['error' => $error, 'userName' => $userOb->getUserName()]); 
        }
    }

    /**
     * @Route("/contact-create-update-api", name="contact_create_update_api")
     */
    public function contactCreateUpdateApi(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if($userOb) {
            
            $method = $request->getMethod(); 

            if($method == 'POST' || $method == 'PUT') {
                
                

                $id = $request->get('id');
                $title = $request->get('title');
                $comment = $request->get('comment');
                $phone = $request->get('phone');
                $fullName = $request->get('full_name');
                $address = $request->get('address');

                if($method == 'POST') {
                    $contactOb = new Contacts();
                }

                if($method == 'PUT') {
                    if($id){
                        $contactOb = $this->entityManager->getRepository(Contacts::class)->findOneBy(['id'=>$id]);
                    } else {
                        $responceArray = array('success' => false, 'Record ID not found');
                        $response = new Response(json_encode($responceArray), 404);  
                        return $response;
                    }
                }


                $contactOb->setTitle($title);
                $contactOb->setComment($comment);
                $contactOb->setPhone($phone);
                $contactOb->setAddress($address);
                
                $dateOb = new \DateTime();
                $contactOb->setDate($dateOb);
                $contactOb->setFullname($fullName);
                $contactOb->setUser($userOb);
                
                $this->entityManager->persist($contactOb);
                $this->entityManager->flush();

                $responceArray = array('success' => true);
                $response = new Response(json_encode($responceArray), 200);    
              
            } else {
                $responceArray = array('success' => false, 'error' => 'Support only POST request');
                $response = new Response(json_encode($responceArray), 404);            
            }
            
        } else {
            $responceArray = array('success' => false);
            $response = new Response(json_encode($responceArray), 401);
        }
        $response->headers->set('Content-Type', 'application/json');    
        return $response;
    }

    /**
     * @Route("/contact-delete-api", name="contact_create_api")
     */
    public function contactDeleteApi(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if($userOb) {
            
            $method = $request->getMethod(); 

            if($method == 'GET') {

                $id = $request->get('id');
                
                $comment = $request->get('comment');
                $phone = $request->get('phone');

                if($id){
                    $contactOb = $this->entityManager->getRepository(Contacts::class)->findOneBy(['id'=>$id]);
                    $this->entityManager->remove($contactOb);
                    $this->entityManager->flush();
                    $responceArray = array('success' => true);
                    $response = new Response(json_encode($responceArray), 200);    
                } else {
                    $responceArray = array('success' => false, 'record not found');
                    $response = new Response(json_encode($responceArray), 404);    
                }

              
            } else {
                $responceArray = array('success' => false, 'error' => 'Support only DELETE request');
                $response = new Response(json_encode($responceArray), 404);            
            }
            
        } else {
            $responceArray = array('success' => false);
            $response = new Response(json_encode($responceArray), 401);
        }
        $response->headers->set('Content-Type', 'application/json');    
        return $response;
    }

    /**
     * @Route("/contacts-export", name="contacts_export")
     */
    public function contactsExport(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return new RedirectResponse('/');
        } else {

            return $this->render('contacts/contacts-export.html.twig', []); 
        }
    }

}
