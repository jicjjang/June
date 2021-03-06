<?php
namespace Jicjjang\June;

use Jicjjang\June\stubs\AdminController;
use Psr\Http\Message\ServerRequestInterface;
use Mockery;
use PHPUnit_Framework_TestCase;
use ArrayObject;

class RouterTest extends PHPUnit_Framework_TestCase
{
    /** @var Router */
    public $app;

    public function setUp()
    {
        $this->app = new Router();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testMethodsWithMap()
    {
        $this->assertEquals(0, $this->app->count());

        $handler = function () {
            return "!!!";
        };

        $this->app->get('/', $handler);
        $this->assertEquals(1, $this->app->count());

        $this->app->post('/', $handler);
        $this->assertEquals(2, $this->app->count());

        $this->app->put('/', $handler);
        $this->assertEquals(3, $this->app->count());

        $this->app->delete('/', $handler);
        $this->assertEquals(4, $this->app->count());

        $this->app->options('/', $handler);
        $this->assertEquals(5, $this->app->count());
    }

    public function testDispatch()
    {
        $getMock = Mockery::mock(ServerRequestInterface::class);
        $getMock->shouldReceive('getMethod')->andReturn('GET');
        $getMock->shouldReceive('getUri->getPath')->andReturn('/');
        $getMock->shouldReceive('setArguments')->with([
        ]);

        $postMock = Mockery::mock(ServerRequestInterface::class);
        $postMock->shouldReceive('getMethod')->andReturn('POST');
        $postMock->shouldReceive('getUri->getPath')->andReturn('/');
        $postMock->shouldReceive('setArguments')->with([
        ]);

        $getCalled = 0;
        $postCalled = 0;
        $this->app->get(
            '/',
            function (ServerRequestInterface $req, \Closure $next) {
                return $next($req) . ' getMiddleware';
            },
            function (ServerRequestInterface $req) use (&$getCalled) {
                $getCalled++;
                return 'get';
            }
        );

        $this->app->post(
            '/',
            function (ServerRequestInterface $req, \Closure $next) {
                return $next($req) . ' postMiddleware';
            },
            function (ServerRequestInterface $req) use (&$postCalled) {
                $postCalled++;
                return 'post';
            }
        );

        $this->assertEquals('get getMiddleware', $this->app->dispatch($getMock));
        $this->assertEquals(1, $getCalled);
        $this->assertEquals(0, $postCalled);

        $this->assertEquals('post postMiddleware', $this->app->dispatch($postMock));
        $this->assertEquals(1, $getCalled);
        $this->assertEquals(1, $postCalled);
    }

    public function testDispatchWithArguments()
    {
        $getMock = Mockery::mock(ServerRequestInterface::class);
        $getMock->shouldReceive('getMethod')->andReturn('GET');
        $getMock->shouldReceive('getUri->getPath')->andReturn('/jicjjang/hello');
        $getMock->shouldReceive('withAttribute');

        $this->app->get(
            '/{name}/{message}',
            function (ServerRequestInterface $req, \Closure $next) {
                return $next($req) . ' getMiddleware';
            },
            function (ServerRequestInterface $req) {
                return 'get';
            }
        );

        $this->assertEquals('get getMiddleware', $this->app->dispatch($getMock));
    }

    public function testBindingController()
    {
        $controllerMock = Mockery::mock(ControllerInterface::class);

        $containerMock = Mockery::mock(ArrayObject::class);
        $containerMock->shouldReceive('offsetSet')->with('admin', $controllerMock);
        $containerMock->shouldReceive('offsetGet')->with('admin')->andReturn($controllerMock);

        $router = new Router;

        $this->assertSame($router, $router->setController('admin', $controllerMock));
        $this->assertSame($controllerMock, $router->getController('admin'));

        $router = new Router($containerMock);

        $this->assertSame($router, $router->setController('admin', $controllerMock));
        $this->assertSame($controllerMock, $router->getController('admin'));
    }


    public function testAnyMethod()
    {
        $anyMock = Mockery::mock(ServerRequestInterface::class);
        $anyMock->shouldReceive('getMethod')->andReturn('GET');
        $anyMock->shouldReceive('getUri->getPath')->andReturn('/');
        $anyMock->shouldReceive('setArguments')->with([
        ]);

        $this->app->any('/', function () {
            return 'any';
        });

        $this->assertEquals('any', $this->app->dispatch($anyMock));
        $this->assertEquals('any', $this->app->dispatch($anyMock));
    }

    public function testExecuteWithController()
    {
        $app = new Router();
        $app->setController('admin', new AdminController());

        $getMock = Mockery::mock(ServerRequestInterface::class);
        $getMock->shouldReceive('getMethod')->andReturn('GET');
        $getMock->shouldReceive('getUri->getPath')->andReturn('/');
        $getMock->shouldReceive('setArguments')->with([
        ]);

//        $app->get('/', "middleware@admin", ['admin', 'action']);
        $app->get('/', ["admin", "middleware"], ['admin', 'action']);

//        $this->assertEquals('Hello World!!!', $app->dispatch($getMock));
        $this->assertEquals('Hello World!!!', $app->dispatch($getMock));
    }
}
