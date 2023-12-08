<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use App\Validator as ReviewAssert;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[UniqueEntity(
    fields: ['movie', 'createdBy'],
    errorPath: 'createdBy',
    message: 'User has already rated this movie'
)]
#[ReviewAssert\IsReviewComplete]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\Column(type: "uuid", unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups('review')]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups('review')]
    private ?Movie $movie = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('review')]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(
        min: 0,
        max: 5,
        notInRangeMessage: 'Ratings must be between {{ min }} and {{ max }}',
    )]
    #[Assert\NotBlank(message: "Rating is required")]
    #[Groups('review')]
    private ?int $rating = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups('review')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups('review')]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups('review')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable|\DateTime $createdAt): static
    {
        if ($createdAt instanceof \DateTime) {
            $this->createdAt = \DateTimeImmutable::createFromMutable($createdAt);
        } else {
            $this->createdAt = $createdAt;
        }

        return $this;
    }
}
