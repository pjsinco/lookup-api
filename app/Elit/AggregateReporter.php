<?php

namespace Elit;
use App\Alias;
use DB;

/**
 * 
 */
class AggregateReporter
{
  
  public static function report($physicians, $requestedAliasId = null)
  {
    $nearest = $physicians->min('distance');
    $farthest = $physicians->max('distance');
    $aliases = self::countAliases($physicians, $requestedAliasId);

    return [
      'nearest' => $nearest, 
      'farthest' => $farthest, 
      'aliases' => $aliases,
      'gender' => [ 
        'm' => $physicians->get()->where('Gender', 'M')->count(),
        'f' => $physicians->get()->where('Gender', 'F')->count(),
      ],
    ];
  }

  /**
   * Get aliases for a specialty
   *
   * @param array $specialtyCodes The specialty codes to look up
   */
  public static function getAliases($specialtyCodes)
  {
    $alias = DB::table('specialty_alias')
      ->join(
        'aliases', 
        'specialty_alias.alias_id',
        '=',
        'aliases.id' 
      )
      //->select('aliases.alias')
      ->whereIn('specialty_alias.specialty_id', $specialtyCodes)
      ->get();

    return $alias;
  }

  private static function countAliasesSmarterly($physicians, $requestedAliasId)
  {

  }

  private static function countAliases($physicians, $requestedAliasId)
  {
    if ($requestedAliasId) {
      $alias = Alias::find($requestedAliasId);
      return [
        [ 
          'id' => $alias->id, 
          'alias' => $alias->alias, 
          'count' => $physicians->count(),
        ],
      ];
    }

    $all = [];
    $physiciansArray = $physicians->get()->toArray();

    foreach ($physiciansArray as $physician) {
      // start make-believe code
      $physAliases = [];

      array_push(
        $physAliases,
        $physician['alias_1'], 
        $physician['alias_2'], 
        $physician['alias_3'],
        array_key_exists('alias_4', $physician) ? $physician['alias_4'] : null,
        array_key_exists('alias_5', $physician) ? $physician['alias_5'] : null,
        array_key_exists('alias_6', $physician) ? $physician['alias_6'] : null
      );

      //$physAliases = self::getAliases($physician['PrimaryPracticeFocusCode']);
      // end make-believe code
      
      foreach ($physAliases as $alias) {
        if (!empty($alias)) {
          if (array_key_exists($alias, $all)) {
            $newCount = $all[$alias]['count'] + 1;
            $all[$alias]['count'] = $newCount;
          } else {
            $all[$alias]['count'] = 1;
            $all[$alias]['id'] = $alias;
          }
        }
      }

//      foreach ($physAliases as $alias) {
//        if (array_key_exists($alias->alias, $all)) {
//          $newCount = $all[$alias->alias]['count'] + 1;
//          $all[$alias->alias]['count'] = $newCount;
//        } else {
//          $all[$alias->alias] = [
//            'id' => $alias->id, 
//            'alias' => $alias->alias,
//            'count' => 1
//          ];
//        }
      //}
    }

    return self::translateAliases($all);
  }

  /**
   * Convert array of [[alias_id => count]] items into 
   * an array suitable for returning in the meta entry.
   *
   * @param array
   * @return array
   */
  private static function translateAliases($aliasCounts)
  {
    $aliasesForMeta = [];
    foreach ($aliasCounts as $aliasCount) {
      $a = Alias::find($aliasCount['id']);
      $aliasesForMeta[$a->alias] = [
        'id' => $a->id,
        'alias' => $a->alias,
        'count' => $aliasCount['count'],
      ];
    }

    return $aliasesForMeta;
  }

  
}
