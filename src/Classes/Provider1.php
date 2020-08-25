<?php


use App\Entity\Task;

class Provider1 extends AbstractProvider
{

    public function getTasks($em){

        $response_content = $this->fetchTasks();
        if ($response_content != null) {
            $response_json = json_decode($response_content,true);
            foreach ($response_json as $item){
                $task = new Task('Provider1',
                    $item['id'],
                    $item['zorluk'],
                    $item['sure']);
                $em->persist($task);
                $em->flush();
            }
            return true;
        } else {
            echo 'Something went wrong: Provider1 -> $response_content is null';
            return false;
        }
    }
}