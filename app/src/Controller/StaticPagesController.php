<?php
/**
 * Offer controller.
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Form\FindOfferType;
use Repository\OfferRepository;
/**
 * Class OfferController.
 *
 * @package Controller
 */
class StaticPagesController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->match('/', [$this, 'findMatchingAction'])
            ->method('POST|GET')
            ->bind('homepage');
        $controller->get('/page/{page}', [$this, 'displayMatchingAction'])
            ->value('page', 1)
            ->bind('matching_offers_paginated');


        return $controller;
    }

    public function findMatchingAction(Application $app, Request $request, $page = 1)
    {
        $offer = [];
        $form = $app['form.factory']->createBuilder(
            FindOfferType::class,
            $offer,
            ['type_repository' => new OfferRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $offerRepository = new OfferRepository($app['db']);

            $offers = $offerRepository->findMatchingOffersPaginated($form->getData(), 'offers', $page);

            return $app['twig']->render(
                'staticPages/index.html.twig',
                [
                    'paginator' => $offers,
                ]
            );
        }

        return $app['twig']->render(
            'staticPages/homepage.html.twig',
            [
                'offer' => $offer,
                'form' => $form->createView(),
            ]
        );
    }

    public function displayMatchingAction($offers)
    {
        return $app['twig']->render(
            'staticPages/index.html.twig',
            [
                'paginator' => $offers,
            ]
        );
    }


}