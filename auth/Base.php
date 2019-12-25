<?php

namespace Passport\Auth;

use Phalcon\DI\InjectionAwareInterface;
use Phalcon\DiInterface;
use Phalcon\Http\Request as HttpRequest;


/**
 * Author:QiLin
 * Class BaseAuth
 * @package Application\OriginOrder\Components\Internet\Http
 */
abstract class Base extends HttpCode implements AuthInterface, InjectionAwareInterface
{

    public $requestHeaders;
    public $accessToken;
    public $accessTicket;
    public $passportInfo;
    public $currentAdministrator;
    public $currentRoute;

    protected $_di;


    const ACCESS_TOKEN_HEADER_NAME = 'Access-Token';

    const TICKET_HEADER_NAME = 'Access-Ticket';

    public function __construct()
    {
        $request = new HttpRequest();

        $this->requestHeaders = $request->getHeaders();

        if (isset($this->requestHeaders[self::ACCESS_TOKEN_HEADER_NAME])) {
            $this->accessToken = $this->requestHeaders[self::ACCESS_TOKEN_HEADER_NAME];
        }

        if (isset($this->requestHeaders[self::TICKET_HEADER_NAME])) {
            $this->accessTicket = $this->requestHeaders[self::TICKET_HEADER_NAME];
        }

    }

    public function setDI(DiInterface $di)
    {
        $this->_di = $di;
    }

    public function getDi()
    {
        return $this->_di;
    }


    public function checkAccessTicket(): bool
    {
        $errorMap = [
            '40001' => '通行证过期，请重新登录',
            '40005' => '通行证不可用，锁定中',
            '40003' => '通行证过期，请重新登录',
        ];
        $pInfo = (new \Services\Passport\Account\Manager())->authorize($this->accessTicket);
        if ($pInfo['resultCode'] and isset($errorMap[$pInfo['resultCode']])) {
            $this->errorMsg = $errorMap[$pInfo['resultCode']];
            $this->errorCode = HttpCode::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        $this->passportInfo = $pInfo;
        return true;
    }

    public function getHasTicket(): bool
    {
        if ($this->accessTicket) {
            return true;
        }
        if ($this->getAccessTicket()) {
            return true;
        }
        return false;
    }

    public function getAccessTicket(): string
    {
        $cookie = $this->getDi()->get('cookie');
        $ticket = $cookie->get(self::TICKET_HEADER_NAME)->getValue();
        if ($ticket) {
            return $this->accessTicket = $ticket;
        }
        return $this->accessTicket;
    }


    public function getHasToken(): bool
    {
        if ($this->accessToken) {
            return true;
        }
        return false;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function checkAccessToken(): bool
    {
        if ($this->getHasToken() === false) {
            $this->errorMsg = "您的登录凭证不存在";
            $this->errorCode = HttpCode::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        return true;
    }


    public function getSiteInfo($siteId = null)
    {
        if ($siteId == null) {
            if (!$this->currentAdministrator) {
                return null;
            }
            $siteId = $this->currentAdministrator->siteId;
        }
        $site = (new \Services\Passport\Site\App())->list([$siteId]);
        if (empty($site)) {
            $this->errorMsg = '站点不存在';
            $this->errorCode = HttpCode::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return null;
        }
        return $site[0];
    }

    public function getPassportInfo()
    {
        if ($this->passportInfo) {
            return $this->passportInfo;
        }
        if ($this->getAccessTicket() || ($this->currentAdministrator && $this->currentAdministrator->passportId)) {
            if ($this->getAccessTicket()) {
                return ($this->checkAccessTicket() === true) ? $this->passportInfo : null;
            }
            if ($this->currentAdministrator && $this->currentAdministrator->passportId) {
                $passport = (new \Services\Passport\Account\Manager())->list(['id' => $this->currentAdministrator->passportId]);
                if (empty($passport)) {
                    $this->errorMsg = '通行证账户不存在';
                    $this->errorCode =HttpCode::UNAUTHORIZED_HTTP_CODE;
                    return null;
                }
                $pInfo = [];
                $pInfo['account'] = $passport[0]['name'];
                $pInfo['id'] = $passport[0]['id'];
                $pInfo['exp'] = '';
                $pInfo['resultCode'] = '0';
                return $this->passportInfo = $pInfo;
            }
        }
        $this->errorMsg = '登录失败，账户不存在';
        $this->errorCode = HttpCode::UNAUTHORIZED_HTTP_CODE;
        return null;
    }

    public function authorize($administratorId = null): bool
    {
        $currentAdministrator = $this->getCurrentAdministrator($administratorId);
        if (!$currentAdministrator) {
            return false;
        }
        $this->currentAdministrator = $currentAdministrator;
        return true;
    }


    public function getCurrentRoute()
    {
        $router = $this->getDi()->get('router');
        $currentMethod = $router->getMatchedRoute()->getHttpMethods();
        $currentPattern = $router->getMatchedRoute()->getPattern();
        return $this->currentRoute = $currentMethod . ':' . $currentPattern;
    }
}
