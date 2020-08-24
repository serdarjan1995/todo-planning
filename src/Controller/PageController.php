<?php

namespace App\Controller;

use App\Entity\Developer;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    /**
     * @Route("/page", name="page")
     */
    public function index()
    {
        $provider_tasks = $this->getScheduledTasks();

        return $this->render('page/index.html.twig', [
            'provider_tasks' => $provider_tasks
        ]);
    }

    public function getScheduledTasks(){
        $developers = $this->getDoctrine()->getRepository(Developer::class)
            ->findBy(array(),array('level' => 'ASC'));
        if (!$developers) {
            throw $this->createNotFoundException(
                'No developers found'
            );
        }

        /// allocate developer jobs array
        $developer_jobs = [];

        $tasks = $this->getDoctrine()->getRepository(Task::class)
            ->findBy(array(),array('complexity' => 'ASC','duration' => 'ASC'));
        if (!$tasks) {
            throw $this->createNotFoundException(
                'No tasks found'
            );
        }

        $complexities = $this->getDoctrine()->getRepository(Task::class)->getDistinctComplexities();
        $providers = $this->getDoctrine()->getRepository(Task::class)->getDistinctProviders();

        /// allocate provider based jobs array
        $provider_jobs = [];
        foreach ($providers as $provider){
            $tasks_by_complexity = [];
            foreach ($complexities as $complexity){
                $tasks_by_complexity[$complexity['complexity']] = [];
            }

            /// average duration for all jobs belong to the provider
            $avg_duration = $this->getDoctrine()->getRepository(Task::class)
                ->getAvgTaskDurationByProviderName($provider['provider']);
            $avg_duration = ceil($avg_duration);

            /// get tasks by complexity and store in $tasks_by_complexity array
            foreach ($complexities as $complexity){
                $provider_tasks = $this->getDoctrine()->getRepository(Task::class)
                    ->getTasksByProviderNameAndComplexity($provider['provider'],$complexity['complexity']);
                foreach ($provider_tasks as $task){
                    array_push($tasks_by_complexity[$complexity['complexity']], $task);
                }
            }

            /// iterate over $developers and start assigning jobs by their levels
            foreach ($developers as $developer){
                $developer_jobs[$developer->getName()] = [
                        'jobs' => $tasks_by_complexity[$developer->getLevel()],
                        'developer' => $developer
                    ];
            }
            $developer_jobs = $this->optimizeJobs($developer_jobs, $avg_duration);
            $provider_jobs[$provider['provider']] = $developer_jobs;
        }
        return $provider_jobs;
    }

    public function optimizeJobs($developer_jobs, $avg_duration){
        $duration_sums_arr = [];
        for($i=0; $i<count($developer_jobs); $i++){
            $duration_sums_arr[$i] = 0;
        }

        $i=0;
        $dev_names = array_keys($developer_jobs);
        foreach ($developer_jobs as $dev_job){
            foreach ($dev_job['jobs'] as $task){
                $duration_sums_arr[$i] += $task->getDuration();
            }
            $i++;
        }

        $max_i = $this->getMaxIndex($duration_sums_arr);
        $min_i = array_keys($duration_sums_arr, min($duration_sums_arr))[0];
        while (($duration_sums_arr[$max_i]-$duration_sums_arr[$min_i])>$avg_duration){
            $task = array_pop($developer_jobs[$dev_names[$max_i]]['jobs']);
            $duration_sums_arr[$max_i] -= $task->getDuration();
            $max_i_developer_level = $developer_jobs[$dev_names[$max_i]]['developer']->getLevel();
            $min_i_developer_level = $developer_jobs[$dev_names[$min_i]]['developer']->getLevel();

            $add_duration_amount = $max_i_developer_level/$min_i_developer_level*$task->getDuration();
            $add_duration_amount = ceil($add_duration_amount);
            $duration_sums_arr[$min_i] += $add_duration_amount;
            array_push($developer_jobs[$dev_names[$min_i]]['jobs'], $task);

            $max_i = $this->getMaxIndex($duration_sums_arr);
            $min_i = array_keys($duration_sums_arr, min($duration_sums_arr))[0];
        }
        return $developer_jobs;
    }

    public function getMaxIndex($array){
        $max_i = count($array)-1;
        for ($i=$max_i; $i>=0; $i--){
            if($array[$max_i]<$array[$i]){
                $max_i = $i;
            }
        }
        return $max_i;
    }

}
