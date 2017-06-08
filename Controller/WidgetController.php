<?php

namespace Tellaw\SunshineAdminBundle\Controller;

use Doctrine\DBAL\Types\JsonArrayType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Tellaw\SunshineAdminBundle\Service\EntityService;
use Tellaw\SunshineAdminBundle\Service\PageService;

class WidgetController extends Controller
{
    /**
     * List entity in a dataTable ajax loaded bloc
     *
     * @Route("/app/widget/crudlist/{pageName}/{row}/{widgetName}", name="sunshine_widget_crudlist")
     * @Method({"GET"})
     * @param Request $request
     * @param $pageName
     * @param $row
     * @param $widgetName
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function widgetCrudListAction(Request $request, $pageName, $row, $widgetName)
    {

        /** @var PageService $pageService */
        $pageService = $this->get("sunshine.pages");
        $pageConfiguration = $pageService->getPageConfiguration($pageName);

        if (!isset($pageConfiguration["rows"][$row][$widgetName]["parameters"]["entityName"])) {
            throw new \Exception("entityName parameter should be configured for widget ".$widgetName." in row : ".$row);
        }
        $entityName = $pageConfiguration["rows"][$row][$widgetName]["parameters"]["entityName"];

        /** @var EntityService $entities */
        $entities = $this->get("sunshine.entities");
        $listConfiguration = $entities->getListConfiguration($entityName);

        return $this->render(
            'TellawSunshineAdminBundle:Widget:ajax-datatable.html.twig',
            array(
                "fields" => $listConfiguration,
                "row" => $row,
                "widgetName" => $widgetName,
                "pageName" => $pageName,
                "entityName" => $entityName,
                "widget" => $pageConfiguration["rows"][$row][$widgetName],
            )
        );
    }

    /**
     * Shows entity
     *
     * @Route("/app/widget/show/{pageName}/{row}/{widgetName}", name="sunshine_widget_view")
     * @Method("GET")
     * @param $pageName
     * @param $row
     * @param $widgetName
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     * @internal param Request $request
     */
    public function viewAction($pageName, $row, $widgetName)
    {
        /** @var PageService $pageService */
        $pageService = $this->get("sunshine.pages");
        $pageConfiguration = $pageService->getPageConfiguration($pageName);

        if (!isset($pageConfiguration["rows"][$row][$widgetName]["parameters"]["entityName"])) {
            throw new \Exception("entityName parameter should be configured for widget ".$widgetName." in row : ".$row);
        }

        $entityName = $pageConfiguration["rows"][$row][$widgetName]["parameters"]["entityName"];
        $id = $pageConfiguration["rows"][$row][$widgetName]["parameters"]["id"];

        /** @var EntityService $entities */
        $entities = $this->get("sunshine.entities");
        $configuration = $entities->getFormConfiguration($entityName);

        /** @var CrudService $entities */
        $crudService = $this->get("sunshine.crud_service");
        $entity = $crudService->getEntity($entityName, $id);

        return $this->render(
            'TellawSunshineAdminBundle:Widget:view.html.twig',
            [
                "fields" => $configuration,
                "widgetName" => $widgetName,
                "pageName" => $pageName,
                "entityName" => $entityName,
                "widget" => $pageConfiguration["rows"][$row][$widgetName],
                "entity" => $entity,
            ]
        );
    }

    /**
     * Shows entity
     *
     * @Route("/app/widget/edit/{pageName}/{row}/{widgetName}/{objId}", name="sunshine_widget_edit")
     * @Method("GET")
     * @param Request $request
     * @param $pageName
     * @param $row
     * @param $widgetName
     * @param string $objId
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function editAction(Request $request, $pageName, $row, $widgetName, $objId = "")
    {
        $formTypeClass = [
            'array' => TextareaType::class,
            'bigint' => TextType::class,
            'boolean' => TextType::class,
            'date' => TextType::class,
            'datetime' => TextType::class,
            'datetimetz' => TextType::class,
            'email' => TextType::class,
            'float' => TextType::class,
            'guid' => TextType::class,
            'id' => TextType::class,
            'image' => TextType::class,
            'integer' => TextType::class,
            'json_array' => JsonArrayType::class,
            'object' => TextType::class,
            'raw' => TextType::class,
            'simple_array' => TextType::class,
            'smallint' => TextType::class,
            'string' => TextType::class,
            'tel' => TextType::class,
            'text' => TextType::class,
            'time' => TextType::class,
            'toggle' => TextType::class,
            'url' => TextType::class,
        ];

        /** @var PageService $pageService */
        $pageService = $this->get("sunshine.pages");
        $pageConfiguration = $pageService->getPageConfiguration($pageName);

        if (!isset($pageConfiguration["rows"][$row][$widgetName]["parameters"]["entityName"])) {
            throw new \Exception("entityName parameter should be configured for widget ".$widgetName." in row : ".$row);
        }

        $entityName = $pageConfiguration["rows"][$row][$widgetName]["parameters"]["entityName"];
        $id = $pageConfiguration["rows"][$row][$widgetName]["parameters"]["id"];

        /** @var EntityService $entities */
        $entities = $this->get("sunshine.entities");
        $configuration = $entities->getFormConfiguration($entityName);

        /** @var CrudService $entities */
        $crudService = $this->get("sunshine.crud_service");
        $entity = $crudService->getEntity($entityName, $id);

        $formBuilder = $this->get('form.factory')->createBuilder();
        foreach ($configuration as $field) {
            if (!in_array($field['type'], ['id'])) {
                $formBuilder->add($field['label'], $formTypeClass[$field['type']]);
            }
        }


        $formBuilder->add('Enregistrer', SubmitType::class);
        $form = $formBuilder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        }

        return $this->render(
            'TellawSunshineAdminBundle:Widget:edit.html.twig',
            [
                "form" => $form->createView(),
                "fields" => $configuration,
                "widgetName" => $widgetName,
                "pageName" => $pageName,
                "entityName" => $entityName,
                "widget" => $pageConfiguration["rows"][$row][$widgetName],
                "entity" => $entity,
            ]
        );

    }

}
