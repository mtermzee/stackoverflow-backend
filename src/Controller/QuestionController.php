<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;

class QuestionController extends AbstractController
{
    #[Route('/questions/{page<\d+>}', name: 'app_question')]
    public function index(QuestionRepository $questionRepository, Request $request, int $page = 1): Response
    {
        //$questions = $questionRepository->findAllAskedOrderedByNewest();
        $queryBuilder = $questionRepository->createAskedOrderByNewestQueryBuilder();

        $pagerfanta = new Pagerfanta(
            new QueryAdapter($queryBuilder)
        );
        $pagerfanta->setMaxPerPage(5);
        $pagerfanta->setCurrentPage($page);
        //$pagerfanta->setCurrentPage($request->query->getInt('page', 1));
        //dd($questions);

        return $this->render('question/index.html.twig', [
            'pager' => $pagerfanta,
            //'questions' => $questions,
        ]);
    }

    //Automatic Controller Queries: Param Converter
    #[Route('/questions/{slug}', name: 'app_question_show')]
    public function show(Question $question): Response
    {
        return $this->render('question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/questions/{slug}/vote', name: 'app_question_vote', methods: ['POST'])]
    public function questionVoteCount(Question $question, Request $request, EntityManagerInterface $entityManager)
    {
        $direction = $request->request->get('direction');

        if ($direction === 'up') {
            $question->upVote();
        } elseif ($direction === 'down') {
            $question->downVote();
        }

        //dd($question, $request->request->all());

        $entityManager->flush();

        return $this->redirectToRoute('app_question_show', [
            'slug' => $question->getSlug(),
        ]);
    }
}
