<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TeamRepository")
 * @UniqueEntity(fields="name",message="Ce nom d'équipe est déjà utilisé.")
 * @UniqueEntity(fields="tag",message="ce tag est déjà utilisée.")
 * @ORM\HasLifecycleCallbacks
 */
class Team
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $tag;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $logo;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $recruitStatus;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TeamMembership", mappedBy="team", orphanRemoval=true)
     */
    private $memberships;

    private $leader;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TournamentParticipation", mappedBy="team")
     */
    private $participations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TeamInvitation", mappedBy="team", orphanRemoval=true)
     */
    private $teamInvitations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\TournamentApplication", mappedBy="team", orphanRemoval=true)
     */
    private $tournamentApplications;

    public function __construct()
    {
        $this->memberships = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->teamInvitations = new ArrayCollection();
        $this->tournamentApplications = new ArrayCollection();
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
        $this->name = $name;

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

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
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

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRecruitStatus(): ?bool
    {
        return $this->recruitStatus;
    }

    public function setRecruitStatus(?bool $recruitStatus): self
    {
        $this->recruitStatus = $recruitStatus;

        return $this;
    }   

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection|TeamMembership[]
     */
    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function addMembership(TeamMembership $membership): self
    {
        if (!$this->memberships->contains($membership)) {
            $this->memberships[] = $membership;
            $membership->setTeam($this);
        }

        return $this;
    }

    public function removeMembership(TeamMembership $membership): self
    {
        if ($this->memberships->contains($membership)) {
            $this->memberships->removeElement($membership);
            // set the owning side to null (unless already changed)
            if ($membership->getTeam() === $this) {
                $membership->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TournamentParticipation[]
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(TournamentParticipation $participation): self
    {
        if (!$this->participations->contains($participation)) {
            $this->participations[] = $participation;
            $participation->setTeam($this);
        }

        return $this;
    }

    public function removeParticipation(TournamentParticipation $participation): self
    {
        if ($this->participations->contains($participation)) {
            $this->participations->removeElement($participation);
            // set the owning side to null (unless already changed)
            if ($participation->getTeam() === $this) {
                $participation->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|TeamInvitation[]
     */
    public function getTeamInvitations(): Collection
    {
        return $this->teamInvitations;
    }

    public function addTeamInvitation(TeamInvitation $teamInvitation): self
    {
        if (!$this->teamInvitations->contains($teamInvitation)) {
            $this->teamInvitations[] = $teamInvitation;
            $teamInvitation->setTeam($this);
        }

        return $this;
    }

    public function removeTeamInvitation(TeamInvitation $teamInvitation): self
    {
        if ($this->teamInvitations->contains($teamInvitation)) {
            $this->teamInvitations->removeElement($teamInvitation);
            // set the owning side to null (unless already changed)
            if ($teamInvitation->getTeam() === $this) {
                $teamInvitation->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * Permet d'initialiser le slug
     * 
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     * @ return void
     */
    public function initializeSlug() {
        if (empty($this->slug)){
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->name);
        }
    }

    // Trouver le chef de l'équipe
    public function getLeader()
    {        
        foreach($this->memberships as $member){
            if ($member -> getTeamRole() ->getTitle() =='Leader' ){
                $leader = $member -> getMember() ;
                return $leader;
            }     
        }
        return null;
    }

    // Verifie si $user fait partie de l'équipe
    public function isMember($user)
    {        
        foreach($this->memberships as $member){
            if ($member -> getMember()  == $user ){
                             return true;
            }     
        }
        return false;
    }

    /**
     * @return Collection|TournamentApplication[]
     */
    public function getTournamentApplications(): Collection
    {
        return $this->tournamentApplications;
    }

    public function addTournamentApplication(TournamentApplication $tournamentApplication): self
    {
        if (!$this->tournamentApplications->contains($tournamentApplication)) {
            $this->tournamentApplications[] = $tournamentApplication;
            $tournamentApplication->setTeam($this);
        }
        return $this;
    }

    public function removeTournamentApplication(TournamentApplication $tournamentApplication): self
    {
        if ($this->tournamentApplications->contains($tournamentApplication)) {
            $this->tournamentApplications->removeElement($tournamentApplication);
            // set the owning side to null (unless already changed)
            if ($tournamentApplication->getTeam() === $this) {
                $tournamentApplication->setTeam(null);
            }
        }

        return $this;
    }




}
