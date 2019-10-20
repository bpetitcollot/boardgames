<?php

namespace App\Model\Innovation;

class Set extends Component implements ContainerInterface
{

    protected $name;
    protected $elements;
    protected $owner;

    public function __construct($name, MetaPlayerInterface $owner = null)
    {
        parent::__construct();
        $this->name = $name;
        $this->elements = array();
        $this->owner = $owner;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner(MetaPlayerInterface $owner = null)
    {
        $this->owner = $owner;
        return $this;
    }

    public function add($element)
    {
        $this->elements[] = $element;
        $element->setContainer($this);
    }

    public function remove($element)
    {
        if (in_array($element, $this->elements)) {
            $this->elements = array_filter($this->elements, function($e) use ($element) {
                return $e !== $element;
            });
            $element->setContainer(null);
        }
    }

    public function getElements()
    {
        return $this->elements;
    }
    
    public function setElements(array $elements)
    {
        $this->elements = $elements;
        
        return $this;
    }
    
    public function getSize()
    {
        return count($this->elements);
    }

    public function isEmpty()
    {
        return count($this->elements) === 0;
    }

    public function removeContent(Component $component)
    {
        $this->remove($component);
    }

    public function __toString()
    {
        return ($this->owner ? $this->owner.'\'s ' : '').$this->name;
    }
}
