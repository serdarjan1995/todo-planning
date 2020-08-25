<?php

namespace App\Controller;

use App\Entity\Developer;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class PageController extends AbstractController
{
    /**
     * @Route("/page/{split_providers}", name="page")
     * @param bool $split_providers
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(bool $split_providers = false)
    {
        $developers = $this->getDoctrine()->getRepository(Developer::class)->findAll();
        if (!$developers) {
            throw $this->createNotFoundException(
                'No developers found'
            );
        }

        $tasks = $this->getScheduledTasks($split_providers);
        $tasks = $this->split_into_weeks($tasks,$split_providers);

        return $this->render('page/index.html.twig', [
            'tasks' => $tasks,
            'is_providers_split' => $split_providers
        ]);
    }

    public function getScheduledTasks($split_by_providers){
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

        /// allocate jobs array
        $assingned_dev_jobs = [];
        foreach ($providers as $provider){
            $tasks_by_complexity = [];
            foreach ($complexities as $complexity){
                $tasks_by_complexity[$complexity['complexity']] = [];
            }

            /// average duration for all jobs for current provider
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

            //optimize assignments
            $developer_jobs = $this->optimizeJobs($developer_jobs, $avg_duration);
            if($split_by_providers){
                $assingned_dev_jobs[$provider['provider']] = $developer_jobs;
            }
            else{
                foreach ($developer_jobs as $dev => $val){
                    if(isset($assingned_dev_jobs[$dev])){
                        $assingned_dev_jobs[$dev]['jobs'] = array_merge($assingned_dev_jobs[$dev]['jobs'], $val['jobs']);
                    }
                    else{
                        $assingned_dev_jobs = $developer_jobs;
                        break;
                    }
                }
            }

        }

        return $assingned_dev_jobs;
    }

    public function getTotalWorkloadsDuration($array){
        $duration_sums_arr = [];
        for($i=0; $i<count($array); $i++){
            $duration_sums_arr[$i] = 0;
        }

        $i=0;
        foreach ($array as $arr){
            foreach ($arr['jobs'] as $task){
                $duration_sums_arr[$i] += $task->getDuration();
            }
            $i++;
        }
        return $duration_sums_arr;
    }

    public function optimizeJobs($developer_jobs, $avg_duration){
        $duration_sums_arr = $this->getTotalWorkloadsDuration($developer_jobs);
        $dev_names = array_keys($developer_jobs);

        $max_i = $this->getMaxIndex($duration_sums_arr);
        $min_i = array_keys($duration_sums_arr, min($duration_sums_arr))[0];
        $retries = 40;
        while (($duration_sums_arr[$max_i]-$duration_sums_arr[$min_i])>$avg_duration && --$retries){
            $task = array_pop($developer_jobs[$dev_names[$max_i]]['jobs']);
            $duration_sums_arr[$max_i] -= $task->getDuration();
            $max_i_developer_level = $developer_jobs[$dev_names[$max_i]]['developer']->getLevel();
            $min_i_developer_level = $developer_jobs[$dev_names[$min_i]]['developer']->getLevel();

            // if job is easy for developer then duration will be reduced
            $actual_duration = $max_i_developer_level/$min_i_developer_level*$task->getDuration();
            $actual_duration = ceil($actual_duration);
            $duration_sums_arr[$min_i] += $actual_duration;
            $task->setDuration($actual_duration);

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

    public function split_into_weeks($tasks_arr, $split_providers){

        $result_arr = [];
        if($split_providers){
            foreach ($tasks_arr as $provider => $developers){
                foreach ($developers as $dev_name => $developer){
                    $weekly_work_limit = 45;
                    $current_limit = 0;
                    $week = 1;

                    if(!isset($result_arr[$provider]['Week'.$week])){
                        $result_arr[$provider] = ['Week'.$week => []];
                    }

                    $result_arr[$provider]['Week'.$week][$dev_name] = ['developer' => $developer['developer'],
                        'jobs' => []];
                    $r_jobs = array_reverse($developer['jobs']);
                    while (count($r_jobs) > 0){
                        $task = array_pop($r_jobs);
                        $current_limit += $task->getDuration();
                        if($current_limit > $weekly_work_limit){
                            $exceed = $current_limit - $weekly_work_limit;
                            $task->setDuration($task->getDuration() - $exceed);
                            array_push($result_arr[$provider]['Week'.$week][$dev_name]['jobs'], $task);
                            $week++;
                            $result_arr[$provider]['Week'.$week][$dev_name] = ['developer' => $developer['developer'],
                                'jobs' => []];
                            $new_task = new Task($task->getProvider(),$task->getName().' Second Part',
                                $task->getComplexity(),$exceed);
                            array_push($result_arr[$provider]['Week'.$week][$dev_name]['jobs'], $new_task);
                            $current_limit = $exceed;
                        }
                        else if ($current_limit == $weekly_work_limit){
                            array_push($result_arr[$provider]['Week'.$week][$dev_name]['jobs'], $task);
                            $current_limit = 0;
                            $week++;
                            $result_arr[$provider]['Week'.$week][$dev_name] = ['developer' => $developer['developer'],
                                'jobs' => []];
                        }
                        else{
                            array_push($result_arr[$provider]['Week'.$week][$dev_name]['jobs'], $task);
                        }
                    }
                }
            }
        }
        else{
            foreach ($tasks_arr as $dev_name => $developer){
                $weekly_work_limit = 45;
                $current_limit = 0;
                $week = 1;

                if(!isset($result_arr['Week'.$week])){
                    $result_arr = ['Week'.$week => []];
                }

                $result_arr['Week'.$week][$dev_name] = ['developer' => $developer['developer'],
                    'jobs' => []];
                $r_jobs = array_reverse($developer['jobs']);
                while (count($r_jobs) > 0){
                    $task = array_pop($r_jobs);
                    $current_limit += $task->getDuration();
                    if($current_limit > $weekly_work_limit){
                        $exceed = $current_limit - $weekly_work_limit;
                        $task->setDuration($task->getDuration() - $exceed);
                        array_push($result_arr['Week'.$week][$dev_name]['jobs'], $task);
                        $week++;
                        $result_arr['Week'.$week][$dev_name] = ['developer' => $developer['developer'],
                            'jobs' => []];
                        $new_task = new Task($task->getProvider(),$task->getName().' Second Part',
                            $task->getComplexity(),$exceed);
                        array_push($result_arr['Week'.$week][$dev_name]['jobs'], $new_task);
                        $current_limit = $exceed;
                    }
                    else if ($current_limit == $weekly_work_limit){
                        array_push($result_arr['Week'.$week][$dev_name]['jobs'], $task);
                        $current_limit = 0;
                        $week++;
                        $result_arr['Week'.$week][$dev_name] = ['developer' => $developer['developer'],
                            'jobs' => []];
                    }
                    else{
                        array_push($result_arr['Week'.$week][$dev_name]['jobs'], $task);
                    }
                }

            }

        }

        return $result_arr;
    }

}
