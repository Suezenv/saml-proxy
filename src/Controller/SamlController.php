<?php


namespace App\Controller;

use EightPoints\Bundle\GuzzleBundle\Log\Logger;
use EightPoints\Bundle\GuzzleBundle\Log\LoggerInterface;
use Psr\Log\LoggerInterface as LogLoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SamlController extends AbstractController
{
    /**
     * @Route("/saml/is-logged-in")
     */
    public function isAuthenticated(LogLoggerInterface $logger)
    {
        if (!$this->getUser()) {
            $logger->error('Is logged in failed because user is not authenticated.');
            return new JsonResponse([
                'code' => 'USER_NOT_AUTHENTICATED',
                'user' => null
            ], 403);
        }

        return new JsonResponse([
            'code' => 'USER_AUTHENTICATED',
            'user' => $this->get('serializer')->normalize($this->getUser())
        ]);
    }

    /**
     * @Route("/saml/redirect")
     */
    public function afterLoginRedirect()
    {
        $this->denyAccessUnlessGranted($this->getParameter('backend.user_role'));

        $redirectUrl = $this->getParameter('saml.default_target_path');
        if (!$redirectUrl) {
            throw new \LogicException('No default target path setup has been configured in project');
        }

        return $this->redirect($redirectUrl);
    }
}