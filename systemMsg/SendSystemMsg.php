<?php

namespace Passport\SystemMsg;

use Passport\Auth\HttpCode;
use Passport\Invitation\Invitation;

/**
 * Created by QiLin.
 * User: NO.01
 * Date: 2019/12/25
 * Time: 15:34
 */
class SendSystemMsg extends HttpCode
{
    /**
     * 发送加入消息
     * Author:QiLin
     * @param string $currentSiteId
     * @param string $joinMsg
     * @return bool
     */
    public function sendJoinMsg(string $currentSiteId,string $joinMsg)
    {
        $siteInfo = Invitation::getSite($currentSiteId);
        if (empty($siteInfo)) {
            $this->errorMsg = '手机号格式不正确';
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return false;
        }
        $joinMsg .= $siteInfo['name'] ?? '未找到平台名称';
        $result = (new \Services\Common\Message\SystemNotice())->insertNoticeType('join', $joinMsg, $siteInfo['accountId'], $siteInfo['companyId']);
        if ($result['code'] != '200') {
            $this->errorMsg = $result['message'];
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return false;
        }
        return true;
    }

    /**
     * 发送欢迎消息
     * Author:QiLin
     * @param string $currentPassportId
     * @param string $currentAdminName
     * @param string $currentCompanyId
     * @return bool
     */
    public function sendWelcomeMsg(string $currentPassportId, string $currentAdminName,string $currentCompanyId)
    {
        if (!$currentPassportId or !$currentAdminName or !$currentCompanyId) {
            $this->errorMsg = 'currentPassportId、currentAdminName、currentCompanyId不能为空';
            return false;
        }
        $msg = sprintf("欢迎%s使用系统工作平台", $currentAdminName);
        $result = (new \Services\Common\Message\SystemNotice())->insertNoticeType('welcome', $msg, $currentPassportId, $currentCompanyId);
        if ($result['code'] != '200') {
            $this->errorMsg = $result['message'];
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return false;
        }
        return true;
    }

}