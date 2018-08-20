<?php

namespace App\Entity;

class Boardgame
{
    private $id;
    private $title;
    private $slug;
    private $rulesManager;
    
    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    public function getRulesManager()
    {
        return $this->rulesManager;
    }

    public function setRulesManager($rulesManager)
    {
        $this->rulesManager = $rulesManager;
        return $this;
    }

    public function generateSlug()
    {
        $this->slug = preg_replace('#[^a-z]+#', '-', strtolower($this->title));
    }
    
}
