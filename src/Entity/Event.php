<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\EventRepository;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
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
    private $name;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $published;

    /**
     * @ORM\ManyToOne(targetEntity=City::class,inversedBy="events")
     */
    private $city;

    /**
     * @ORM\ManyToOne(targetEntity=Template::class)
     */
    private $template;

    /**
     * @ORM\OneToMany(targetEntity=EventAttendee::class, mappedBy="event", orphanRemoval=true)
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template): self
    {
        $this->template = $template;

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
            $eventAttendee->setEvent($this);
        }

        return $this;
    }

    public function removeEventAttendee(EventAttendee $eventAttendee): self
    {
        if ($this->eventAttendees->removeElement($eventAttendee)) {
            // set the owning side to null (unless already changed)
            if ($eventAttendee->getEvent() === $this) {
                $eventAttendee->setEvent(null);
            }
        }

        return $this;
    }
}
