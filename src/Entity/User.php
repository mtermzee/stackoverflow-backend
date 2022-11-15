<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;
use Carbon\Carbon;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\State\UserPasswordHasher;

#[ApiResource(
    processor: UserPasswordHasher::class,
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            securityMessage: "Only authenticated users can fitch users"
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            securityMessage: "Only authenticated users can fitch user"
        ),
        new Post(),
        new Put(
            security: "is_granted('ROLE_USER') and object == user",
            securityMessage: "Only authenticated owners can update users"
        )
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['userAPI:read', 'user:read', 'user:write'])]
    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[Groups(['userAPI:read', 'user:read', 'user:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private $joinedAt;

    #[Groups(['userAPI:read', 'user:read'])]
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:write'])]
    #[SerializedName('password')]
    #[Assert\NotBlank]
    private ?string $plainPassword = null;

    #[Groups(['userAPI:read', 'user:read'])]
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Question::class)]
    private Collection $questions;

    #[Groups(['userAPI:read', 'user:read'])]
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Answer::class)]
    private Collection $answers;

    #[Groups(['userAPI:read', 'user:read'])]
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Comment::class)]
    private Collection $comments;

    #[Groups(['userAPI:read', 'user:read'])]
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Tag::class)]
    private Collection $tags;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeInterface
    {
        return $this->joinedAt;
    }

    #[Groups(['userAPI:read', 'user:read'])]
    public function getJoinedAtAgo(): string
    {
        return Carbon::instance($this->getJoinedAt())->diffForHumans();
    }

    public function setJoinedAt(?\DateTimeInterface $joinedAt): self
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        $this->password = null;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        $this->plainPassword = null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(['userAPI:read', 'user:read'])]
    #[SerializedName('displayNameEmail')]
    public function getDisplayName(): string
    {
        return $this->getName() ?? $this->getEmail();
    }

    #[Groups(['userAPI:read', 'user:read'])]
    public function getAvatarURL(): ?string
    {
        // . urlencode($this->getDisplayName()
        return 'https://ui-avatars.com/api?'  . http_build_query([
            'name' => $this->getDisplayName(),
            'background' => 'random',
            'size' => 64,
        ]);
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
            $question->setOwner($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->questions->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getOwner() === $this) {
                $question->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Answer>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers->add($answer);
            $answer->setOwner($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getOwner() === $this) {
                $answer->setOwner(null);
            }
        }

        return $this;
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
            $comment->setOwner($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getOwner() === $this) {
                $comment->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->setOwner($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        //if ($this->getRoles() === 'ROLE_ADMIN')

        if ($this->tags->removeElement($tag)) {
            // set the owning side to null (unless already changed)
            if ($tag->getOwner() === $this) {
                $tag->setOwner(null);
            }
        }

        return $this;
    }
}
