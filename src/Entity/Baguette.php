<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\BaguetteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BaguetteRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['baguette:lire']],
    denormalizationContext: ['groups' => ['baguette:ecrire']]
)]
class Baguette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['baguette:lire'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['baguette:lire', 'baguette:ecrire', 'personnage:lire'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Personnage>
     */
    #[Groups(['baguette:lire'])]
    #[ORM\ManyToMany(targetEntity: Personnage::class, mappedBy: 'baguettes')]
    private Collection $personnages;

    public function __construct()
    {
        $this->personnages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function addPersonnage(Personnage $personnage): static
    {
        if (!$this->personnages->contains($personnage)) {
            $this->personnages->add($personnage);
            $personnage->addBaguette($this);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): static
    {
        if ($this->personnages->removeElement($personnage)) {
            $personnage->removeBaguette($this);
        }

        return $this;
    }
}
