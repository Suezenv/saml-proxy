<?php


namespace App\Security\Heimdall;

use App\HttpClient\HeimdallClient;
use App\Security\Heimdall\Model\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var HeimdallClient
     */
    protected $heimdall;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $samlGroupAttribute;

    /**
     * UserProvider constructor.
     * @param HeimdallClient $heimdall
     * @param LoggerInterface|null $logger
     * @param string $samlGroupAttribute
     */
    public function __construct(HeimdallClient $heimdall, LoggerInterface $logger, string $samlGroupAttribute)
    {
        $this->heimdall = $heimdall;
        $this->logger = $logger;
        $this->samlGroupAttribute = $samlGroupAttribute;
    }

    /**
     * @inheritDoc
     */
    public function loadUserByUsername($username)
    {
        $this->logger->info("try to getUser by username: '" . $username . "'");
        try {
            $user = $this->heimdall->getUserByLdapUidNumber($username);
        } catch (\Exception $e) {
            $this->logger->warning(sprintf('Get User by LdapUidNumber %s from session not found. Origin : %s', $username, $e->getMessage()));
        }
        if (!$user) {
            try {
                $user = $this->heimdall->getUserByUsername($username);
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Get user By Username  %s from session not found. Origin : %s', $username, $e->getMessage()));
                throw $e;
            }
        }
        if (!$user) {
            $this->logger->error(sprintf('User %s not found', $username));
            throw new UsernameNotFoundException(sprintf('User %s not found', $username));
        }
        try {
            $userContracts = $this->heimdall->getUserContracts($user);
            $user->setUserContracts($userContracts);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Exception occurred when loading user %s permissions : %s', $username, $e->getMessage())
            );
            throw $e;
        }

        $user->setSamlGroupAttribute($this->samlGroupAttribute);

        return $user;
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user)
    {
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
