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
   */
  private static function getAliases($specialtyCode)
  {
    $alias = DB::table('specialty_alias')
      ->join(
        'aliases', 
        'specialty_alias.alias_id',
        '=',
        'aliases.id' 
      )
      //->select('aliases.alias')
      ->where('specialty_alias.specialty_id', '=', $specialtyCode)
      ->get();

    return $alias;
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
    $physicians = $physicians->get()->toArray();
    
    foreach ($physicians as $physician) {
      $aliases = self::getAliases($physician['PrimaryPracticeFocusCode']);
      foreach ($aliases as $alias) {
        if (array_key_exists($alias->alias, $all)) {
          $newCount = $all[$alias->alias]['count'] + 1;
          $all[$alias->alias]['count'] = $newCount;
        } else {
          $all[$alias->alias] = [
            'id' => $alias->id, 
            'alias' => $alias->alias,
            'count' => 1
          ];
        }
      }
    }

    return $all;
  }
  
}
