<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use App\Repository\AnswerRepository;
use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    //, 'groups' => ['user:read']
    normalizationContext: ['groups' => ['read']],
    denormalizationContext: ['groups' => ['write']],
    paginationClientEnabled: true,
    paginationItemsPerPage: 10
)]
#[ApiFilter(BooleanFilter::class, properties: ['isPublished'])]
#[ApiFilter(SearchFilter::class, properties: ['title' => 'partial', 'username' => 'exact'])]
#[ApiFilter(RangeFilter::class, properties: ['votes'])]
#[ApiFilter(PropertyFilter::class)]
#[ORM\Entity(repositoryClass: QuestionRepository::class)]
class Question
{
    // use it for the created and updated fields
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['read', 'write'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $username = null;

    #[Groups(['read', 'write'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50, maxMessage: 'The question name is too long.')]
    private ?string $title = null;

    #[Groups(['read', 'write'])]
    #[ORM\Column(length: 100)]
    #[Gedmo\Slug(fields: ['title'])]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[Groups(['read', 'write'])]
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255, maxMessage: 'The question description is too long.')]
    private ?string $question = null;

    #[Groups(['read', 'write'])]
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $askedAt = null;

    #[Groups(['read'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    private int $votes = 0;

    #[Groups(['read'])]
    #[ORM\OneToMany(mappedBy: 'question', targetEntity: Answer::class, fetch: 'EXTRA_LAZY'), OrderBy(['createdAt' => 'DESC'])]
    private Collection $answers;

    #[Groups(['read'])]
    #[ORM\ManyToMany(targetEntity: Tag::class, fetch: 'EXTRA_LAZY', inversedBy: 'questions')]
    private Collection $tags;

    #[Groups(['read'])]
    #[ORM\Column]
    private ?bool $isPublished = false;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getuserName(): ?string
    {
        return $this->username;
    }

    public function setuserName(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    #[Groups(['read'])]
    public function getShortQuestion(): ?string
    {
        //strip_tags
        if (strlen($this->question) < 40) {
            return $this->question;
        }

        return substr($this->question, 0, 40) . '...';
    }

    public function getAskedAt(): ?\DateTimeInterface
    {
        return $this->askedAt;
    }

    public function setAskedAt(?\DateTimeInterface $askedAt): self
    {
        $this->askedAt = $askedAt;

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
            $answer->setQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getQuestion() === $this) {
                $answer->setQuestion(null);
            }
        }

        return $this;
    }

    public function getApprovedAnswers(): Collection
    {
        /*return $this->answers->filter(function (Answer $answer) {
            return $answer->isApproved();
        });*/

        return $this->answers->matching(AnswerRepository::createApprovedCriteria());
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
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function isIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }
}
