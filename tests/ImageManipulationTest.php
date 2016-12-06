<?php

namespace Middlewares\Tests;

use Middlewares\ImageManipulation;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class ImageManipulationTest extends \PHPUnit_Framework_TestCase
{
    public function testImageManipulation()
    {
        $key = uniqid();
        $path = '/assets/foto.jpg';
        $uri = ImageManipulation::getUri($path, 'resizeCrop,50,50|format,png', $key);
        $request = Factory::createServerRequest([], 'GET', '/subdirectory/of/images'.$uri)
            ->withHeader('Accept', 'image/*');

        $response = Dispatcher::run([
            new ImageManipulation($key),
            function ($request) use ($path) {
                $this->assertEquals('/subdirectory/of/images'.$path, $request->getUri()->getPath());
                $content = file_get_contents(
                    'https://upload.wikimedia.org/wikipedia/commons/5/58/Vaca_rubia_galega._Oroso_1.jpg'
                );

                $response = Factory::createResponse();
                $response->getBody()->write($content);

                return $response;
            },
        ], $request);

        $this->assertEquals('png', pathinfo($uri, PATHINFO_EXTENSION));
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals('image/png', $response->getHeaderLine('Content-Type'));

        $info = getimagesizefromstring((string) $response->getBody());

        $this->assertEquals(50, $info[0]);
        $this->assertEquals(50, $info[1]);
        $this->assertEquals(IMAGETYPE_PNG, $info[2]);
    }
}
