<?php
namespace Plugins;
use Phalcon\Dispatcher;
use Phalcon\Http\Request;
use Phalcon\Mvc\User\Component;
use Phalcon\Logger\Adapter\File as FileAdapter;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Mvc\Dispatcher\Exception as DispatchException;


class AutoResponse extends Component
{

    public function beforeSendResponse($event,$dispatcher){
        $di = $this->getDI();
        $view = $di['view'];
        
        $request = $di['request'];
        if ($request->isAjax()) {
            $view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_LAYOUT);
        }

        $acceptable = $request->getAcceptableContent();
        $isJson = false;
        foreach ($acceptable as $v) {
            if ($v['accept']=='application/json') {
                $view->disable();
                $isJson = true;
                break;
            }
        }

        if ($isJson) {
            $this->output($view->getParamsToView());
        }

        return true;
    }


    private function output($data){
        $di = $this->getDI();
        $response = $di['response'];

        $return = ['code'=>200];
        $return['data'] = $data;

        $response->setContentType('application/json', 'UTF-8');
        $response->setContent(json_encode($return));
        
        // $response->sendHeaders();
        // echo $response->getContent();
        // die();
    }
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

