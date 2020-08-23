<?php

namespace App\Interfaces;

interface ProviderInterface
{
    public function fetchTasks();
    public function getTasks($em);
}