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
        $view = $di->get('view');
        $request = $di['request'];

        if ($request->isAjax()) {
            $view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_ACTION_VIEW);
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

        $return = ['code'=>$response->getStatusCode()];
        $return['data'] = $data;

        $response->setContentType('application/json', 'UTF-8');
        $response->setContent(json_encode($return));
        
        // $response->sendHeaders();
        // echo $response->getContent();
        // die();
    }


    public function beforeException($event, $dispatcher, $exception) {
        // var_dump($exception);
        $di = $this->getDI();
        $response = $di['response'];

        // Default error action
        $action = "show503";
        
        $response->setStatusCode(503, "Service1 Unavailable");
        // 处理404异常
        if ($exception instanceof DispatchException) {
            $action = "show404";
            // $response->setHeader(404, 'Not Found');
            $response->setStatusCode(404, "Not Found");

            // $dispatcher->forward(
            //     [
            //         "controller" => "index",
            //         "action"     => $action,
            //     ]
            // );
            // return false;

        }

        $dispatcher->forward(
                [
                    "controller" => "index",
                    "action"     => $action,
                ]
            );
       
        return false;
    }


}

