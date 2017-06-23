<?php
/**
 * Offer controller.
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $controller->get('/', [$this, 'findMatchingAction'])
            ->bind('homepage');
        $controller->get('/page/{page}/{params}', [$this, 'displayMatchingAction'])
            ->value('params', '')
            ->value('page', 1)
            ->bind('matching_offers_paginated');


        return $controller;
    }

    public function findMatchingAction(Application $app, Request $request)
    {
        $app['session']->remove('form');
        $offer = [];
        $form = $app['form.factory']->createBuilder(
            FindOfferType::class,
            $offer,
            ['type_repository' => new OfferRepository($app['db'])]
        )->getForm();

            return $app['twig']->render(
            'staticPages/homepage.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

   public function displayMatchingAction(Application $app, Request $request, $page = 1)
   {
       if(!$app['session']->get('form')) {
           $form = $request->get('offer_type');
           $app['session']->set('form', $form);
       }
       $match = $app['session']->get('form');

       $offerRepository = new OfferRepository($app['db']);

           $offers = $offerRepository->getMatching($match, 'offers');

           $paginator = $offerRepository->paginateMatchingOffers($offers, $page);


           return $app['twig']->render(
               'staticPages/index.html.twig',
               [
                   'paginator' => $paginator,

               ]
           );
       }


}