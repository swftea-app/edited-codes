<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class DashboardController extends Controller {
    public function index() {
        $enabled_modules = Module::allEnabled();
        $data['charts'] = [];
        foreach ($enabled_modules as $enabled_module) {
          $config = config($enabled_module->getLowerName());
          if(array_key_exists('charts', $config) && array_key_exists('dashboard', $config['charts'])) {
            // check for permissions
            foreach ($config['charts']['dashboard'] as $chart_key => $chart) {
              if(!auth()->user()->can($chart['can'])) {
                continue;
              }
              $simple_chart = new $chart['chart_class'](route('charts.api',['module' => $enabled_module->getLowerName(),'type' => $chart_key]), $chart['labels']);
              $data['charts'][] = (object)[
                'size' => $chart['size'],
                'label' => $chart['label'],
                'content' => $simple_chart
              ];
            }
          }
        }
        return view('dashboard::index')->with($data);
    }
}
