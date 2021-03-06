<?php
/**
 * Offer controller.
 */
namespace Controller;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Repository\OfferRepository;
use Form\OfferType;
/**
 * Class OfferController.
 *
 * @package Controller
 */
class OfferController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('offer_index');
        $controller->get('/myoffers', [$this, 'displayUsersOffersAction'])
            ->bind('my_offers');
        $controller->get('/myoffers/page/{page}', [$this, 'displayUsersOffersAction'])
            ->value('page', 1)
            ->bind('my_offers_paginated');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('params', '')
            ->value('page', 1)
            ->bind('offer_index_paginated');
        $controller->get('price/page/{page}', [$this, 'IndexSortedByPriceAction'])
            ->value('page', 1)
            ->bind('offer_sorted_price');
        $controller->get('/{id}', [$this, 'viewAction'])
            ->bind('offer_view')
            ->assert('id', '[0-9]\d*');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('offer_add');
        $controller->match('/{id}/edit', [$this, 'editAction'])
            ->method('POST|GET')
            ->assert('id', '[0-9]\d*')
            ->bind('offer_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[0-9]\d*')
            ->bind('offer_delete');
        $controller->get('/sort/{param}', [$this, 'displayOffersSorted'])
            ->bind('offers_sorted');



        return $controller;
    }

    /**
     * Index action.
     *
     * @param \Silex\Application $app Silex application
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function indexAction(Application $app, $page = 1)
    {
        if($app['session']->get('param') !== 'created_at')
        {
            $app['session']->set('param', 'created_at');
        }
        $params = $app['session']->get('param');
        $offerModel = new OfferRepository($app['db']);
        return $app['twig']->render(
            'offer/index.html.twig',
            ['paginator' => $offerModel->findAllPaginated($page, 'offers', $params),
             'route_name' => 'offer_index_paginated']
        );
    }

    public function indexSortedByPriceAction(Application $app, $page = 1)
    {
        if($app['session']->get('param') !== 'price')
        {
            $app['session']->set('param', 'price');
        }

        $params = $app['session']->get('param');
        var_Dump($params);
        $offerModel = new OfferRepository($app['db']);
        return $app['twig']->render(
            'offer/index.html.twig',
            ['paginator' => $offerModel->findAllPaginated($page, 'offers', $params),
             'route_name' => 'offer_sorted_price']

        );
    }
    /**
     * View action.
     *
     * @param \Silex\Application $app Silex application
     * @param string             $id  Element Id
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function viewAction(Application $app, Request $request)
    {
        $offerModel = new OfferRepository($app['db']);

        $id = $request->get('id');

        return $app['twig']->render(
            'offer/view.html.twig',
            ['offer' => $offerModel->findOneById($id, 'offers'),
                'id' => $id]
        );
    }

    /**
     * Add action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function addAction(Application $app, Request $request)
    {
        $offer = [];

        $form = $app['form.factory']->createBuilder(
            OfferType::class,
            $offer,
            ['type_repository' => new OfferRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);
        $userLogin = $this->getUserLogin($app);

         if($form->isSubmitted() && $form->isValid()) {
            $offerRepository = new OfferRepository($app['db']);
            $offerRepository->save($form->getData(), $userLogin);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('my_offers_paginated'), 301);

        }

        return $app['twig']->render(
            'offer/add.html.twig',
            [
                'offer' => $offer,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Edit action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function editAction(Application $app, $id, Request $request)
    {
        $offerRepository = new OfferRepository($app['db']);
        $offer = $offerRepository->findOneById($id, 'offers');
        if (!$offer) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'messages.record_not_found',
                ]
            );

            return $app->redirect($app['url_generator']->generate('offer_index'));
        }

        $form = $app['form.factory']->createBuilder(
            OfferType::class,
            $offer,
            ['type_repository' => new OfferRepository($app['db'])]
        )->getForm();
        $form->handleRequest($request);

        $userLogin = $this->getUserLogin($app);
        if ($form->isSubmitted() && $form->isValid()) {
            $offerRepository->save($form->getData(), $userLogin);

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_edited',
                ]
            );

            return $app->redirect($app['url_generator']->generate('offer_view', array('id' => $id)), 301);
        }

        return $app['twig']->render(
            'offer/edit.html.twig',
            [
                'offer' => $offer,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Delete action.
     *
     * @param \Silex\Application                        $app     Silex application
     * @param int                                       $id      Record id
     * @param \Symfony\Component\HttpFoundation\Request $request HTTP Request
     *
     * @return \Symfony\Component\HttpFoundation\Response HTTP Response
     */
    public function deleteAction(Application $app, $id, Request $request)
    {
        $offerRepository = new OfferRepository($app['db']);
        $offer = $offerRepository->findOneById($id, 'offers');

        if(!$offer) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found'
                ]
            );

            return $app->redirect($app['url_generator']->generate('offer_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $offer)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $offerRepository->delete($form->getData(), 'offers');
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('my_offers_paginated'),
                301
            );
        }

        return $app['twig']->render(
            'offer/delete.html.twig',
            [
                'offer' => $offer,
                'form' => $form->createView(),
            ]
        );
    }

    public function displayUsersOffersAction(Application $app, $page = 1)
    {
        $userLogin = $this->getUserLogin($app);
        $offerModel = new OfferRepository($app['db']);
        $usersOffers = $offerModel->findOffersByUserLoginPaginated($userLogin, $page);
       // var_dump($usersOffers);
        return $app['twig']->render(

            'offer/myoffers.html.twig',
            ['paginator' => $usersOffers]
        );
    }

    protected function getUserLogin(Application $app)
    {
        $token = $app['security.token_storage']->getToken();
        if (null !== $token) {
            $user = $token->getUser();
            $userLogin = $user->getUsername();
        }

        return $userLogin;
    }


}