<?php
namespace Doctrine\Bundle\LicenseManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="author")
 */
class Author
{
    const STATUS_NONE       = 0;
    const STATUS_APPROVED   = 1;
    const STATUS_NO         = 2;
    const STATUS_NOT_PERSON = 3;

    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    protected $id;
    /** @ORM\Column */
    protected $username;
    /** @ORM\Column */
    protected $email;
    /** @ORM\Column(type="integer") */
    protected $approved = self::STATUS_NONE;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     */
    protected $project;

    /** @ORM\OneToMany(targetEntity="Commit", mappedBy="author") */
    protected $commits;

    public function __construct($username, $email, Project $project)
    {
        $this->username = $username;
        $this->email    = $email;
        $this->project  = $project;
    }

    public function hasRealEmail()
    {
        $parts = explode('@', $this->email);
        return (isset($parts[1]) && strpos($parts[1], '.') !== false);
    }

    /**
     * Get id.
     *
     * @return id.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get email.
     *
     * @return email.
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get username.
     *
     * @return username.
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get approved.
     *
     * @return approved.
     */
    public function getApproved()
    {
        return $this->approved;
    }

    public function setApproved($status)
    {
        if ($this->approved > 0) {
            return;
        }

        $this->approved = $status;
    }

    public function approve()
    {
        $this->setApproved(self::STATUS_APPROVED);
    }

    public function getCommits()
    {
        return $this->commits;
    }

    public function getProject()
    {
        return $this->project;
    }
}

