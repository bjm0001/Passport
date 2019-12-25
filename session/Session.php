<?php

namespace Passport\Session;

use Passport\Auth\Base;
use Phalcon\Di\InjectionAwareInterface;

/**
 * Created by QiLin.
 * User: NO.01
 * Date: 2019/12/17
 * Time: 9:29
 */
class Session implements InjectionAwareInterface
{
    protected $_di;

    public $errorMsg;

    public $errorCode;

    public function setDI(\Phalcon\DiInterface $dependencyInjector)
    {
        $this->_di = $dependencyInjector;
        // TODO: Implement setDI() method.
    }

    public function getDI()
    {
        return $this->_di;
        // TODO: Implement getDI() method.
    }


    public function delete($passportId)
    {
        $errorMap = ['60002' => '账户不存在', '20016' => '操作数据失败'];
        //刷新ticket
        $pStatus = Services\Passport\Account\Manager::refreshTicket(intval($passportId));
        if (!$pStatus) {
            $this->errorMsg = $errorMap['20016'];
            return false;
        }
        //退出下单中心账户
        $refStatus = Services\OrderCenter\Administrator\Manager::refreshToken(intval($passportId));
        if (isset($errorMap[$refStatus['resultCode']]) && $refStatus['resultCode'] != '60002') {
            $this->errorMsg = $errorMap[$refStatus['resultCode']];
            return false;
        }
        //退出综合云服务平台
        $res = Services\Common\Administrator\Manager::refreshToken(intval($passportId));
        if (isset($errorMap[$res['resultCode']]) && $res['resultCode'] != '60002') {
            $this->errorMsg = $errorMap[$res['resultCode']];
            return false;
        }
        return $this->unsetCookie();
    }

    private function unsetCookie()
    {
        $domain = implode('.', array_slice(explode('.', $_SERVER['HTTP_HOST']), -2, 2));
        $ticket = $this->getDI()->get('cookie')->get(Base::TICKET_HEADER_NAME)->getValue();
        $this->getDI()->get('cookie')->set(Base::TICKET_HEADER_NAME, $ticket, time() - 200, '/', false, $domain, true);
        return true;
    }

}