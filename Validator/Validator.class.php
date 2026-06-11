<?php
/**
 * Eventic Packages
 * Copyright (C) 2007-2026 WebProduction
 *
 * @author Maxim Miroshnichenko <max@miroshnichenko.org>
 */

/**
 * Класс-проверщик различных данных на корректность
 */
class Validator {

    /**
     * Проверить E-mail на корректность
     *
     * @param string $email
     * @return bool
     */
    public static function CheckEmail($email) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверить дату на корректность ввода
     *
     * @param int $datetime
     * @return bool
     */
    public static function CheckDate($datetime) {
        if ($datetime) {
            return strtotime($datetime) > 0;
        } else {
            return false;
        }
    }

    /**
     * Проверить доменное имя на корректность ввода
     *
     * @param string $domainname
     * @return bool
     */
    public static function CheckDomainName($domainname) {
        if (filter_var($domainname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверить URL на корректность ввода
     *
     * @param string $url
     * @return bool
     */
    public static function CheckURL($url) {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверить URL или host без wrapper'a на корректность
     *
     * @param string $hostname
     * @return bool
     */
    public static function CheckHost($hostname) {
        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверить IP на корректность ввода
     *
     * @param int $ip
     * @return bool
     */
    public static function CheckIP($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверить IPv4 на корректность ввода
     *
     * @param int $ip
     * @return bool
     */
    public static function CheckIPv4($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверить IPv6 на корректность ввода
     *
     * @param int $ip
     * @return bool
     */
    public static function CheckIPv6($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check port
     *
     * @param $port
     * @return bool
     */
    public static function CheckPort($port) {
        $port = (int) $port;
        if ($port < 0) {
            return false;
        } elseif ($port > 65535) {
            return false;
        } else {
            return true;
        }
    }

}