<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Carbon\Carbon;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            securityMessage: "Only authenticated admins can create tags"
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN') and object.getOwner() == user",
            securityMessage: "Only authenticated owners can update tags"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') and object.getOwner() == user",
            securityMessage: "Only authenticated owners can delete tags"
        ),
    ],
    normalizationContext: ['groups' => ['tag:read'], 'swagger_definition_name' => 'Read'],
    denormalizationContext: ['groups' => ['tag:write'], 'swagger_definition_name' => 'Write'],
)]
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[UniqueEntity('name', message: 'This tag already exists')]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['tag:read', 'tag:write', 'question:read'])]
    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $name = null;

    /* 
    beacuse we have the TimestampableEntity trait, we don't need this field anymore
    #[Groups(['read', 'write'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $taggedAt;*/

    #[Groups(['tag:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private $createdAt;

    #[Groups(['tag:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private $updatedAt;

    #[Groups(['tag:read'])]
    #[ORM\ManyToMany(targetEntity: Question::class, mappedBy: 'tags')]
    private Collection $questions;

    #[Groups(['tag:read', 'tag:write'])]
    #[ORM\ManyToOne(inversedBy: 'tags')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;


    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->taggedAt = new \DateTimeImmutable();
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
        //if ($this->owner->getRoles()[0] === 'ROLE_ADMIN') 
        $this->name = $name;

        return $this;
    }

    /* public function getTaggedAt(): ?\DateTimeImmutable
    {
        return $this->taggedAt;
    }

    public function setTaggedAt(\DateTimeImmutable $taggedAt): self
    {
        $this->taggedAt = $taggedAt;

        return $this;
    }*/

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Groups(['tag:read', 'question:read'])]
    public function getCreatedAtAgo(): string
    {
        return Carbon::instance($this->getCreatedAt())->diffForHumans();
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->addTag($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            $question->removeTag($this);
        }

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
