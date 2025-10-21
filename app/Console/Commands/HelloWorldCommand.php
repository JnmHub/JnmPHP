<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HelloWorldCommand extends Command
{
    // You can keep this, but we'll ensure the name is set in configure()
    // protected static $defaultName = 'app:hello-world'; // Optional now

    protected function configure(): void
    {
        $this

            ->setName('app:hello-world')
            ->setDescription('Prints Hello World message.')
            ->setHelp('This command allows you to print a greeting message...')
            ->addArgument('name', InputArgument::OPTIONAL, 'Who do you want to greet?', 'World')
            ->addOption('uppercase', 'u', InputOption::VALUE_NONE, 'Print the greeting in uppercase');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $greeting = "Hello {$name}!";

        if ($input->getOption('uppercase')) {
            $greeting = strtoupper($greeting);
        }

        $output->writeln("<info>{$greeting}</info>");

        return Command::SUCCESS;
    }
}