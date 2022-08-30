<?php

namespace MrMadClown\Mnemosyne;

/**
 * @method static pi(): Expression
 * @method static abs(string $x): Expression
 * @method static acos(string $x): Expression
 * @method static asin(string $x): Expression
 * @method static atan(string $x, string $y = null): Expression
 * @method static ceil(string $x): Expression
 * @method static conv(string $x, string $from, string $to): Expression
 * @method static cos(string $x): Expression
 * @method static cot(string $x): Expression
 * @method static crc32(string $x): Expression
 * @method static degrees(string $x): Expression
 * @method static exp(string $x): Expression
 * @method static floor(string $x): Expression
 * @method static format(string $x, string $format): Expression
 * @method static hex(string $x): Expression
 * @method static ln(string $x): Expression
 * @method static log(string $x, string $b = null): Expression
 * @method static log2(string $x): Expression
 * @method static log10(string $x): Expression
 * @method static mod(string $n, string $m): Expression
 * @method static pow(string $x, string $y): Expression
 * @method static radians(string $x): Expression
 * @method static rand(string $x = null): Expression
 * @method static round(string $x, string $d = null): Expression
 * @method static sign(string $x): Expression
 * @method static sqrt(string $x): Expression
 * @method static tan(string $x): Expression
 * @method static truncate(string $x, string $d): Expression
 *
 * @method static ADDDATE(string $date, string $days): Expression
 * @method static ADDTIME(string $expr1, string $expr2): Expression
 * @method static CONVERT_TZ(string $dt, string $from_tz, string $to_tz): Expression
 * @method static CURDATE(): Expression
 * @method static CURRENT_DATE(): Expression
 * @method static CURRENT_TIME(string $fsp = null): Expression
 * @method static CURRENT_TIMESTAMP(string $fsp = null): Expression
 * @method static CURTIME(string $fsp = null): Expression
 * @method static DATE(string $expr): Expression
 * @method static DATEDIFF(string $expr1, string $expr2): Expression
 * @method static DATE_ADD(string $date, string $expr): Expression
 * @method static DATE_SUB(string $date, string $expr): Expression
 * @method static DATE_FORMAT(string $date, string $format): Expression
 * @method static DAY(string $date): Expression
 * @method static DAYNAME(string $date): Expression
 * @method static DAYOFMONTH(string $date): Expression
 * @method static DAYOFWEEK(string $date): Expression
 * @method static DAYOFYEAR(string $date): Expression
 * @method static EXTRACT(string $unit): Expression
 * @method static FROM_DAYS(string $N): Expression
 * @method static FROM_UNIXTIME(string $unixTimestamp, string $format = null): Expression
 * @method static GET_FORMAT(string $date, string $format): Expression
 * @method static HOUR(string $time): Expression
 * @method static LAST_DAY(string $date): Expression
 * @method static LOCALTIME(string $fsp = null): Expression
 * @method static LOCALTIMESTAMP(string $fsp = null): Expression
 * @method static MAKEDATE(string $year, string $dayOfYear): Expression
 * @method static MAKETIME(string $hour, string $minute, string $second): Expression
 * @method static MICROSECOND(string $expr): Expression
 * @method static MINUTE(string $time): Expression
 * @method static MONTH(string $date): Expression
 * @method static MONTHNAME(string $date): Expression
 * @method static NOW(string $fsp = null): Expression
 * @method static PERIOD_ADD(string $P, string $N): Expression
 * @method static PERIOD_DIFF(string $P1, string $P2): Expression
 * @method static QUARTER(string $date): Expression
 * @method static SECOND(string $time): Expression
 * @method static SEC_TO_TIME(string $seconds): Expression
 * @method static STR_TO_DATE(string $str, string $format): Expression
 * @method static SUBDATE(string $expr, string $days): Expression
 * @method static SUBTIME(string $expr1, string $expr2): Expression
 * @method static SYSDATE(string $fsp = null): Expression
 * @method static TIME(string $expr): Expression
 * @method static TIMEDIFF(string $expr1, string $expr2): Expression
 * @method static TIMESTAMP(string $expr1, string $expr2): Expression
 * @method static TIMESTAMPADD(string $unit, string $interval, string $datetimeExpr): Expression
 * @method static TIMESTAMPDIFF(string $unit, string $datetimeExpr1, string $datetimeExpr2): Expression
 * @method static TIME_FORMAT(string $time, string $format): Expression
 * @method static TIME_TO_SEC(string $time): Expression
 * @method static TO_DAYS(string $date): Expression
 * @method static TO_SECONDS(string $expression): Expression
 * @method static UNIX_TIMESTAMP(string $date = null): Expression
 * @method static UTC_DATE(): Expression
 * @method static UTC_TIME(string $fsp = null): Expression
 * @method static UTC_TIMESTAMP(string $fsp = null): Expression
 * @method static WEEK(string $date, string $mode = null): Expression
 * @method static WEEKDAY(string $date): Expression
 * @method static WEEKOFYEAR(string $date): Expression
 * @method static YEAR(string $date): Expression
 * @method static YEARWEEK(string $date, string $mode): Expression
 */
class Expression implements \Stringable
{
    public function __construct(private readonly string $expression)
    {
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    public static function __callStatic(string $method, array $args): Expression
    {
        return new Expression(sprintf('%s(%s)', $method, implode(', ', $args)));
    }
}
