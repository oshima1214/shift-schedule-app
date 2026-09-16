<?php

namespace App\Support;

/**
 * 週（月曜〜日曜）の計算をまとめたヘルパー。
 * 画面側の「前週／次週」切り替えは、この基準日を渡し直すだけで済むようにする。
 */
class Week
{
  /** @var array 曜日の表示名（0=月曜） */
  private static $labels = array('月', '火', '水', '木', '金', '土', '日');

  /**
   * 指定日が属する週の月曜日を返す。不正な値のときは今週の月曜日。
   *
   * @param string|null $date  Y-m-d
   * @return \DateTime
   */
  public static function monday($date = null)
  {
    if (is_string($date) and $date !== '')
    {
      $parsed = \DateTime::createFromFormat('Y-m-d', $date);

      if ($parsed and $parsed->format('Y-m-d') === $date)
      {
        $parsed->setTime(0, 0, 0);

        // ISO-8601では月曜が週の起点（N: 1=月曜）
        return $parsed->modify('-'.((int) $parsed->format('N') - 1).' days');
      }
    }

    return new \DateTime('monday this week');
  }

  /**
   * 月曜から日曜までの7日分を返す
   *
   * @param \DateTime $monday
   * @return array  array(array('date' => 'Y-m-d', 'label' => '09/07（月）', 'dow' => '月'), ...)
   */
  public static function days(\DateTime $monday)
  {
    $days = array();

    for ($i = 0; $i < 7; $i++)
    {
      $day = (clone $monday)->modify('+'.$i.' days');

      $days[] = array(
        'date'  => $day->format('Y-m-d'),
        'label' => $day->format('m/d').'（'.static::$labels[$i].'）',
        'dow'   => static::$labels[$i],
      );
    }

    return $days;
  }

  /**
   * 「2026/09/07 〜 09/13」形式の表示用文字列
   *
   * @param \DateTime $monday
   * @return string
   */
  public static function label(\DateTime $monday)
  {
    $sunday = (clone $monday)->modify('+6 days');

    return $monday->format('Y/m/d').' 〜 '.$sunday->format('m/d');
  }

  /**
   * 週の最終日（日曜）を Y-m-d で返す
   *
   * @param \DateTime $monday
   * @return string
   */
  public static function sunday(\DateTime $monday)
  {
    return (clone $monday)->modify('+6 days')->format('Y-m-d');
  }
}
