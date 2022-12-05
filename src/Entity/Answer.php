<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use App\Repository\AnswerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
use App\Doctrine\SetOwnerListener;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            security: "is_granted('ROLE_USER')",
            securityMessage: "Only authenticated users can create answers"
        ),
        new Put(
            security: "is_granted('ROLE_USER') or object.getOwner() == user",
            securityMessage: "Only authenticated users or owners can update answers"
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getOwner() == user",
            securityMessage: "Only authenticated owners can delete answers"
        ),
    ],
    normalizationContext: ['groups' => ['answer:read'], 'swagger_definition_name' => 'Read'],
    denormalizationContext: ['groups' => ['answer:write'], 'swagger_definition_name' => 'Write'],
)]
#[ApiFilter(PropertyFilter::class)]
#[ApiFilter(OrderFilter::class, properties: ['votes' => 'DESC'])]
#[ORM\Entity(repositoryClass: AnswerRepository::class)]
#[ORM\EntityListeners([SetOwnerListener::class])]
class Answer
{
    /*public const STATUS_NEED_APPROVAL = 'need_approval';
    public const STATUS_SPAM = 'spam';
    public const STATUS_APPROVED = 'approved';*/

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['answer:read', 'answer:write', 'question:read', 'userAPI:read'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[Groups(['answer:write'])]
    #[ORM\Column]
    private int $votes = 0;

    /*#[Groups(['answer:read', 'answer:write'])]
    #[ORM\Column(length: 15)]
    #[Assert\NotBlank]
    private ?string $status = null;*/

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'update')]
    private $updatedAt;

    #[Groups(['answer:read', 'answer:write'])]
    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Question $question = null;

    #[Groups(['answer:read'])]
    #[ORM\OneToMany(mappedBy: 'answer', targetEntity: Comment::class)]
    private Collection $comments;

    #[Groups(['answer:read'])]
    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

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

    public function getVotes(): ?int
    {
        return $this->votes;
    }

    public function setVotes(int $votes): self
    {
        $this->votes = $votes;

        return $this;
    }

    #[Groups(['answer:read'])]
    #[SerializedName('votes')]
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

    /*  public function isApproved(): bool
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
    }*/

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    //#[Groups(['answer:read', 'question:read'])]
    #[Groups(['answer:read'])]
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

    //#[Groups(['answer:read', 'question:read'])]
    #[Groups(['answer:read'])]
    public function getUpdatedAtAgo(): string
    {
        return Carbon::instance($this->getUpdatedAt())->diffForHumans();
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

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAnswer($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getAnswer() === $this) {
                $comment->setAnswer(null);
            }
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
