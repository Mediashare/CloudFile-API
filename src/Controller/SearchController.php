<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SearchController extends AbstractController
{
    public function __construct() {
        $this->response = new Response();
    }

    /**
     * Search file(s)
     * @Route("/search", name="search")
     */
    public function search(Request $request) {
        $queries = $this->getRealInput('GET');
        if (!$queries && $request->getContent()):
            $queries = \json_decode($request->getContent(), true);
        elseif (!$queries):
            $queries = $request->query->all();
        endif;

        // Check Authority
        $em = $this->getDoctrine()->getManager();
        $apikey = $request->headers->get('apikey');
        if ($apikey):
            $authority = $this->checkAuthority($apikey);
            if ($authority):
                return $authority;
            endif;
            $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey]);
            $files = $em->getRepository(File::class)->search($queries, $volume);
        else:
            $files = $em->getRepository(File::class)->search($queries);
        endif;

        // Response
        $fileSystem = new FileSystemApi();
        return $this->response->send([
            'status' => 'success',
            'queries' => $queries,
            'files' => [
                'counter' => count($files['results']),
                'size' => $fileSystem->getSizeReadable($files['size']),
                'results' => $files['results']
            ],
        ]);
    }

    private function getRealInput($source) {
        $pairs = explode("&", $source == 'POST' ? file_get_contents("php://input") : $_SERVER['QUERY_STRING']);
        $vars = array();
        foreach ($pairs as $pair) {
            $name = null;
            $value = null;
            $nv = explode("=", $pair);
            $name = trim(urldecode($nv[0]));
            if (isset($nv[1])):
                $value = trim(urldecode($nv[1]));
            endif;
            if ($name || $value):
                $vars[$name] = $value ?? null;
            endif;
        }
        return $vars;
    }

    /**
     * Check if ApiKey exist & if Volume associated.
     *
     * @param Request $request
     * @return Response|null
     */
    private function checkAuthority($apikey) {
        if (!$apikey):
            return $this->response->send([
                'status' => 'error',
                'message' => 'ApiKey not found in Header.'
            ]);
        endif;
        $em = $this->getDoctrine()->getManager();
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey]);
        if (!$volume):
            return $this->response->send([
                'status' => 'error',
                'message' => 'Volume not found with your apikey.'
            ]);
        endif;
        
        return null; // Checkup valid!
    }
}
