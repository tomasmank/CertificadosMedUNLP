<?php

namespace App\Entity;

use App\Entity\Event;
use App\Repository\AttendeeRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AttendeeRepository::class)
 */
class Attendee
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $last_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dni;

    /**
     * @ORM\OneToMany(targetEntity=EventAttendee::class, mappedBy="attendee", orphanRemoval=true)
     */
    private $eventAttendees;

    public function __construct()
    {
        $this->eventAttendees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getDni(): ?string
    {
        return $this->dni;
    }

    public function setDni(string $dni): self
    {
        $this->dni = $dni;

        return $this;
    }

    /**
     * @return Collection|EventAttendee[]
     */
    public function getEventAttendees(): Collection
    {
        return $this->eventAttendees;
    }

    public function addEventAttendee(EventAttendee $eventAttendee): self
    {
        if (!$this->eventAttendees->contains($eventAttendee)) {
            $this->eventAttendees[] = $eventAttendee;
            $eventAttendee->setAttendee($this);
        }

        return $this;
    }

    public function removeEventAttendee(EventAttendee $eventAttendee): self
    {
        if ($this->eventAttendees->removeElement($eventAttendee)) {
            // set the owning side to null (unless already changed)
            if ($eventAttendee->getAttendee() === $this) {
                $eventAttendee->setAttendee(null);
            }
        }

        return $this;
    }
}
