<?php

declare(strict_types=1);

namespace Paysera\LoggingExtraBundle\Tests\Functional\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class PersistedEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @var string|null
     * @ORM\Column(type="string")
     */
    private ?string $field;

    /**
     * @ORM\ManyToOne(targetEntity="PersistedEntity")
     */
    private ?PersistedEntity $parent;

    /**
     * @ORM\OneToMany(targetEntity="PersistedEntity", cascade={"all"}, mappedBy="parent")
     */
    private array|Collection|ArrayCollection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function setField(string $field): self
    {
        $this->field = $field;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }
}
