<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="trips")
 */
class Trip
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
    protected $name;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $dateStart;

    /**
     * @ORM\Column(type="string")
     */
    protected $placeStart;

    /**
     * @ORM\Column(type="string")
     */
    protected $placeEnd;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $dateEnd;

    /**
     * @ORM\Column(type="integer")
     */
    protected $numberPlace; 

    /**
     * @ORM\Column(type="string")
     */
    protected $location;

    /**
     * @ORM\Column(type="string")
     */
    protected $urlPicture;

    /**
     * @ORM\Column(type="string", length=1000)
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Director", inversedBy="tripManage")
     * @ORM\JoinColumn(name="login_director", referencedColumnName="user")
     */
    protected $manager;

    /**
     * @ORM\OneToMany(targetEntity="ParticipateTrip", mappedBy="idTrip")
     */
    protected $childParticipate;
    
    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="idTrip")
     */
    protected $comment;

    /**
     * @ORM\OneToMany(targetEntity="Actuality", mappedBy="idTrip")
     */
    protected $idActuality;

    /**
     * @ORM\OneToMany(targetEntity="Picture", mappedBy="idTrip")
     */
    protected $idPicture;


    public function __construct()
    {
        $this->childParticipate = new ArrayCollection();
        $this->comment = new ArrayCollection();
        $this->idActuality = new ArrayCollection();
        $this->idPicture = new ArrayCollection();
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getDateStart()
    {
        return $this->dateStart;
    }

    public function setDateStart($dateStart)
    {
        $this->dateStart = $dateStart;
    }

    public function getPlaceStart()
    {
        return $this->placeStart;
    }

    public function setPlaceStart($placeStart)
    {
        $this->placeStart = $placeStart;
    }

    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;
    }

    public function getPlaceEnd()
    {
        return $this->placeEnd;
    }

    public function setPlaceEnd($placeEnd)
    {
        $this->placeEnd = $placeEnd;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getUrlPicture()
    {
        return $this->urlPicture;
    }

    public function setUrlPicture($urlPicture)
    {
        $this->urlPicture = $urlPicture;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getNumberPlace()
    {
        return $this->numberPlace;
    }

    public function setNumberPlace($numberPlace)
    {
        $this->numberPlace = $numberPlace;
    }

    public function getManager(){
        return $this->manager;
    }

    public function setManager($manager){
        $this->manager = $manager;
        return $this;
    }

    public function getChildParticipate(){
        return $this->childParticipate;
    }

    public function setChildParticipate($childParticipate){
        $this->childParticipate = $childParticipate;
        return $this;
    }

    public function getIdActuality(){
        return $this->idActuality;
    }

    public function setIdActuality($idActuality){
        $this->idActuality = $idActuality;
        return $this;
    }

    public function getIdPicture(){
        return $this->idPicture;
    }

    public function setIdPicture($idPicture){
        $this->idPicture = $idPicture;
        return $this;
    }
}