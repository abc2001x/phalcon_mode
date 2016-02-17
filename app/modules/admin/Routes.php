<?php
namespace Admin;
class Routes
{

    public function init($router)
    {
        
        $router->add('/admin.html', array(
            'module'     => 'admin',
            'controller' => 'index',
            'action'     => 'index',
        ));
        
        return $router;

    }

}
