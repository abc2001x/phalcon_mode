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
    //event at application:boot
    public function boot($event,$application){
        $di = $this->getDI();
        $view = $di->get('view');
        $request = $di['request'];

        if ($request->isAjax()) {
            $view->setRenderLevel(\Phalcon\Mvc\View::LEVEL_LAYOUT);

        }

        $acceptable = $request->getAcceptableContent();

        $isJson = false;
        foreach ($acceptable as $v) {
            if ($v['accept']=='application/json') {
                $view->disable();
                $application->useImplicitView(false);//不编译view
                $this->isJson = $isJson = true;
                break;
            }
        }

        return true;
    }
    //event at application:beforeSendResponse
    public function beforeSendResponse($event,$application,$response){

        if ($this->isJson) {
            // echo "string";die();
            $di = $this->getDI();
            $view = $di->get('view');
            $this->setContent($view->getParamsToView());
        }
    }

    private function setContent($data){

        $di = $this->getDI();
        $response = $di['response'];
        // var_dump($response->getStatusCode())die();
        $codeAndStatus = $response->getStatusCode() ? $response->getStatusCode() : 200;
        $code = substr($codeAndStatus,0,3);
        $return = ['code'=>$code];
        $return['message'] = $codeAndStatus;
        $return['data'] = $data;
        
        $response->setJsonContent($return);
    }

    //event at dispater:beforeException
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

        }elseif($exception instanceof \Library\Exception){
            $code = $exception->getCode();
            $message = $exception->getMessage();
            $response->setStatusCode($code, $message);            
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

