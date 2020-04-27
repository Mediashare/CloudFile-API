<?php

namespace App\Controller;

use App\Entity\Volume;
use App\Service\Response;
use App\Service\FileSystemApi;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VolumeController extends AbstractController
{
    public function __construct() {
        $this->response = new Response();
    }

    /**
     * @Route("/volume/new", name="volume_new")
     */
    public function new(Request $request) {
        if ($this->getParameter('volume_password') 
            && $request->get('volume_password') !== $this->getParameter('volume_password')):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'Authority not valid for volume creation.'
                ]);
        endif;
        
        $volume = new Volume();
        $volume->setEmail($request->get('email')); // Email association
        $volume->setSize($request->get('size')); // Gb

        $em = $this->getDoctrine()->getManager();
        $em->persist($volume);
        $em->flush();

        return $this->response->send([
            'status' => 'success',
            'volume' => [
                'id' => $volume->getId(),
                'email' => $volume->getEmail(),
                'apikey' => $volume->getApiKey(),
                'size' => $volume->getSize(),
            ]
        ]);
    }

    /**
     * @Route("/volume/info", name="volume_info")
     */
    public function info(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        return $this->response->send($volume->getInfo());
    }

    /**
     * @Route("/volumes/retrieve", name="volumes_retrieve")
     */
    public function retrieve(Request $request) {
        if ($this->getParameter('volume_password') 
            && $request->get('volume_password') !== $this->getParameter('volume_password')):
                return $this->response->send([
                    'status' => 'error',
                    'message' => 'Authority not valid for volume creation.'
                ]);
        endif;
        
        $email = $request->get('email');
        $em = $this->getDoctrine()->getManager();
        $volumes = $em->getRepository(Volume::class)->findBy(['email' => $email]);

        $results = [];
        foreach ($volumes as $volume):
            $results[] = $volume->getInfo();
        endforeach;

        return $this->response->send([
            'status' => 'success',
            'results' => $results
        ]);
    }

    /**
     * @Route("/volume/clear", name="volume_clear")
     */
    public function clear(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;

        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        // Remove file(s)
        $fileSystem = new FileSystemApi();
        foreach ($volume->getFiles() as $file):
            // Remove to database
            $em->remove($file);
            $em->flush();
            // Remove file stockage
            $fileSystem->remove($file->getStockage());
        endforeach;
        
        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();

        return $this->response->send([
            'status' => 'success',
            'message' => 'All files from volume ['.$volume->getId().'] was deleted.'
        ]);
    }

    /**
     * @Route("/volume/generate/apikey", name="volume_generate_apikey")
     */
    public function generateApiKey(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        $volume->generateApiKey();

        foreach ($volume->getFiles() as $file):
            $file->setApiKey($volume->getApiKey());
        endforeach;

        $volume->setUpdateDate(new \DateTime());
        $em->persist($volume);
        $em->flush();
        
        return $this->response->send([
            'status' => 'success',
            'volume' => $volume->getInfo()
        ]);
    }

    /**
     * @Route("/volume/delete", name="volume_delete")
     */
    public function delete(Request $request) {
        // Check Authority
        $apikey = $request->headers->get('apikey');
        $authority = $this->response->checkAuthority($em = $this->getDoctrine()->getManager(), $apikey);
        if ($authority):
            return $authority;
        endif;
        
        $volume = $em->getRepository(Volume::class)->findOneBy(['apikey' => $apikey, 'online' => true]);
        // Remove file(s)
        $fileSystem = new FileSystemApi();
        foreach ($volume->getFiles() as $file) {    
            // Remove to database
            $em->remove($file);
            $em->flush();
            // Remove file stockage
            $fileSystem->remove($file->getStockage());
        }
        // Delete Volume
        $em->remove($volume);
        $em->flush();
        
        return $this->response->send([
            'status' => 'success',
            'message' => 'Volume  ['.$volume->getId().'] was deleted.'
        ]);
    }
}
