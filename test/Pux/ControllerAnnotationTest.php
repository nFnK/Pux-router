<?php
use Pux\Mux;
use Pux\Executor;
use Pux\Controller;

class ParentController extends Controller {

    /**
     *
     * @Route("/update")
     * @Method("GET")
     */
    public function pageAction() {  }
}

class ChildController extends ParentController { 
    // we should override this action.
    public function pageAction() {  }

    public function subpageAction() {  }

}


class ControllerAnnotationTest extends PHPUnit_Framework_TestCase
{

    public function testAnnotationForGetActionMethods()
    {
        $parent = new ParentController;
        ok($parent);
        ok( $map = $parent->getActionMethods() );
        ok( is_array($map), 'map is an array' );
        ok( isset($map[0]), 'one path' );
        is( 1, count($map), 'count of map' );
        is( 'pageAction', $map[0][0], 'pageAction');
        is([ 'Route' => '/update', 'Method' => 'GET' ], $map[0][1] );
        is([ 'class' => 'ParentController' ], $map[0][2] );
    }


    public function testInheritedActions() 
    {
        $con = new ChildController;
        ok($con);
        ok( $map = $con->getActionMethods() );
        ok( is_array($map), 'map is an array' );

        is( 3, count($map), 'count of map should contain parent and child methods' );

        ok( is_array($map[0]), 'first path' );
        ok( is_array($map[1]), 'second path' );
        ok( is_array($map[2]), 'third path' );
        
        $expectedMap = array (
            array(
                'pageAction', array (
                    'Route' => '/update',
                    'Method' => 'GET',
                ), array(
                    'class' => 'ParentController',
                    'is_parent' => true,
                ),
            ),
            array(
                'pageAction', array(), array( 'class' => 'ChildController' ),
            ),
            array(
                'subpageAction', array(), array( 'class' => 'ChildController' ),
            ),
        );
        is( $expectedMap, $map );
    }

    public function testAnnotations()
    {
        if (defined('HHVM_VERSION')) {
            echo "HHVM does not support Reflection to expand controller action methods";
            return;
        }

        $controller = new ExpandableProductController;
        ok($controller);

        ok( is_array( $map = $controller->getActionMethods() ) );

        count_ok( 6, $map);
        is('indexAction', $map[0][0], 'the method name');
        is(array(),       $map[0][1], 'annotation info');
        is(array('class' => 'ExpandableProductController'), $map[0][2], 'meta');

        is('fooBarAction', $map[5][0], 'the method name');
        is(array(),       $map[5][1], 'annotation info');
        is(array('class' => 'ExpandableProductController'), $map[5][2], 'meta');

        $routes = $controller->getActionRoutes();
        is('', $routes[0][0], 'the path');
        is('indexAction', $routes[0][1], 'the mapping method');
        ok( is_array($routes) );

        $mux = new Pux\Mux;

        // works fine
        // $submux = $controller->expand();
        // $mux->mount('/product', $submux );

        // gc scan bug
        $mux->mount('/product', $controller->expand() );
        ok($mux);

        $paths = array(
            '/product/delete' => 'DELETE',
            '/product/update' => 'PUT' ,
            '/product/add'    => 'POST' ,
            '/product/foo/bar' => null,
            '/product/item' => 'GET',
            '/product' => null,
        );

        foreach( $paths as $path => $method ) {
            if ( $method ) {
                $_SERVER['REQUEST_METHOD'] = $method;
            } else {
                $_SERVER['REQUEST_METHOD'] = 'GET';
            }
            ok( $mux->dispatch($path) , $path);
        }
    }


}

