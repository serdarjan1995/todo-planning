<?php


use App\Entity\Task;

class Provider2 extends AbstractProvider
{

    public function getTasks($em){

        $response_content = $this->fetchTasks();
        if ($response_content != null) {
            $response_json = json_decode($response_content,true);
            foreach ($response_json as $item){
                $name = array_key_first($item);
                $task = new Task('Provider2',
                    $name,
                    $item[$name]['level'],
                    $item[$name]['estimated_duration']);
                $em->persist($task);
                $em->flush();
            }
            return true;
        } else {
            echo 'Something went wrong: Provider2 -> $response_content is null';
            return false;
        }
    }
}