<?php

namespace App\Command;

use App\Entity\Developer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateDeveloperCommand extends Command
{
    protected static $defaultName = 'create:developer';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }


    protected function configure()
    {
        $this
            ->setDescription('Create Developer Entity')
            ->setHelp('This command allows you to create a developer...')
            //->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('default', 'd', InputOption::VALUE_NONE,
                'Create 5 default developers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->entityManager;

        $output->writeln([
            'Developer Creator',
            '============',
            '',
        ]);
        try {
            if ($input->getOption('default')) {
                $output->writeln([
                    '',
                    'Using Default',
                    '',
                ]);
                for ($i=1; $i<=5; $i++){
                    $dev = new Developer('Dev'.$i,$i);
                    $em->persist($dev);
                }
                $em->flush();
            }
            else{
                $name = $io->ask('Enter name: ', 'Developer');
                $level = (int)$io->ask('Enter level: ', 1);
                $dev = new Developer($name,$level);
                $em->persist($dev);
                $em->flush();
            }
        }
        catch (\Exception $e){
            $io->error('Failed to Create Developer...');
            $output->writeln($e->getMessage());
            return -1;
        }

        $io->success('Success!');

        return 0;
    }
}
