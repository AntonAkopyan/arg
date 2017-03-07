<?php

//require_once('../vendor/autoload.php');

/**
 * Created by IntelliJ IDEA.
 * User: volodymyr.ivchyk
 * Date: 10/24/16
 * Time: 5:11 PM
 */
namespace Game\Services;

use Game\JWT\JWT;
use Library\Game;

class Auth
{
    const SECRET_KEY = "JoyRocksSecretKey1234567890";
    const ALGORITHM = 'HS512';
    const TOKEN_LIFETIME = 2592000; // 1 month;

    public function login($email, $password)
    {
        $result = [
            'data' => [],
            'error' =>  0
        ];

        if ($email && $password) {
            $mainDb = Game::getApp()->getMainDb();

            $objectRevenueQuery = $mainDb->paramQuery("SELECT * FROM auth WHERE email = :email",
                [':email' => $email]);

            $row = $objectRevenueQuery->fetch();

            if (count($row) > 0 && $row['password'] == $password) {
                $tokenId = base64_encode(mcrypt_create_iv(32));
                $issuedAt = time();
                $notBefore = $issuedAt;
                $expire = $notBefore + self::TOKEN_LIFETIME;
                $serverName = 'http://jr-web-test.joyrocks.com/'; /// set your domain name

                $data = [
                    'iat' => $issuedAt,         // Issued at: time when the token was generated
                    'jti' => $tokenId,          // Json Token Id: an unique identifier for the token
                    'iss' => $serverName,       // Issuer
                    'nbf' => $notBefore,        // Not before
                    'exp' => $expire,           // Expire
                    'data' => [                  // Data related to the logged user you can set your required data
                        'uid' => $row['user_id'], // id from the users table
                        'email' => $row['email'], // id from the users table
                        'firstName' => $row['firstname'], // id from the users table
                        'lastName' => $row['lastname'], // id from the users table
                        'role' => $row['role'], // id from the users table
                    ]
                ];

                $secretKey = base64_decode(self::SECRET_KEY);
                /// Here we will transform this array into JWT:
                $jwt = JWT::encode(
                    $data, //Data to be encoded in the JWT
                    $secretKey, // The signing key
                    self::ALGORITHM
                );

                $result['data']['user']['uid'] = $row['user_id'];
                $result['data']['user']['email'] = $row['email'];
                $result['data']['user']['firstName'] = $row['firstname'];
                $result['data']['user']['lastName'] = $row['lastname'];
                $result['data']['token'] = $jwt;
            } else {
                $result['error'] = 2;
            }
        } else {
            $result['error'] = 1;
        }

        return $result;
    }


    public function checkToken($token)
    {
        $result = [
            'data' => [],
            'error' =>  0
        ];

        try {
            $secretKey = base64_decode(self::SECRET_KEY);
            $decoded = JWT::decode($token, $secretKey, array(self::ALGORITHM));

//            $result = $decoded;
            $result['data']["exp"] = $decoded->exp;
            $result['data']["user"] = $decoded->data;
            $result['data']["token"] = $token;

        } catch (\Exception $err) {
            $result['error'] = 1;
            $result['data'] = $err;
        }

        return $result;
    }
}