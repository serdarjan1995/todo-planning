<?php


use App\Interfaces\ProviderInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AbstractProvider implements ProviderInterface
{

    private $name;
    private $url;
    private $http_client;

    public function __construct(string $name, string $url)
    {
        $this->name = $name;
        $this->url = $url;
        $this->http_client = HttpClient::create();
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

    abstract function getTasks($em);
}