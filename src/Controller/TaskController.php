<?php

namespace App\Controller;

use App\Entity\Developer;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends AbstractController
{
    /**
     * @Route("/task", name="task")
     */
    public function index()
    {
        $developers = $this->getDoctrine()->getRepository(Developer::class)->findAll();
        if (!$developers) {
            throw $this->createNotFoundException(
                'No developers found'
            );
        }
        $tasks = $this->getDoctrine()->getRepository(Task::class)
            ->findBy(array(),array('complexity' => 'ASC'));
        return $this->render('task/index.html.twig', [
            'developers' => $developers,
            'tasks' => $tasks
        ]);
    }
}
