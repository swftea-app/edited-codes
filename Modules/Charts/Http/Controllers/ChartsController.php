<?php

namespace Modules\Charts\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Charts\SimpleChart;

class ChartsController extends Controller {
  public function chartDataApi($module, $type) {
    $config = config($module);
    if(array_key_exists('charts', $config) && array_key_exists('dashboard', $config['charts']) && array_key_exists($type, $config['charts']['dashboard'])) {
      $config = $config['charts']['dashboard'][$type];
      $chart = new $config['chart_class'];
      $data = [];
      foreach ($config['dataset'] as $dataset_key => $value) {
        $data[$dataset_key] = [];
        $model = $value['model'];
        if(!auth()->user()->can($value['can'])) {
          continue;
        }
        for ($decreasing_count = $config['count'] - 1; $decreasing_count >= 0; $decreasing_count--) {
          $params = explode(",", $value['params']);
          if (count($params) == 1) {
            $first_param = $params[0];
            $count_value = $model::{$value['method']}($$first_param);
          } elseif (count($params) == 2) {
            $first_param = $params[0];
            $second_param = $params[1];
            $count_value = $model::{$value['method']}($$first_param, $$second_param);
          } else {
            $count_value = 0;
          }
          $data[$dataset_key][] = $count_value;
        }
        $chart->dataset($value['label'], $value['graph'], $data[$dataset_key])->options($value['options']);
      }
      return $chart->api();
    } else {
      return abort(404);
    }
  }
}
