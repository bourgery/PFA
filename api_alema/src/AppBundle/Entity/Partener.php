<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity()
 * @ORM\Table(name="parteners")
 */
class Partener
{
	
	/**
	 * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=500)
	 */
	protected $description;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $urlSite;

	/**
	 * @ORM\Column(type="string")
	 */
	protected $urlImg;

    public function getId(){
    	return $this->id;
    }

    public function setId($id){
    	$this->id = $id;
    	return $this;
    }

   	public function getDescription(){
		return $this->description;
	}

	public function setDescription($description){
		$this->description = $description;
		return $this;
	}

	public function getUrlSite(){
    	return $this->urlSite;
    }

    public function setUrlSite($urlSite){
    	$this->urlSite = $urlSite;
    	return $this;
    }

    public function getUrlImg(){
    	return $this->urlImg;
    }

    public function setUrlImg($urlImg){
    	$this->urlImg = $urlImg;
    	return $this;
    }


}