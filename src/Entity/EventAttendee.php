<?php

namespace App\Entity;

use App\Repository\EventAttendeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EventAttendeeRepository::class)
 */
class EventAttendee
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Event::class, inversedBy="eventAttendees")
     * @ORM\JoinColumn(nullable=false)
     */
    private $event;

    /**
     * @ORM\ManyToOne(targetEntity=Attendee::class, inversedBy="eventAttendees")
     * @ORM\JoinColumn(nullable=false)
     */
    private $attendee;

    /**
     * @ORM\Column(type="string", length=320)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $cond;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getAttendee(): ?Attendee
    {
        return $this->attendee;
    }

    public function setAttendee(?Attendee $attendee): self
    {
        $this->attendee = $attendee;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getCond(): ?string
    {
        return $this->cond;
    }

    public function setCond(string $cond): self
    {
        $this->cond = $cond;

        return $this;
    }
}
