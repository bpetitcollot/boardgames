<?php

namespace App\Repository;

class GameRepository extends \Doctrine\ORM\EntityRepository
{
    public function findByBoardgames($boardgames)
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.actionsRoot', 'a')
            ->where('g.boardgame IN (:boardgames)')
            ->setParameter('boardgames', $boardgames)
            ->getQuery()->getResult();
    }
    
}
