<?php

namespace App\Entity;

use App\Repository\PartnerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PartnerRepository::class)]
#[UniqueEntity(
    fields: ['name', 'address', 'postalCode', 'city'],
    message: 'Ce partenaire existe déjà.',
    errorPath: '',
)]
class Partner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column]
    #[Assert\NotBlank]
    private string $address;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{5}$/i',
        message: 'Le code postal doit être composé uniquement de 5 chiffres.'
    )]
    private string $postalCode;

    #[ORM\Column]
    #[Assert\NotBlank]
    private string $city;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $offer;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getOffer(): string
    {
        return $this->offer;
    }

    public function setOffer(string $offer): static
    {
        $this->offer = $offer;

        return $this;
    }
}
