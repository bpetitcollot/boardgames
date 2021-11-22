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

    public function findWithActions($id){
        return $this->createQueryBuilder('g')
            ->addSelect('a0, a1, a2, a3, a4, p1, p2, p3, p4')
            ->leftJoin('g.actionsRoot', 'a0')
            ->leftJoin('a0.children', 'a1')
            ->leftJoin('a1.player', 'p1')
            ->leftJoin('a1.children', 'a2')
            ->leftJoin('a2.player', 'p2')
            ->leftJoin('a2.children', 'a3')
            ->leftJoin('a3.player', 'p3')
            ->leftJoin('a3.children', 'a4')
            ->leftJoin('a4.player', 'p4')
            ->where('g.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getOneOrNullResult()
        ;
    }
    
}
