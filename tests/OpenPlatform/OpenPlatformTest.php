<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace EasyWeChat\Tests\OpenPlatform;

use Doctrine\Common\Cache\ArrayCache;
use EasyWeChat\Applications\OpenPlatform\Core\AccessToken;
use EasyWeChat\Applications\OpenPlatform\Core\VerifyTicket;
use EasyWeChat\Factory;
use EasyWeChat\Tests\TestCase;
use Mockery as m;

class OpenPlatformTest extends TestCase
{
    public function testOpenPlatform()
    {
        $openPlatform = $this->make()->offsetGet('open_platform.instance');

        $this->assertInstanceOf('EasyWeChat\Applications\OpenPlatform\Api\BaseApi', $openPlatform->api);
        $this->assertInstanceOf('EasyWeChat\Applications\OpenPlatform\Api\PreAuthorization', $openPlatform->pre_auth);
        $this->assertInstanceOf('EasyWeChat\Applications\OpenPlatform\Server\Guard', $openPlatform->server);
    }

    public function testMakeAuthorizer()
    {
        $verifyTicket = new VerifyTicket('open-platform-appid@999', new ArrayCache());
        $verifyTicket->setTicket('ticket');

        $cache = m::mock('Doctrine\Common\Cache\Cache', function ($mock) {
            $mock->shouldReceive('fetch')->andReturn('thisIsACachedToken');
            $mock->shouldReceive('save')->andReturnUsing(function ($key, $token, $expire) {
                return $token;
            });
        });
        $accessToken = new AccessToken(
            'open-platform-appid@999',
            'open-platform-secret'
        );
        $accessToken->setCache($cache);
        $accessToken->setVerifyTicket($verifyTicket);

        $app = $this->make();
        $app['open_platform.access_token'] = $accessToken;
        $newApp = $app->offsetGet('open_platform.instance')->createAuthorizerApplication('authorizer-appid@999', 'authorizer-refresh-token');

        $this->assertInstanceOf('EasyWeChat\Applications\OpenPlatform\Core\AuthorizerAccessToken', $newApp->access_token);
        $this->assertSame('authorizer-appid@999', $newApp->access_token->getAppId());
    }

    /**
     * Makes application.
     *
     * @return Factory
     */
    private function make()
    {
        $config = [
            'app_id' => 'init-appid',
            'secret' => 'init-secret',
            'open_platform' => [
                'app_id' => 'your-app-id',
                'secret' => 'your-app-secret',
                'token' => 'your-token',
                'aes_key' => 'your-ase-key',
            ],
        ];

        return new Factory($config);
    }
}