<?php

namespace App\Controller;

use App\Entity\Boardgame;
use App\Form\BoardgameType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class BoardgamesController extends AbstractController
{

    /**
     * List of all available boardgames
     * Handle boardgame creation
     */
    public function index(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $boardgameRep = $em->getRepository(Boardgame::class);
        $boardgames = $boardgameRep->findAll();
        $boardgame = new Boardgame();
        $form = $this->createForm(BoardgameType::class, $boardgame);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $boardgame->generateSlug();
            $em->persist($boardgame);
            $em->flush();

            return $this->redirectToRoute('boardgames_index');
        }

        return $this->render('index.html.twig', [
                'boardgames' => $boardgames,
                'form' => $form->createView(),
        ]);
    }
}
