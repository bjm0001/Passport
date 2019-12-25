<?php

namespace Passport\Auth;

/**
 * Created by QiLin.
 * User: NO.01
 * Date: 2019/12/25
 * Time: 15:11
 */
class  HttpCode {

    public $errorMsg = "";

    public $errorCode = "";


    //认证失败
    const UNAUTHORIZED_HTTP_CODE = 401;

    //认证过期
    const TOKEN_AUTHORIZED_OUT_DATE = 407;

    //http 服务错误
    const HTTP_INTERNAL_SERVER_ERROR_CODE = 506;

}