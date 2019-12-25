<?php

/**
 * Created by QiLin.
 * User: NO.01
 * Date: 2019/12/25
 * Time: 14:55
 */

namespace Passport\Invitation;

use Passport\Auth\HttpCode;
use Passport\SystemMsg\SendSystemMsg;

class Invitation extends HttpCode
{

    /**
     * 生成邀请码
     * Author:QiLin
     * @param string $currentPassportId
     * @param string $currentMobile
     * @param string $currentSiteId
     * @param string $currentCompanyId
     * @return null
     */
    public function generateInvitation(string $currentPassportId, string $currentMobile, string $currentSiteId, string $currentCompanyId)
    {
        if (!preg_match('/^1[\d]{10}$/', $currentMobile)) {
            $this->errorMsg = '手机号格式不正确';
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return null;
        }
        $site = $this->getSite($currentSiteId);
        if (empty($site)) {
            $this->errorMsg = '读取当前站点信息失败';
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return null;
        }
        $errorMap = [
            '20016' => '生成邀请失败,操作数据失败',
            '60002' => '生成邀请失败,邀请账户不存在',
        ];
        $handleRes = (new \Services\Passport\Invitation\Invitation())->generate($currentPassportId, $currentMobile, $currentSiteId, $currentCompanyId, $site['url']);
        if (isset($errorMap[$handleRes['resultCode']])) {
            $this->errorMsg = $errorMap[$handleRes['resultCode']]['message'];
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return null;
        }
        return $handleRes;
    }

    /**
     * 获取站点
     * Author:QiLin
     * @param $currentSiteId
     * @return array
     */
    public static function getSite(string $currentSiteId)
    {
        $site = (new \Services\Passport\Site\App())->list([$currentSiteId]);
        return $site[0] ?? [];
    }

    /**
     * 销毁邀请码
     * Author:QiLin
     * @param string $invitationCode
     * @return bool
     */
    public function unsetInvitationCode(string $invitationCode): bool
    {
        $errorMap = ['20016' => '销毁邀请码失败,操作数据失败'];
        $handleRes = (new \Services\Passport\Invitation\Invitation())->changeIsAvailable($invitationCode);
        if (isset($errorMap[$handleRes['resultCode']])) {
            $this->errorMsg = $errorMap[$handleRes['resultCode']];
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return false;
        }
        return true;
    }

    /**
     * 根据邀请码激活邀请
     * Author:QiLin
     * @param string $currentPassportId
     * @param string $invitationCode
     * @param $joinMsg
     * @return bool
     */
    public function activateByInvitationCode(string $currentPassportId, string $invitationCode, $joinMsg): bool
    {
        if (!$currentPassportId or !$invitationCode) {
            $this->errorMsg = 'currentPassportId和invitationCode不能为空';
            $this->errorCode = self::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        $siteInfo = (new \Services\Passport\Invitation\Invitation())->get($invitationCode);
        if (empty($siteInfo)) {
            $this->errorMsg = '邀请码无效';
            $this->errorCode = self::UNAUTHORIZED_HTTP_CODE;
            return false;
        }
        $error = [
            '20016' => '操作数据失败',
            '5020' => '邀请已过期',
            '5021' => '邀请不存在',
            '5022' => '邀请不可用',
            '40011' => '公司站点不存在',
            '50031' => '系统应用不存在',
            '4000' => '账户站点已存在',
        ];
        //激活邀请
        $activateByCode = (new \Services\Passport\Account\Site())->activateByCode($currentPassportId, $invitationCode, $siteInfo['siteId']);
        if (isset($error[$activateByCode['resultCode']])) {
            $this->errorMsg = $error[$activateByCode['resultCode']];
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return false;
        }
        $sendSystemMsg = new SendSystemMsg();
        $sendRes = $sendSystemMsg->sendJoinMsg($siteInfo['siteId'], $joinMsg);
        if (!$sendRes) {
            $this->errorMsg = $sendSystemMsg->errorMsg;
            $this->errorCode = $sendSystemMsg->errorCode;
            return false;
        }
        return true;
    }

    /**
     * 检查邀请是否过期
     * Author:QiLin
     * @param string $currentCode
     * @return bool
     */
    public function checkInvitationCode(string $currentCode):bool
    {
        $errorMap = [
            '5020' => '邀请已过期',
            '5021' => '邀请不存在',
            '5022' => '邀请不可用',
        ];
        $effective = (new \Services\Passport\Invitation\Invitation())->isEffective($currentCode);
        if (isset($errorMap[$effective['resultCode']])) {
            $this->errorMsg = $errorMap[$effective['resultCode']];
            $this->errorCode = self::HTTP_INTERNAL_SERVER_ERROR_CODE;
            return false;
        }
        return true;
    }
}