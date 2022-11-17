<?php

namespace App\HttpClient;

use App\Security\Heimdall\Model\User;
use App\Security\Heimdall\Model\UserContract;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HeimdallClient
{
    public const GET_USER_URL = '/api/user?ldapUidNumber=%s';
    public const GET_USER_BY_USERNAME_URL = '/api/user?username=%s';
    public const GET_USER_PERMISSIONS_URL = '/api/user/%s/permissions';

    /**
     * @var HttpClientInterface
     */
    protected $heimdallClient;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(HttpClientInterface $heimdallClient, SerializerInterface $serializer)
    {
        $this->heimdallClient = $heimdallClient;
        $this->serializer = $serializer;
    }

    /**
     * @param string $ldapUidNumber
     *
     * @return User|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getUserByLdapUidNumber(string $ldapUidNumber): ?User
    {
        $response = $this->heimdallClient->request('GET', sprintf(self::GET_USER_URL, urlencode($ldapUidNumber)));

        $this->successOrException($response);

        $responseBody  = $response->toArray();

        if (count($responseBody) === 0) {
            return null;
        }

        return $this->serializer->denormalize($responseBody[0], User::class);
    }

    /**
     * @param string $ldapUidNumber
     *
     * @return User|null
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getUserByUsername(string $username): ?User
    {
        $response = $this->heimdallClient->request('GET', sprintf(self::GET_USER_BY_USERNAME_URL, urlencode($username)));

        $this->successOrException($response);

        $responseBody  = $response->toArray();

        if (count($responseBody) === 0) {
            return null;
        }

        return $this->serializer->denormalize($responseBody[0], User::class);
    }

    /**
     * @param User $user
     *
     * @return UserContract[]
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getUserContracts(User $user): array
    {
        $response = $this->heimdallClient->request('GET', sprintf(static::GET_USER_PERMISSIONS_URL, $user->getId()));

        $this->successOrException($response);

        $responseBody = $response->toArray();

        return $this->serializer->denormalize($responseBody, UserContract::class . '[]');
    }

    /**
     * @param ResponseInterface $response
     *
     * @return void
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    protected function successOrException(ResponseInterface $response): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new \Exception(json_encode($response->toArray(false)));
        }
    }
}
