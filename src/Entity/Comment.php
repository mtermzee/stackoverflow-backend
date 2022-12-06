<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Doctrine\SetOwnerListener;
use Carbon\Carbon;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            securityMessage: "Only authenticated users can create comments"
        ),
        new Put(
            security: "is_granted('ROLE_USER') and object.getOwner() == user",
            securityMessage: "Only authenticated owners can update comments"
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getOwner() == user",
            securityMessage: "Only authenticated owners can delete comments"
        ),
    ],
    normalizationContext: ['groups' => ['comment:read'], 'swagger_definition_name' => 'Read'],
    denormalizationContext: ['groups' => ['comment:write'], 'swagger_definition_name' => 'Write'],
)]
#[ApiFilter(SearchFilter::class, properties: ['owner.name' => 'partial'])]
#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\EntityListeners([SetOwnerListener::class])]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['comment:read', 'comment:write', 'answer:read', 'userAPI:read'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updatedAt = null;

    #[Groups(['comment:read', 'comment:write'])]
    #[ORM\ManyToOne(inversedBy: 'comments', fetch: 'EXTRA_LAZY'), OrderBy(['createdAt' => 'DESC'])]
    private ?Answer $answer = null;

    #[Groups(['comment:read'])]
    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Groups(['comment:read'])]
    public function getCreatedAtAgo(): string
    {
        return Carbon::instance($this->getCreatedAt())->diffForHumans();
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[Groups(['comment:read'])]
    public function getUpdatedAtAgo(): string
    {
        return Carbon::instance($this->getUpdatedAt())->diffForHumans();
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAnswer(): ?Answer
    {
        return $this->answer;
    }

    public function setAnswer(?Answer $answer): self
    {
        $this->answer = $answer;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}
