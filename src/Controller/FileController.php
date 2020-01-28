<?php

namespace App\Controller;

use App\Entity\File;
use App\Service\FileSystemApi;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class FileController extends AbstractController
{
    /**
     * @Route("/show/{id}", name="show")
     */
    public function show(string $id) {
        $em = $this->getDoctrine()->getManager();
        $file = $em->getRepository(File::class)->find($id);
        if ($file):
            return new BinaryFileResponse($file->getPath());
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/download/{id}", name="download")
     */
    public function download(string $id) {
        $em = $this->getDoctrine()->getManager();
        $file = $em->getRepository(File::class)->find($id);
        if ($file):
            $response = new BinaryFileResponse($file->getPath());
            $response->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $file->getName()
            );
            return $response;
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }

    /**
     * @Route("/remove/{id}", name="remove")
     */
    public function remove(string $id) {
        $em = $this->getDoctrine()->getManager();
        $file = $em->getRepository(File::class)->find($id);
        if ($file):
            $fileSystem = new FileSystemApi();
            // Remove to database
            $em->remove($file);
            $em->flush();
            // Remove file
            $fileSystem->remove($file->getPath());
            // Response
            return $this->json([
                'status' => 'success',
                'message' => '['.$id.'] File was removed.',
            ]);
        endif;
        return $this->json([
            'status' => 'error',
            'message' => 'File not found.',
        ]);
    }
}