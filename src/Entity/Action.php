<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Action
{

    private $id;
    private $name;
    private $params;
    private $player;
    private $parent;
    private $children;
    private $required;
    private $declined;
    private $completed;
    private $choices;
    private $extraDatas;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->params = array();
        $this->required = false;
        $this->declined = false;
        $this->completed = false;
        $this->choices = array();
        $this->extraDatas = array();
    }

    public function getId()
    {
        return $this->id;
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

    public function getPlayer()
    {
        return $this->player;
    }

    public function setPlayer($player)
    {
        $this->player = $player;
        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }
    
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function addChild($action)
    {
        if (!$this->children->contains($action)) {
            $this->children->add($action);
            $action->setParent($this);
        }
        return $this;
    }

    public function removeChild($action)
    {
        if ($this->children->contains($action)) {
            $this->children->removeElement($action);
            $action->setParent(null);
        }
        return $this;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }

    public function isDeclined()
    {
        return $this->declined;
    }

    public function setDeclined($declined)
    {
        $this->declined = $declined;
        return $this;
    }

    public function decline()
    {
        $this->declined = true;
        return $this;
    }

    public function isCompleted()
    {
        return $this->completed;
    }

    public function complete()
    {
        $this->completed = true;
        return $this;
    }

    public function getChoices()
    {
        return $this->choices;
    }

    public function setChoices($choices)
    {
        $this->choices = $choices;
        return $this;
    }

    public function getExtraDatas()
    {
        return $this->extraDatas;
    }

    public function setExtraDatas($extraDatas)
    {
        $this->extraDatas = $extraDatas;
        return $this;
    }
    
    public function getExtraData($key)
    {
        return array_key_exists($key, $this->extraDatas) ? $this->extraDatas[$key] : null;
    }
    
    public function addExtraData($key, $value)
    {
        $this->extraDatas[$key] = $value;
    }

    public function getUncompletedSubactions()
    {
        $actions = array();
        foreach ($this->children as $action) {
            if ($action->isCompleted()) {
                foreach ($action->getUncompletedSubactions() as $uncompletedSubaction) {
                    $actions[] = $uncompletedSubaction;
                }
            }
            elseif (!$action->isDeclined())
                $actions[] = $action;
        }

        return $actions;
    }

    public function retrieveActionsRoot()
    {
        $action = $this;
        while($action->getParent() !== null)
        {
            $action = $action->getParent();
        }
        
        return $action;
    }
    
    public function removeUncompletedSubactions()
    {
        foreach ($this->children as $action)
        {
            $action->removeUncompletedSubactions();
            if (!$action->isCompleted())
            {
                $this->children->remove($action);
            }
        }
    }

    /**
     * Next action is the first child of current action or the next one in parent's children or parent's not-child next action.
     * 
     * @return Action | null
     */
    public function nextAction($includeChildren = true)
    {
        if ($includeChildren && count($this->children) > 0) return $this->children[0];
        elseif ($this->parent !== null)
        {
            $actions = $this->parent->getChildren()->toArray();
            $key = array_search($this, $actions, true);
            if (array_key_exists($key + 1, $actions)) return $actions[$key + 1];
            else return $this->parent->nextAction(false);
        }
        return null;
    }
    
    public function getParents($callback = null)
    {
        if ($callback === null || $this->parent === null) return null;
        elseif ($callback($this->parent)) return $this->parent;
        else return $this->parent->getParents($callback);
    }
}
