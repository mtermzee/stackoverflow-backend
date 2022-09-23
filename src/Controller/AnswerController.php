<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Repository\AnswerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class AnswerController extends AbstractController
{
    #[Route('/answers', name: 'app_answer')]
    public function index(): Response
    {
        return $this->render('answer/index.html.twig', [
            'controller_name' => 'AnswerController',
        ]);
    }

    //commentVote
    #[Route('/answers/{id}/vote', name: 'answer_vote', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function commentVote(Answer $answer, LoggerInterface $logger, Request $request, EntityManagerInterface $entityManager): Response
    {
        // get user
        $logger->info('{user} is voting on answer {answer}', [
            'user' => $this->getUser()->getUserIdentifier(),
            'answer' => $answer->getId(),
        ]);

        // todo use id to query database for comment
        $data = json_decode($request->getContent(), true);
        $direction = $data['direction'] ?? 'up';

        // use real logic here to save this to the database
        if ($direction === 'up') {
            $logger->info('Voting up!');
            $answer->upVote();
        } else {
            $logger->info('Voting down!');
            $answer->downVote();
        }

        //dd($answer, $request->request->all());

        $entityManager->flush();

        return $this->json(['votes' => $answer->getVotes()]);
    }

    #[Route('/answers/popular', name: 'app_popular_answers', methods: ['GET'])]
    public function popularAnswers(AnswerRepository $answerRepository, Request $request): Response
    {
        $answers = $answerRepository->findeMostPopular(
            $request->query->get('q')
        );

        return $this->render('answer/popularAnswers.html.twig', [
            'answers' => $answers,
        ]);
    }
}
