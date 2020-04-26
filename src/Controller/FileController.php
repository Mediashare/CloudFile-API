<?php

namespace App\Controller;

use App\Entity\File;
use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileController extends AbstractController
{
    public function __construct() {
        $this->response = new Response();
    }

    /**
     * @Route("/list/{page}", name="list")
     */
    public function list(Request $request, ?int $page = 1) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
           
        // Get Files
        $fileSystem = new FileSystemApi();
        $result = $fileSystem->getFiles($em, $apikey, $page);
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);

        $files = $result['files'];
        $results = [];
        $size = 0;
        foreach ($files as $file) {
            $results[] = $file->getInfo();;
            $size += $file->getSize();
        }

        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo(),
            'files' => [
                'page' => $page,
                'counter' => $result['counter'],
                'size' => $fileSystem->getSizeReadable($size),
                'results' => $results
            ],
        ]);
    }

    /**
     * @Route("/info/{id}", name="info")
     */
    public function info(Request $request, string $id) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;

        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $em);
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;
        
        return $this->response->send($file->getInfo());
    }

    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(Request $request, string $id) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $this->getDoctrine()->getManager());
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;

        $response = new BinaryFileResponse($file->getPath(), 200);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(Request $request, string $id) {
        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $this->getDoctrine()->getManager());
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;

        $response = new BinaryFileResponse($file->getPath());
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getName()
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    /**
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(Request $request, string $id) {
        $fileSystem = new FileSystemApi();
        $file = $fileSystem->getFile($request, $id, $this->getDoctrine()->getManager());
        if (!$file):
            return $this->response->send([
                'status' => 'error',
                'message' => 'File not found.',
            ], 404);
        endif;
        
        // Remove to database
        $em = $this->getDoctrine()->getManager();
        $em->remove($file);
        $em->flush();
        // Remove file stockage
        $fileSystem->remove($file->getStockage());
        // Response
        return $this->response->send([
            'status' => 'success',
            'message' => '['.$id.'] File was removed.',
        ]);
    }
}
