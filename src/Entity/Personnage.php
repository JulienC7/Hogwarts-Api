<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PersonnageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PersonnageRepository::class)]
#[ApiResource]
class Personnage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $hogwartsId = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $genre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'personnages')]
    private ?Maison $maison = null;

    #[ORM\ManyToOne(inversedBy: 'personnages')]
    private ?Espece $espece = null;

    #[ORM\ManyToOne(inversedBy: 'personnages')]
    private ?Patronus $patronus = null;

    /**
     * @var Collection<int, Baguette>
     */
    #[ORM\ManyToMany(targetEntity: Baguette::class, inversedBy: 'personnages')]
    private Collection $baguettes;

    public function __construct()
    {
        $this->baguettes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHogwartsId(): ?string
    {
        return $this->hogwartsId;
    }

    public function setHogwartsId(string $hogwartsId): static
    {
        $this->hogwartsId = $hogwartsId;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getMaison(): ?Maison
    {
        return $this->maison;
    }

    public function setMaison(?Maison $maison): static
    {
        $this->maison = $maison;

        return $this;
    }

    public function getEspece(): ?Espece
    {
        return $this->espece;
    }

    public function setEspece(?Espece $espece): static
    {
        $this->espece = $espece;

        return $this;
    }

    public function getPatronus(): ?Patronus
    {
        return $this->patronus;
    }

    public function setPatronus(?Patronus $patronus): static
    {
        $this->patronus = $patronus;

        return $this;
    }

    /**
     * @return Collection<int, Baguette>
     */
    public function getBaguettes(): Collection
    {
        return $this->baguettes;
    }

    public function addBaguette(Baguette $baguette): static
    {
        if (!$this->baguettes->contains($baguette)) {
            $this->baguettes->add($baguette);
        }

        return $this;
    }

    public function removeBaguette(Baguette $baguette): static
    {
        $this->baguettes->removeElement($baguette);

        return $this;
    }
}
