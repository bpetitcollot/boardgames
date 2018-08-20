<?php

namespace App\Model\Innovation;

abstract class Component
{
    protected $container;
    
    public function __construct()
    {
        $this->container = null;
    }
    
    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        if ($this->container !== null) $this->container->removeContent($this);
        $this->container = $container;
        return $this;
    }

}
