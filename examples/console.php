#!/usr/bin/env php
<?php

declare(strict_types=1);

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zp\Supple\Console\ConsoleRunner;
use Zp\Supple\Supple;

$factory = require __DIR__ . '/config.php';
$supple = $factory(getenv('DEBUG') !== false);

$migrateCommand = new class ('supple:example') extends Symfony\Component\Console\Command\Command {
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $postComment = new PostComment();
        $postComment->authorName = "Vasya";
        $postComment->comment = "Awesome!";

        $post1 = new Post();
        $post1->id = (int)(microtime(true) * 1000000);
        $post1->createdAt = new DateTime();
        $post1->authorID = 123;
        $post1->authorName = 'Bloje Pisaylo';
        $post1->content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.';
        $post1->comments = [$postComment];

        $post2 = new Post();
        $post2->id = (int)(microtime(true) * 1000000);
        $post2->createdAt = new DateTime();
        $post2->authorID = 123;
        $post2->authorName = 'Bloje Pisaylo';
        $post2->content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor.';
        $post2->comments = [$postComment];

        /** @var Supple $supple */
        $supple = $this->getHelper('supple')->getSupple();
        $supple->index($post1);
        $supple->index($post2);
        $result = $supple->flush();

        if ($result->hasErrors()) {
            $output->writeln('post1:');
            $output->writeln($result->getErrorsForDocumentOrID($post1, null));

            $output->writeln('post2:');
            $output->writeln($result->getErrorsForDocumentOrID($post1, null));
        } else {
            $output->writeln('success');
        }

        return 0;
    }
};

ConsoleRunner::run(
    ConsoleRunner::createHelperSet($supple),
    [$migrateCommand]
);
