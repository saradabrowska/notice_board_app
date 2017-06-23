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
use Repository\UserRepository;
use Form\SignUpType;
use Form\UserType;

/**
 * Class OfferController.
 *
 * @package Controller
 */
class UserController implements ControllerProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function connect(Application $app)
    {
        $controller = $app['controllers_factory'];
        $controller->get('/', [$this, 'indexAction'])->bind('user_index');
        $controller->get('/page/{page}', [$this, 'indexAction'])
            ->value('page', 1)
            ->bind('user_index_paginated');
        $controller->match('/add', [$this, 'addAction'])
            ->method('POST|GET')
            ->bind('user_add');
        $controller->get('/{name}', [$this, 'viewAction'])
            ->bind('user_view');
           // ->assert('id', '[0-9]\d*');
        $controller->match('/{login}/edit', [$this, 'editAction'])
            ->method('POST|GET')
            ->assert('id', '[0-9]\d*')
            ->bind('user_edit');
        $controller->match('/{id}/delete', [$this, 'deleteAction'])
            ->method('GET|POST')
            ->assert('id', '[0-9]\d*')
            ->bind('user_delete');



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

        $offerModel = new UserRepository($app['db']);
            return $app['twig']->render(
            'user/homepage.html.twig',
            ['paginator' => $offerModel->findAllPaginated($page, 'users')]

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
        $model = new UserRepository($app['db']);

        $id = $request->get('name');

        return $app['twig']->render(
            'user/view.html.twig',
            ['offer' => $model->findOneById($id),
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
        $user = [];

        $form = $app['form.factory']->createBuilder(
            SignUpType::class,
            $user
        )->getForm();
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $userRepository = new UserRepository($app['db']);
            $userRepository->save($form->getData());

            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_added',
                ]
            );

            return $app->redirect($app['url_generator']->generate('offer_index'), 301);

        }

        return $app['twig']->render(
            'user/add.html.twig',
            [
                'user' => $user,
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
    public function editAction(Application $app, $login, Request $request)
     {
         $userRepository = new UserRepository($app['db']);
         $user= $userRepository->getUserByLogin($login);
         if (!$user) {
             $app['session']->getFlashBag()->add(
                 'messages',
                 [
                     'type' => 'warning',
                     'message' => 'messages.record_not_found',
                 ]
             );

             return $app->redirect($app['url_generator']->generate('offer_index'));
         }
         $userLogin = $user['login'];
        // var_dump($userId);
        $userData = $userRepository->findOneById($userLogin);
        $userId = $userData['id'];
         $form = $app['form.factory']->createBuilder(
             UserType::class,
             $userData
         )->getForm();
         $form->handleRequest($request);

         if ($form->isSubmitted() && $form->isValid()) {
             $userRepository->save($form->getData(), $userId);

             $app['session']->getFlashBag()->add(
                 'messages',
                 [
                     'type' => 'success',
                     'message' => 'message.element_successfully_edited',
                 ]
             );

             return $app->redirect($app['url_generator']->generate('offer_index'), 301);
         }

         return $app['twig']->render(
             'user/edit.html.twig',
             [
                 'user' => $user,
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
        $repository = new UserRepository($app['db']);
        $object = $repository->findOneById($id);

        if(!$object) {
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'warning',
                    'message' => 'message.record_not_found'
                ]
            );

            return $app->redirect($app['url_generator']->generate('user_index'));
        }

        $form = $app['form.factory']->createBuilder(FormType::class, $object)->add('id', HiddenType::class)->getForm();
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $repository->delete($form->getData(), 'users');
            $app['session']->getFlashBag()->add(
                'messages',
                [
                    'type' => 'success',
                    'message' => 'message.element_successfully_deleted',
                ]
            );

            return $app->redirect(
                $app['url_generator']->generate('user_index'),
                301
            );
        }

        return $app['twig']->render(
            'user/delete.html.twig',
            [
                'user' => $object,
                'form' => $form->createView(),
            ]
        );
    }
}