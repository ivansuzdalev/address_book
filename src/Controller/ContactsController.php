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
use App\Entity\ContactsSharing;
use App\Services\ContactsService;

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

            $id = $request->get('id');
            $contactOb = new Contacts();
            return $this->render('contacts/contact-create-update.html.twig', ['contactOb' => $contactOb, 'error' => $error, 'userName' => $userOb->getUserName()]); 
        }
    }

    /**
     * @Route("/contact-update", name="contact_update")
     */
    public function contactUpdate(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return new RedirectResponse('/');
        } else {

            $error = '';
            $id = $request->get('id');
            
            $contactOb = $this->entityManager->getRepository(Contacts::class)->findOneBy(['id'=>$id]);
    
            return $this->render('contacts/contact-create-update.html.twig', ['contactOb' => $contactOb, 'error' => $error, 'userName' => $userOb->getUserName()]); 
        }
    }

    /**
     * @Route("/contact-share", name="contact_share")
     */
    public function contactShare(Request $request, Security $security, Session $session, ContactsService $contactsServiceOb)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return new RedirectResponse('/');
        } else {

            $error = '';
            $contactId = $request->get('contact_id');
            $contactOb = $this->entityManager->getRepository(Contacts::class)->findOneBy(['id'=>$contactId]);
            $contactSharedWithArr = $contactsServiceOb->getContactSharedWith($contactId);

            return $this->render('contacts/contact-share.html.twig', ['contactOb'=>$contactOb, 'contactSharedWithArr' => $contactSharedWithArr, 'error' => $error, 'userName' => $userOb->getUserName()]); 
        }
    }

    /**
     * @Route("/contact-create-update-api", name="contact_create_update_api")
     */
    public function contactCreateUpdateApi(Request $request, Security $security, Session $session, ContactsService $contactsServiceOb)
    {
        $error = '';
        $userOb = $security->getUser();
        if($userOb) {
            
            $method = $request->getMethod(); 
            if($method == 'POST') {
                
                $contactsServiceOb->createContact($request, $userOb);

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

                $contactId = $request->get('contactId');
                
                $comment = $request->get('comment');
                $phone = $request->get('phone');

                if($contactId){
                    $contactOb = $this->entityManager->getRepository(Contacts::class)->findOneBy(['id'=>$contactId]);
                    $contactsSharingOb = $this->entityManager->getRepository(ContactsSharing::class)->findOneBy(['userId'=>$userOb->getId(), 'contactId'=>$contactId]);
                    $this->entityManager->remove($contactOb);
                    $this->entityManager->remove($contactsSharingOb);
                    $this->entityManager->flush();
                    $responceArray = array('success' => true);
                    $response = new Response(json_encode($responceArray), 200);    
                } else {
                    $responceArray = array('success' => false, 'record not found');
                    $response = new Response(json_encode($responceArray), 404);    
                }

              
            } else {
                $responceArray = array('success' => false, 'error' => 'Support only GET request');
                $response = new Response(json_encode($responcentityManagerArray), 501);            
            }
            
        } else {
            $responceArray = array('success' => false);
            $response = new Response(json_encode($responceArray), 401);
        }
        $response->headers->set('Content-Type', 'application/json');    
        return $response;
    }

    /**
     * @Route("/contact-share-api", name="contact_share_api")
     */
    public function contactShareApi(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return new RedirectResponse('/');
        } else {

            $method = $request->getMethod(); 

            if($method == 'GET') {

                $contactId = $request->get('contact_id');
                
                $userId = $request->get('user_id');

                if($contactId && $userId){
                    
                    $contactsSharingOb = $this->entityManager->getRepository(ContactsSharing::class)->findOneBy(['userId'=>$userId, 'contactId'=>$contactId]);
                    
                    if(!$contactsSharingOb) {
                
                        $contactsSharingOb = new ContactsSharing();
                        $contactsSharingOb->setUserId($userId);
                        $contactsSharingOb->setContactId($contactId);
                    
                        $this->entityManager->persist($contactsSharingOb);
                        $this->entityManager->flush();
                    
                        $responceArray = array('success' => true);
                        $response = new Response(json_encode($responceArray), 200);    

                    } else {
                        $responceArray = array('success' => false, 'error' => 'Record exists');
                        $response = new Response(json_encode($responceArray), 501);    
                    }
                    
                    
                } else {
                    $responceArray = array('success' => false, 'record not found');
                    $response = new Response(json_encode($responceArray), 404);    
                }

              
            } else {
                $responceArray = array('success' => false, 'error' => 'Support only GET request');
                $response = new Response(json_encode($responceArray), 501);            
            }
        }


        return $response;
    }

    /**
     * @Route("/contact-unshare-api", name="contact_unshare_api")
     */
    public function contactUnShareApi(Request $request, Security $security, Session $session)
    {
        $error = '';
        $userOb = $security->getUser();
        if(!$userOb) {
            return new RedirectResponse('/');
        } else {

            $method = $request->getMethod(); 

            if($method == 'GET') {

                $contactId = $request->get('contact_id');
                
                $userId = $request->get('user_id');

                if($contactId && $userId){
                    
                    $contactsSharingOb = $this->entityManager->getRepository(ContactsSharing::class)->findOneBy(['userId'=>$userId, 'contactId'=>$contactId]);
                    
                    if($contactsSharingOb) {
                
                       $this->entityManager->remove($contactsSharingOb);
                        $this->entityManager->flush();
                    
                        $responceArray = array('success' => true);
                        $response = new Response(json_encode($responceArray), 200);    

                    } else {
                        $responceArray = array('success' => false, 'error' => 'Record not found');
                        $response = new Response(json_encode($responceArray), 404);    
                    }
                    
                    
                } else {
                    $responceArray = array('success' => false, 'record not found');
                    $response = new Response(json_encode($responceArray), 404);    
                }

              
            } else {
                $responceArray = array('success' => false, 'error' => 'Support only GET request');
                $response = new Response(json_encode($responceArray), 501);            
            }
        }


        return $response;
    }

    /**
     * @Route("/contacts-export", name="contacts_export")
     */
    public function contactsExportApi(Request $request, Security $security, Session $session)
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
