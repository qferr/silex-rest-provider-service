<?php
namespace QFerrer\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RestServiceProvider
 */
class RestServiceProvider implements ServiceProviderInterface
{

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        // handling CORS preflight request
        $app->before(function (Request $request) {
            if ($request->getMethod() === 'OPTIONS') {
                $response = new Response();
                $response->headers->set('Access-Control-Allow-Methods', 'GET,POST,PUT,DELETE,OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');
                $response->headers->set('Access-Control-Allow-Origin', '*');
                $response->setStatusCode(200);

                return $response;
            }
        }, $app::EARLY_EVENT);

        $app->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : []);
            }
        });

        // CORS domain
        $app->after(function (Request $request, Response $response) {
            $response->headers->set('Access-Control-Allow-Origin', '*');

            return $response;
        });

        // Returns the status code in the response body
        $app->after(function (Request $request, Response $response) {
            $status = $response->getStatusCode();

            // Errors
            if ($status >= 400 && $response instanceof JsonResponse) {
                $data = json_decode($response->getContent(), true);

                if (!is_array($data)) {
                    $data = [];
                }

                $response->setData(array_merge($data, ['status' => $status]));
            }

            return $response;
        });

        // Converts HTTP exception to response
        $app->error(function (\Exception $e) {
            $response = null;

            switch(true) {
                case $e instanceof NotFoundHttpException:
                case $e instanceof BadRequestHttpException:
                    $response = new JsonResponse(['message' => $e->getMessage()], $e->getStatusCode(), $e->getHeaders());
                    break;
                default:
            }

            return $response;
        });
    }
}
