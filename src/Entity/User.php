<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\File;
use Symfony\Component\DomCrawler\Image;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Email]
    #[Assert\Length(
        min: 10,
        max: 60
    )]
    #[Assert\NoSuspiciousCharacters]
    private ?string $email = null;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(
        min: 3,
        max: 25
    )]
   //#[Assert\NoSuspiciousCharacters]
   //#[Assert\PasswordStrength(
   //    message: 'Debe contener al menos un número y una mayúscula.'
   //)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(
        allowNull: true
    )]
    #[Assert\Type('string')]
    #[Assert\NoSuspiciousCharacters]
    private ?string $photo = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(
        allowNull: true
    )]
    #[Assert\Type('string')]
    #[Assert\Length(
        min: 0,
        max: 255,
    )]
    #[Assert\NoSuspiciousCharacters]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Interaction::class, orphanRemoval: true)]
    private Collection $interactions;

    #[ORM\Column(length: 255)]
    private ?string $first_name = null;

    #[ORM\Column(length: 255)]
    private ?string $last_name = null;

    #[ORM\Column]
    #[Assert\Type('int')]
    #[Assert\Length(
        min: 1,
        max: 10,
        minMessage: 'Debe contener más de {{ limit }} carácteres',
        maxMessage: 'Debe contener menos de {{ limit }} carácteres'
    )]
    #[Assert\NoSuspiciousCharacters]
    private ?int $dni = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Type('int')]
    private ?int $account_status = null;

    #[ORM\Column]
    private ?bool $hide_email = null;

    public function __construct($id = null, $email = null, $password = null, $photo = null, $description = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->password = $password;
        $this->photo = $photo;
        $this->description = $description;
        $this->posts = new ArrayCollection();
        $this->interactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->id;
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

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

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

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Interaction>
     */
    public function getInteractions(): Collection
    {
        return $this->interactions;
    }

    public function addInteraction(Interaction $interaction): static
    {
        if (!$this->interactions->contains($interaction)) {
            $this->interactions->add($interaction);
            $interaction->setUser($this);
        }

        return $this;
    }

    public function removeInteraction(Interaction $interaction): static
    {
        if ($this->interactions->removeElement($interaction)) {
            // set the owning side to null (unless already changed)
            if ($interaction->getUser() === $this) {
                $interaction->setUser(null);
            }
        }

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getDni(): ?int
    {
        return $this->dni;
    }

    public function setDni(int $dni): static
    {
        $this->dni = $dni;

        return $this;
    }

    public function getAccountStatus(): ?int
    {
        return $this->account_status;
    }

    public function setAccountStatus(int $account_status): static
    {
        $this->account_status = $account_status;

        return $this;
    }

    public function isHideEmail(): ?bool
    {
        return $this->hide_email;
    }

    public function setHideEmail(bool $hide_email): static
    {
        $this->hide_email = $hide_email;

        return $this;
    }

}
