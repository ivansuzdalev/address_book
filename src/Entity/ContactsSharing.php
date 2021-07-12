<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactsSharing
 *
 * @ORM\Table(name="contacts_sharing")
 * @ORM\Entity
 */
class ContactsSharing
{
    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $userId;

    /**
     * @var int
     *
     * @ORM\Column(name="contact_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $contactId;

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }


}
