<?php

namespace App\Command;

use App\Entity\Provider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateProviderCommand extends Command
{
    protected static $defaultName = 'create:provider';

    private $entityManager;

    private $default_providers = [
        [
            'name' => 'Provider1',
            'url' => 'http://www.mocky.io/v2/5d47f24c330000623fa3ebfa',
            'json_layout_type' => 1,
            'task_name_key' => 'id',
            'task_complexity_key' => 'zorluk',
            'task_duration_key' => 'sure',
        ],
        [
            'name' => 'Provider2',
            'url' => 'http://www.mocky.io/v2/5d47f235330000623fa3ebf7',
            'json_layout_type' => 2,
            'task_name_key' => '',
            'task_complexity_key' => 'level',
            'task_duration_key' => 'estimated_duration',
        ]
    ];

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Create Provider')
            ->setHelp('This command allows you to create a task provider...')
            ->addOption('default', 'd', InputOption::VALUE_NONE,
                'Get tasks from default 2 providers:\n
                http://www.mocky.io/v2/5d47f24c330000623fa3ebfa\n
                http://www.mocky.io/v2/5d47f235330000623fa3ebf7')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $em = $this->entityManager;

        $output->writeln([
            'Provider Creator',
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
                foreach ($this->default_providers as $default_provider){
                    $provider = new Provider($default_provider['name'], $default_provider['url'],
                        $default_provider['json_layout_type'], $default_provider['task_name_key'],
                        $default_provider['task_complexity_key'], $default_provider['task_duration_key']);
                    $provider->getTasks($em);
                    $em->persist($provider);
                    $output->writeln($default_provider['name'].' done.');
                }
                $em->flush();
            }
            else{
                $name = $io->ask('Enter name: ', 'Provider');
                $url = $io->ask('Enter url: ');
                $output->writeln([
                    'JSON Layout Types',
                    '============',
                    '',
                ]);
                $output->writeln([
                    '1: {',
                    '   "task_name_key": "Sample task name",',
                    '   "task_complexity_key": 1,',
                    '   "task_duration_key": 5',
                    '}',
                    '',
                ]);
                $output->writeln([
                    '2: {',
                    '   "Sample task name": {',
                    '       "task_complexity_key": 1,',
                    '       "task_duration_key": 5',
                    '   }',
                    '}',
                    '',
                ]);
                $json_layout_type = $io->ask('Enter JSON Layout Type: ');
                while(!is_int($json_layout_type) && ($json_layout_type>2 || $json_layout_type<1)){
                    $io->caution('JSON Layout Type must be integer and equal to 1 or 2');
                    $json_layout_type = $io->ask('Enter JSON Layout Type: ');
                }
                $json_layout_type==1?$task_name_key = $io->ask('Enter Task name key: '):null;
                $task_complexity_key = $io->ask('Enter Task complexity key: ');
                $task_duration_key = $io->ask('Enter Task duration key: ');

                $provider = new Provider($name, $url, $json_layout_type, '',
                    $task_complexity_key, $task_duration_key);
                $em->persist($provider);
                $em->flush();
            }
        }
        catch (\Exception $e){
            $io->error('Failed to Create Provider...');
            $output->writeln($e->getMessage());
            return -1;
        }

        $io->success('Success!');

        return 0;
    }
}
