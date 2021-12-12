<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TemplateRepository::class)
 */
class Template
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
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $header;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $signs;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $footer;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commnets;

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

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header): self
    {
        $this->header = $header;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getSigns(): ?string
    {
        return $this->signs;
    }

    public function setSigns(?string $signs): self
    {
        $this->signs = $signs;

        return $this;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    public function setFooter(?string $footer): self
    {
        $this->footer = $footer;

        return $this;
    }

    public function getCommnets(): ?string
    {
        return $this->commnets;
    }

    public function setCommnets(?string $commnets): self
    {
        $this->commnets = $commnets;

        return $this;
    }
}