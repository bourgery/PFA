<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="children")
 */
class Child
{
   /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue
    */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string")
     */
    protected $lastname;

    /**
     * @ORM\Column(type="date")
     */
    protected $birthDate;

    /**
     * @ORM\Column(name="familyNumber", type="string")
     */
    protected $familyNumber;

    /**
     * @ORM\ManyToOne(targetEntity="Relative", inversedBy="children", cascade={"persist"})
     * @ORM\JoinColumn(name="login_relative", referencedColumnName="user")
     * @var Relative
     */
    protected $relative;

    /**
     * @ORM\OneToMany(targetEntity="accessChild", mappedBy="idChild")
     */
    protected $accessChildren;

    /**
     * @ORM\OneToMany(targetEntity="ParticipateTrip", mappedBy="idChild")
     */
    protected $tripParticipate;
    
    public function __construct()
    {
        $this->accessChildren = new ArrayCollection();
        $this->tripParticipate = new ArrayCollection();
    }
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    public function getFamilyNumber(){
        return $this->familyNumber;
    }

    public function setFamilyNumber($familyNumber){
        $this->familyNumber = $familyNumber;
    }

    public function getBirthDate(){
        return $this->birthDate;
    }

    public function setBirthDate($birthDate){
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getRelative(){
        return $this->relative;
    }

    public function setRelative($relative){
        $this->relative = $relative;
    }

    public function getAccessChildren(){
        return $this->accessChildren;
    }

    public function setAccessChildren($accessChildren){
        $this->accessChildren = $accessChildren;
    }

    public function getTripParticipate(){
        return $this->tripParticipate;
    }

    public function setTripParticipate($tripParticipate){
        $this->tripParticipate = $tripParticipate;
    }

}