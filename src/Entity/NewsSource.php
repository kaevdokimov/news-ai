<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NewsSourceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsSourceRepository::class)]
#[ORM\Table(name: 'news_sources')]
class NewsSource implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: Types::INTEGER)]
    public ?int $id = null {
        get {
            return $this->id;
        }
    }

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 500)]
    private ?string $url = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastParsedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 10, options: ['default' => 'rus'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 10)]
    private string $country = 'rus';

    /**
     * @var Collection<int, NewsItem>
     */
    #[ORM\OneToMany(targetEntity: NewsItem::class, mappedBy: 'newsSource', cascade: ['persist', 'remove'])]
    private Collection $newsItems {
        get {
            return $this->newsItems;
        }
    }

    public function __construct()
    {
        $this->newsItems = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastParsedAt(): ?\DateTimeImmutable
    {
        return $this->lastParsedAt;
    }

    public function setLastParsedAt(?\DateTimeImmutable $lastParsedAt): static
    {
        $this->lastParsedAt = $lastParsedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function addNewsItem(NewsItem $newsItem): static
    {
        if (!$this->newsItems->contains($newsItem)) {
            $this->newsItems->add($newsItem);
            $newsItem->setNewsSource($this);
        }

        return $this;
    }

    public function removeNewsItem(NewsItem $newsItem): static
    {
        if ($this->newsItems->removeElement($newsItem) && $newsItem->getNewsSource() === $this) {
            $newsItem->setNewsSource(null);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
