<?php

namespace Passport\Auth;
/**
 * Created by QiLin.
 * User: NO.01
 * Date: 2019/12/25
 * Time: 14:16
 */
interface  AuthInterface
{
    /**
     * 认证
     * Author:QiLin
     * @return mixed
     */
    public function authorize();

    /**
     * 获取Token
     * Author:QiLin
     * @return mixed
     */
    public function getAccessToken();

    /**
     * 获取Ticket
     * Author:QiLin
     * @return mixed
     */
    public function getAccessTicket();

    /**
     * 获取当前登录对象
     * Author:QiLin
     * @param $administratorId
     * @return mixed
     */
    public function getCurrentAdministrator($administratorId);

    /**
     * 获取当前登录公司对象
     * Author:QiLin
     * @return mixed
     */
    public function getCurrentCompany();

    /**
     * 获取当前登录的用户信息
     * Author:QiLin
     * @return mixed
     */
    public function getCurrentAdministratorInfo();

    /**
     * 获取当前登录用户绑定的通行证信息
     * Author:QiLin
     * @return mixed
     */
    public function getPassportInfo();

    /**
     * 获取当前登录用户的站点信息
     * Author:QiLin
     * @return mixed
     */
    public function getSiteInfo();

    /**
     * 获取当前路由
     * Author:QiLin
     * @return mixed
     */
    public function getCurrentRoute();

}