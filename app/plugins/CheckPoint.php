<?php
namespace Plugins;
use Phalcon\Dispatcher;
use Phalcon\Http\Request;
use Phalcon\Mvc\User\Component;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;


class CheckPoint extends Component
{



    // public function beforeException($event, $dispatcher, $exception) {
    //     if ($event instanceof DispatchException) {
    //         $dispatcher->forward(
    //             array(
    //                 'controller' => 'index',
    //                 'action'     => 'show404'
    //             )
    //         );

    //         return false;
    //     }

    //     switch ($exception->getCode()) {
    //         case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
    //         case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
    //             $dispatcher->forward(
    //                 array(
    //                     'controller' => 'index',
    //                     'action'     => 'show404'
    //                 )
    //             );

    //             return false;
    //     }
    // }


}

