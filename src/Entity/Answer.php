<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use App\Repository\AnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
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
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['answer:read'], 'swagger_definition_name' => 'Read'],
    denormalizationContext: ['groups' => ['answer:write'], 'swagger_definition_name' => 'Write'],
)]
#[ApiFilter(PropertyFilter::class)]
#[ORM\Entity(repositoryClass: AnswerRepository::class)]
class Answer
{
    public const STATUS_NEED_APPROVAL = 'need_approval';
    public const STATUS_SPAM = 'spam';
    public const STATUS_APPROVED = 'approved';

    // use it for the created and updated fields
    //use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    //#[Groups(['read', 'write'])]
    #[Groups(['answer:read', 'answer:write', 'question:read'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    //#[Groups(['read', 'write'])]
    #[Groups(['answer:read', 'answer:write'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $username = null;

    //#[Groups(['read'])]
    #[Groups(['answer:read', 'answer:write'])]
    #[ORM\Column]
    private int $votes = 0;

    //#[Groups(['read'])]
    #[Groups(['answer:read', 'answer:write'])]
    #[ORM\Column(length: 15)]
    #[Assert\NotBlank]
    private ?string $status = null;

    //#[Groups(['read'])]
    #[Groups(['answer:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private $createdAt;

    //#[Groups(['read'])]
    #[Groups(['answer:read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private $updatedAt;

    //#[Groups(['write'])]
    #[Groups(['answer:read', 'answer:write'])]
    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getVotes(): ?int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    public function getVotesString(): string
    {
        $prefix = $this->votes > 0 ? '+' : '-';

        return sprintf('%s%d', $prefix, abs($this->getVotes()));
    }

    public function upVote(): self
    {
        $this->votes++;

        return $this;
    }

    public function downVote(): self
    {
        $this->votes--;

        return $this;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_NEED_APPROVAL, self::STATUS_SPAM, self::STATUS_APPROVED])) {
            throw new \InvalidArgumentException('Invalid status "%s"', $status);
        }
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Groups(['answer:read', 'question:read'])]
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

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getQuestionText(): string
    {
        if (!$this->getQuestion()) {
            return '';
        }

        return (string)$this->getQuestion()->getQuestion();
    }
}
