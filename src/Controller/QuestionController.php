<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    #[Route('/questions', name: 'app_question')]
    public function index(QuestionRepository $questionRepository, Request $request): Response
    {
        $questions = $questionRepository->findAllAskedOrderedByNewest();

        return $this->render('question/index.html.twig', [
            'questions' => $questions,
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
