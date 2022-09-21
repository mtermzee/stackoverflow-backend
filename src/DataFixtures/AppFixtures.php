<?php

namespace App\DataFixtures;

use App\Factory\AnswerFactory;
use App\Factory\CommentFactory;
use App\Factory\QuestionFactory;
use App\Factory\TagFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        TagFactory::new()->createMany(100);
        CommentFactory::new()->createMany(50);

        // create 20 questions
        $questions = QuestionFactory::new()->createMany(20, function () {
            // we user return to rlate defrrent id-tags to defrrent questions
            return [
                // realte tags to the 20.questions randomly
                'tags' => TagFactory::randomRange(0, 5)
            ];
        });

        // add unpulished questions
        QuestionFactory::new()
            ->unpublished()
            ->createMany(5);

        AnswerFactory::new(function () use ($questions) {
            return [
                'question' => $questions[array_rand($questions)],
                'comments' => CommentFactory::randomRange(1, 2)
            ];
        })->needsApproval()->many(20)->create();

        AnswerFactory::createMany(100, function () use ($questions) {
            return [
                'question' => $questions[array_rand($questions)],
                'comments' => CommentFactory::randomRange(1, 2)
            ];
        });

        UserFactory::createOne(['email' => 'mtermzee@gmail.com']);
        UserFactory::new()->createMany(10);

        $manager->flush();
    }
}
