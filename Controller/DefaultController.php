<?php

namespace iLubenets\DIArchitectBundle\Controller;

use iLubenets\DIArchitectBundle\DependencyInjection\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function diAction($bundle_name)
    {
        $pathToJson = $this->container->getParameter(Configuration::BUNDLE.'.path_to_save_json');
        $pathToJson = str_replace('web/', '', $pathToJson);
        return $this->render(
            'iLubenetsDIArchitectBundle:Charts:code_structure.html.twig',
            ['path_to_data_json' => "/$pathToJson/$bundle_name.json"],
            new Response(null, Response::HTTP_OK, ['content-type' => 'text/html'])
        );
    }
}
