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
     * @ORM\Column(type="string", length=320)
     * @Assert\Email(
     *     message = "El mail '{{ value }}' no es una dirección de mail válida.",
     *     mode = "strict"
     * )
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $cond;

    /**
     * @var \Doctrine\Common\Collections\Collection|Event[]
     *
     * @ORM\ManyToMany(targetEntity="Event", mappedBy="attendees")
     */
    private $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
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

    public function getEvents(): Collection
    {
        return $this->events;
    }
}
