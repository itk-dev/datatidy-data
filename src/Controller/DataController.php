<?php

/*
 * This file is part of itk-dev/datatidy-data.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Controller;

use App\Data\DataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DataController extends AbstractController
{
    /** @var DataService */
    private $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * @Route("/")
     */
    public function index()
    {
        $index = $this->dataService->getIndex();

        $index = array_map(function ($item) {
            $item['urls'] = [
                'data' => $this->generateUrl('data', ['path' => $item['filename']], UrlGeneratorInterface::ABSOLUTE_URL),
                'basic auth' => $this->generateUrl('data_auth', ['path' => $item['filename']], UrlGeneratorInterface::ABSOLUTE_URL),
            ];
            unset($item['filename']);

            return $item;
        }, $index);

        return new JsonResponse($index);
    }

    /**
     * @Route("/auth/{path}", name="data_auth", requirements={"path": ".+"})
     */
    public function auth($path)
    {
        return $this->data($path);
    }

    /**
     * @Route("/{path}", name="data", requirements={"path": ".+"})
     */
    public function data($path)
    {
        [$filename, $contentType] = $this->dataService->get($path);

        return new BinaryFileResponse($filename, 200, ['content-type' => $contentType]);
    }
}
