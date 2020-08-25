<?php

namespace App\Entity;

use App\Interfaces\ProviderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProviderRepository;

/**
 * @ORM\Entity(repositoryClass=ProviderRepository::class)
 */
class Provider implements ProviderInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $url;

    /**
     * @ORM\Column(type="integer")
     */
    private $json_layout_type;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $task_name_key;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $task_complexity_key;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $task_duration_key;
    private $http_client;

    public function __construct(string $name, string $url, int $json_layout_type,
                                $task_name_key, $task_complexity_key, $task_duration_key)
    {
        $this->name = $name;
        $this->url = $url;
        $this->json_layout_type = $json_layout_type;
        $this->task_name_key = $task_name_key;
        $this->task_complexity_key = $task_complexity_key;
        $this->task_duration_key = $task_duration_key;
        $this->http_client = HttpClient::create();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getLayoutType(): ?string
    {
        return $this->url;
    }

    public function setLayoutType(string $type): self
    {
        $this->json_layout_type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaskNameKey()
    {
        return $this->task_name_key;
    }

    /**
     * @return mixed
     */
    public function getTaskDurationKey()
    {
        return $this->task_duration_key;
    }

    /**
     * @return mixed
     */
    public function getTaskComplexityKey()
    {
        return $this->task_complexity_key;
    }

    public function fetchTasks()
    {
        try {
            $response = $this->http_client->request('GET', $this->url);
            if ($response->getStatusCode() == 200){
                return $response->getContent();
            }

        } catch (TransportExceptionInterface $e) {
            echo $e->getMessage();
        } catch (ClientExceptionInterface $e) {
            echo $e->getMessage();
        } catch (RedirectionExceptionInterface $e) {
            echo $e->getMessage();
        } catch (ServerExceptionInterface $e) {
            echo $e->getMessage();
        }
        return null;
    }

    public function getTasks($em)
    {
        $response_content = $this->fetchTasks();
        if ($response_content != null) {
            $response_json = json_decode($response_content,true);
            switch ($this->json_layout_type){
                case 1:
                    foreach ($response_json as $item){
                        $task = new Task($this->getName(),
                            $item[$this->getTaskNameKey()],
                            $item[$this->getTaskComplexityKey()],
                            $item[$this->getTaskDurationKey()]);
                        $em->persist($task);
                        $em->flush();
                    }
                    break;

                case 2:
                    foreach ($response_json as $item){
                        $name = array_key_first($item);
                        $task = new Task($this->getName(),
                            $name,
                            $item[$name][$this->getTaskComplexityKey()],
                            $item[$name][$this->getTaskDurationKey()]);
                        $em->persist($task);
                        $em->flush();
                    }
                    break;
            }

            return true;
        } else {
            echo 'Something went wrong: Provider -> $response_content is null';
            return false;
        }
    }
}
