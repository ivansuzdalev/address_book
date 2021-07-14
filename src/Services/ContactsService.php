<?php
namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ContactsSharing;
use App\Entity\User;
use App\Entity\Contacts;
use Symfony\Component\HttpFoundation\Request;


class ContactsService
{
    private $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function isContactShared(int $userId, int $contactId): ?bool
    {
        $record = $this->entityManager->getRepository(ContactsSharing::class)->findOneBy(['userId' => $userId, 'contactId' => $contactId]);
        if($record)
        {
            return true;
        } 
        else 
        {
            return false;
        }

    }

    public function getContactSharedWith($contactId)
    {
        $usersOb = $this->entityManager->getRepository(User::class)->findAll();
            
        $contactSharedWithArr = array();
        foreach($usersOb as $user)
        {
            $contactIsShared = $this->isContactShared($user->getId(), $contactId);
            $contactSharedWithArr[] = array('userId' => $user->getId(), 'userFullName' => $user->getUserIdentifier(), 'contactIsShared' => $contactIsShared);
        }

        return $contactSharedWithArr;
    }

    public function createContact(Request $request, $userOb)
    {

        $id = $request->get('id');
        $title = $request->get('title');
        $comment = $request->get('comment');
        $phone = $request->get('phone');
        $fullName = $request->get('full_name');
        $address = $request->get('address');

        if($id){
            $contactOb = $this->entityManager->getRepository(Contacts::class)->findOneBy(['id'=>$id]);
        } else {
            $contactOb = new Contacts();
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
        
        return $this->entityManager->flush();
    }
}