<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;


class BackendController extends AbstractController
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var LoggerInterface
     */
    public  $logger;

    /**
     * @var string
     */
    public  $requestExecutionTimeout;

    /**
     * BackendController constructor.
     *
     * @param HttpClientInterface $backendClient
     * @param array $backendHeaders
     */
    public function __construct(HttpClientInterface $backendClient, array $backendHeaders, LoggerInterface $logger, string $requestExecutionTimeout)
    {
        $this->client = $backendClient;
        $this->headers = $backendHeaders;
        $this->logger = $logger;
        $this->requestExecutionTimeout = $requestExecutionTimeout;
    }

    /**
     * @Route("/{path}", requirements={"path"="^(?!saml/)(.*)?$"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $this->denyAccessUnlessGranted($this->getParameter('backend.user_role'));

        return $this->callBackend($request);
    }
    
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function callBackend(Request $request): Response
    {
        try {
            $options = [
                'headers' => $this->headers,
                'timeout' => $this->requestExecutionTimeout,
            ];
        
            // Check if the request has files
            if ($request->files->count() > 0) {
                $formFields = [];
        
                // Add files to the form fields
                foreach ($request->files as $name => $file) {
                    $formFields[] = new DataPart(
                        fopen($file->getPathname(), 'r'),
                        $file->getClientOriginalName(),
                        $file->getMimeType()
                    );
                }
        
                // Add other form parameters
                foreach ($request->request->all() as $name => $value) {
                    $formFields[] = ['name' => $name, 'value' => $value];
                }
        
                $formData = new FormDataPart($formFields);
        
                $options['body'] = $formData->bodyToIterable();
                $options['headers'] += $formData->getPreparedHeaders()->toArray();
            } else {
                // If no files, forward the body content as is
                $options['body'] = $request->getContent();
            }
        
            $response = $this->client->request(
                $request->getMethod(),
                $request->getRequestUri(),
                $options
            );
        
            $headers = $response->getHeaders(false);
            unset($headers['transfer-encoding']); // prevent chunk error in proxy
        
            return new StreamedResponse(
                function () use ($response) {
                    $outputStream = fopen('php://output', 'wb');
                    foreach ($this->client->stream($response) as $chunk) {
                        fwrite($outputStream, $chunk->getContent());
                        flush();
                    }
                    fclose($outputStream);
                },
                $response->getStatusCode(),
                $headers
            );
        }
        catch(\Exception $exception) {
            $this->logger->error(
                "An error occured while importing user data",
                ["message" => $exception->getMessage()]
            );
            return new JsonResponse($exception->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}