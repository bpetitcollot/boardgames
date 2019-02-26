<?php

namespace App\Model\Innovation;

class Card extends Component
{
    const SLOT_TOP_LEFT = 0;
    const SLOT_BOTTOM_LEFT = 1;
    const SLOT_BOTTOM_MIDDLE = 2;
    const SLOT_BOTTOM_RIGHT = 3;

    const RESOURCE_STONE = 0;
    const RESOURCE_TREE = 1;
    const RESOURCE_CROWN = 2;
    const RESOURCE_LAMP = 3;
    const RESOURCE_FACTORY = 4;
    const RESOURCE_CLOCK = 5;
    const RESOURCE_AGE = 6;

    const COLOR_RED = 0;
    const COLOR_BLUE = 1;
    const COLOR_GREEN = 2;
    const COLOR_YELLOW = 3;
    const COLOR_PURPLE = 4;

    const AGE_CARDS = array(
        'agriculture' => array('age' => 1, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'alchimie' => array('age' => 3, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_STONE, self::RESOURCE_STONE)),
        'anatomie' => array('age' => 4, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE)),
        'antibiotiques' => array('age' => 8, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE)),
        'archerie' => array('age' => 1, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_LAMP, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'astronomie' => array('age' => 5, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'aviation' => array('age' => 8, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_CROWN)),
        'banlieues_chics' => array('age' => 9, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'bases_de_donnees' => array('age' => 10, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK)),
        'bicyclette' => array('age' => 7, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'bio-ingenierie' => array('age' => 10, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'boussole' => array('age' => 3, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_TREE)),
        'calendrier' => array('age' => 2, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_LAMP)),
        'cartographie' => array('age' => 2, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_STONE)),
        'cellules_souches' => array('age' => 10, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'charbon' => array('age' => 5, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_AGE)),
        'chemin_de_fer' => array('age' => 7, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_CLOCK, self::RESOURCE_FACTORY, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'chimie' => array('age' => 5, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_LAMP, self::RESOURCE_FACTORY, self::RESOURCE_AGE)),
        'cites_etats' => array('age' => 1, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_STONE)),
        'classification' => array('age' => 6, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'code_de_lois' => array('age' => 1, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_TREE)),
        'colonialisme' => array('age' => 4, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_LAMP, self::RESOURCE_FACTORY)),
        'communisme' => array('age' => 8, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'compagnies_marchandes' => array('age' => 5, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_CROWN)),
        'composites' => array('age' => 9, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_AGE, self::RESOURCE_FACTORY)),
        'conserves' => array('age' => 6, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_TREE, self::RESOURCE_FACTORY)),
        'construction' => array('age' => 2, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_AGE, self::RESOURCE_STONE, self::RESOURCE_STONE)),
        'construction_de_canaux' => array('age' => 2, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_TREE, self::RESOURCE_CROWN)),
        'cooperation' => array('age' => 9, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CLOCK, self::RESOURCE_CROWN)),
        'corporations' => array('age' => 8, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_CROWN)),
        'democratie' => array('age' => 6, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'domotique' => array('age' => 10, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'droit_des_societes' => array('age' => 4, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'eclairage' => array('age' => 7, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_CLOCK, self::RESOURCE_TREE)),
        'ecologie' => array('age' => 9, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'ecriture' => array('age' => 1, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_CROWN)),
        'education' => array('age' => 3, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'electricite' => array('age' => 7, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_FACTORY, self::RESOURCE_AGE, self::RESOURCE_FACTORY)),
        'elevage' => array('age' => 1, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'emancipation' => array('age' => 6, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_LAMP, self::RESOURCE_FACTORY, self::RESOURCE_AGE)),
        'encyclopedie' => array('age' => 6, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'evolution' => array('age' => 7, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'experimentation' => array('age' => 4, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP)),
        'explosifs' => array('age' => 7, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY)),
        'feodalisme' => array('age' => 3, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_STONE, self::RESOURCE_TREE, self::RESOURCE_STONE)),
        'fermentation' => array('age' => 2, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'fission' => array('age' => 9, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK)),
        'fusees' => array('age' => 8, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'genetique' => array('age' => 9, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'gratte-ciel' => array('age' => 8, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'imprimerie' => array('age' => 4, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_CROWN)),
        'industrialisation' => array('age' => 6, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_AGE)),
        'ingenierie' => array('age' => 3, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_STONE)),
        'intelligence_artificielle' => array('age' => 10, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'internet' => array('age' => 10, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_LAMP)),
        'invention' => array('age' => 4, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_FACTORY)),
        'la_roue' => array('age' => 1, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_STONE, self::RESOURCE_STONE, self::RESOURCE_STONE)),
        'le_code_des_pirates' => array('age' => 5, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_FACTORY, self::RESOURCE_CROWN, self::RESOURCE_AGE)),
        'logiciel' => array('age' => 10, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'machinerie' => array('age' => 3, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'machines-outils' => array('age' => 6, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_AGE, self::RESOURCE_FACTORY)),
        'machine_a_vapeur' => array('age' => 5, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_CROWN, self::RESOURCE_FACTORY)),
        'maconnerie' => array('age' => 1, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_AGE, self::RESOURCE_STONE, self::RESOURCE_STONE)),
        'mathematiques' => array('age' => 2, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_CROWN, self::RESOURCE_LAMP)),
        'medecine' => array('age' => 3, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE)),
        'media_de_masse' => array('age' => 8, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_LAMP)),
        'metallurgie' => array('age' => 1, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_STONE, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'miniaturisation' => array('age' => 10, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_CLOCK, self::RESOURCE_LAMP)),
        'mobilite' => array('age' => 8, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_CLOCK, self::RESOURCE_FACTORY)),
        'mondialisation' => array('age' => 10, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY, self::RESOURCE_FACTORY)),
        'monnaie' => array('age' => 2, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_CROWN)),
        'monotheisme' => array('age' => 2, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_STONE, self::RESOURCE_STONE, self::RESOURCE_STONE)),
        'moteur_a_explosion' => array('age' => 7, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_FACTORY, self::RESOURCE_AGE)),
        'mysticisme' => array('age' => 1, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_STONE, self::RESOURCE_STONE, self::RESOURCE_STONE)),
        'navigation' => array('age' => 4, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'optique' => array('age' => 3, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_AGE)),
        'ordinateurs' => array('age' => 9, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_CLOCK, self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_FACTORY)),
        'outils' => array('age' => 1, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_STONE)),
        'papier' => array('age' => 3, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_CROWN)),
        'perspective' => array('age' => 4, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_TREE)),
        'philosophie' => array('age' => 2, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP)),
        'physique' => array('age' => 5, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'poterie' => array('age' => 1, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'poudre' => array('age' => 4, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_CROWN, self::RESOURCE_FACTORY)),
        'publications' => array('age' => 7, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_LAMP, self::RESOURCE_CLOCK, self::RESOURCE_LAMP)),
        'rames' => array('age' => 1, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'reforme' => array('age' => 4, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE, self::RESOURCE_TREE)),
        'refrigeration' => array('age' => 7, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_CROWN)),
        'reseau_routier' => array('age' => 2, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_STONE, self::RESOURCE_STONE, self::RESOURCE_AGE, self::RESOURCE_STONE)),
        'robotique' => array('age' => 10, 'color' => self::COLOR_RED, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_CLOCK, self::RESOURCE_FACTORY)),
        'sante_publique' => array('age' => 7, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_AGE, self::RESOURCE_TREE)),
        'satellites' => array('age' => 9, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK)),
        'scientisme' => array('age' => 8, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'services' => array('age' => 9, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_TREE, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'specialisation' => array('age' => 9, 'color' => self::COLOR_PURPLE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_TREE, self::RESOURCE_FACTORY)),
        'statistiques' => array('age' => 5, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_LAMP, self::RESOURCE_TREE, self::RESOURCE_AGE)),
        'systeme_bancaire' => array('age' => 5, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_FACTORY, self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_CROWN)),
        'systeme_metrique' => array('age' => 6, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_FACTORY, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'theorie_de_la_mesure' => array('age' => 5, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_TREE, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'theorie_de_l_atome' => array('age' => 6, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_LAMP, self::RESOURCE_AGE)),
        'theorie_quantique' => array('age' => 8, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_CLOCK, self::RESOURCE_AGE)),
        'tissage' => array('age' => 1, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_TREE, self::RESOURCE_TREE)),
        'traduction' => array('age' => 3, 'color' => self::COLOR_BLUE, 'resources' => array(self::RESOURCE_AGE, self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_CROWN)),
        'vaccination' => array('age' => 6, 'color' => self::COLOR_YELLOW, 'resources' => array(self::RESOURCE_TREE, self::RESOURCE_FACTORY, self::RESOURCE_TREE, self::RESOURCE_AGE)),
        'voiles' => array('age' => 1, 'color' => self::COLOR_GREEN, 'resources' => array(self::RESOURCE_CROWN, self::RESOURCE_CROWN, self::RESOURCE_AGE, self::RESOURCE_TREE)),
    );

    private $age;
    private $color;
    private $name;
    private $resources;

    public function __construct($age, $color, $name, $resources)
    {
        parent::__construct();
        $this->age = $age;
        $this->color = $color;
        $this->name = $name;
        $this->resources = $resources;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function hasResource($resource)
    {
        return in_array($resource, $this->getResources());
    }
    
    public function __toString()
    {
        return $this->name;
    }
}
