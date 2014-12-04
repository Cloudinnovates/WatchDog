<?php

namespace WatchDog\Utils;

class Date {

    public static function formatCurrentDateTime(){

        return date('D d M Y H:i:s');

    }
    public static function getCurrentMonth($fullText = false){

        return date($fullText ? 'F' : 'M');

    }
    public static function getLastMonth($fullText = false){

        return date($fullText ? 'F' : 'M', strtotime(date($fullText ? 'F' : 'M') . ' last month'));

    }
    public static function getFirstDayOfMonth(){

        return new \DateTime('first day of this month 00:00:00');

    }
    public static function isFirstDayOfMonth(){

        $firstDayOfMonth = self::getFirstDayOfMonth();
        $today = new \DateTime();
        $diff = $today->diff($firstDayOfMonth);

        return $diff->d === 0;

    }

}