<?php

namespace MrMadClown\Mnemosyne;

/**
 * @method static Expression pi()
 * @method static Expression abs(string $x)
 * @method static Expression acos(string $x)
 * @method static Expression asin(string $x)
 * @method static Expression atan(string $x, string $y = null)
 * @method static Expression ceil(string $x)
 * @method static Expression conv(string $x, string $from, string $to)
 * @method static Expression cos(string $x)
 * @method static Expression cot(string $x)
 * @method static Expression crc32(string $x)
 * @method static Expression degrees(string $x)
 * @method static Expression exp(string $x)
 * @method static Expression floor(string $x)
 * @method static Expression format(string $x, string $format)
 * @method static Expression hex(string $x)
 * @method static Expression ln(string $x)
 * @method static Expression log(string $x, string $b = null)
 * @method static Expression log2(string $x)
 * @method static Expression log10(string $x)
 * @method static Expression mod(string $n, string $m)
 * @method static Expression pow(string $x, string $y)
 * @method static Expression radians(string $x)
 * @method static Expression rand(string $x = null)
 * @method static Expression round(string $x, string $d = null)
 * @method static Expression sign(string $x)
 * @method static Expression sqrt(string $x)
 * @method static Expression tan(string $x)
 * @method static Expression truncate(string $x, string $d)
 *
 * @method static Expression ADDDATE(string $date, string $days)
 * @method static Expression ADDTIME(string $expr1, string $expr2)
 * @method static Expression CONVERT_TZ(string $dt, string $from_tz, string $to_tz)
 * @method static Expression CURDATE()
 * @method static Expression CURRENT_DATE()
 * @method static Expression CURRENT_TIME(string $fsp = null)
 * @method static Expression CURRENT_TIMESTAMP(string $fsp = null)
 * @method static Expression CURTIME(string $fsp = null)
 * @method static Expression DATE(string $expr)
 * @method static Expression DATEDIFF(string $expr1, string $expr2)
 * @method static Expression DATE_ADD(string $date, string $expr)
 * @method static Expression DATE_SUB(string $date, string $expr)
 * @method static Expression DATE_FORMAT(string $date, string $format)
 * @method static Expression DAY(string $date)
 * @method static Expression DAYNAME(string $date)
 * @method static Expression DAYOFMONTH(string $date)
 * @method static Expression DAYOFWEEK(string $date)
 * @method static Expression DAYOFYEAR(string $date)
 * @method static Expression EXTRACT(string $unit)
 * @method static Expression FROM_DAYS(string $N)
 * @method static Expression FROM_UNIXTIME(string $unixTimestamp, string $format = null)
 * @method static Expression GET_FORMAT(string $date, string $format)
 * @method static Expression HOUR(string $time)
 * @method static Expression LAST_DAY(string $date)
 * @method static Expression LOCALTIME(string $fsp = null)
 * @method static Expression LOCALTIMESTAMP(string $fsp = null)
 * @method static Expression MAKEDATE(string $year, string $dayOfYear)
 * @method static Expression MAKETIME(string $hour, string $minute, string $second)
 * @method static Expression MICROSECOND(string $expr)
 * @method static Expression MINUTE(string $time)
 * @method static Expression MONTH(string $date)
 * @method static Expression MONTHNAME(string $date)
 * @method static Expression NOW(string $fsp = null)
 * @method static Expression PERIOD_ADD(string $P, string $N)
 * @method static Expression PERIOD_DIFF(string $P1, string $P2)
 * @method static Expression QUARTER(string $date)
 * @method static Expression SECOND(string $time)
 * @method static Expression SEC_TO_TIME(string $seconds)
 * @method static Expression STR_TO_DATE(string $str, string $format)
 * @method static Expression SUBDATE(string $expr, string $days)
 * @method static Expression SUBTIME(string $expr1, string $expr2)
 * @method static Expression SYSDATE(string $fsp = null)
 * @method static Expression TIME(string $expr)
 * @method static Expression TIMEDIFF(string $expr1, string $expr2)
 * @method static Expression TIMESTAMP(string $expr1, string $expr2)
 * @method static Expression TIMESTAMPADD(string $unit, string $interval, string $datetimeExpr)
 * @method static Expression TIMESTAMPDIFF(string $unit, string $datetimeExpr1, string $datetimeExpr2)
 * @method static Expression TIME_FORMAT(string $time, string $format)
 * @method static Expression TIME_TO_SEC(string $time)
 * @method static Expression TO_DAYS(string $date)
 * @method static Expression TO_SECONDS(string $expression)
 * @method static Expression UNIX_TIMESTAMP(string $date = null)
 * @method static Expression UTC_DATE()
 * @method static Expression UTC_TIME(string $fsp = null)
 * @method static Expression UTC_TIMESTAMP(string $fsp = null)
 * @method static Expression WEEK(string $date, string $mode = null)
 * @method static Expression WEEKDAY(string $date)
 * @method static Expression WEEKOFYEAR(string $date)
 * @method static Expression YEAR(string $date)
 * @method static Expression YEARWEEK(string $date, string $mode)
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

    /** @param array<int, string> $args */
    public static function __callStatic(string $method, array $args): Expression
    {
        return new Expression(sprintf('%s(%s)', $method, implode(', ', $args)));
    }
}
