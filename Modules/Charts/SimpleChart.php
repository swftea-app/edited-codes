<?php
namespace Modules\Charts;

use ConsoleTVs\Charts\Classes\Chartjs\Chart;

class SimpleChart extends Chart {
  public function __construct($ajax = false, $labels = []) {
    parent::__construct();
    if($ajax) {
      $this->load($ajax);
    }
    $this->labels($labels);
    $this->options([
      'tooltip' => [
        'show' => true
      ]
    ]);
  }
}