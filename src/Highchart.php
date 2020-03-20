<?php
/**
*
* Copyright 2012-2012 Portugalmail Comunicações S.A (http://www.portugalmail.net/)
*
* See the enclosed file LICENCE for license information (GPLv3). If you
* did not receive this file, see http://www.gnu.org/licenses/gpl-3.0.html.
*
* @author Gonçalo Queirós <mail@goncaloqueiros.net>
*/

namespace Ghunti\HighchartsPHP;

use Ghunti\HighchartsPHP\HighchartOption;
use Ghunti\HighchartsPHP\HighchartOptionRenderer;

/**
 * @property object plotOptions
 * @property object yAxis
 * @property object chart
 * @property object title
 * @property object xAxis
 * @property array series
 * @property object subtitle
 * @property object tooltip
 */
class Highchart implements \ArrayAccess
{
    //The chart type.
    //A regullar higchart
    const HIGHCHART = 0;
    //A highstock chart
    const HIGHSTOCK = 1;
    // A Highchart map
    const HIGHMAPS = 2;

    //The js engine to use
    const ENGINE_JQUERY = 10;
    const ENGINE_MOOTOOLS = 11;
    const ENGINE_PROTOTYPE = 12;

    /**
     * The chart options
     *
     * @var array
     */
    protected $_options = array();

    /**
     * The chart type.
     * Either self::HIGHCHART or self::HIGHSTOCK
     *
     * @var int
     */
    protected $_chartType;

    /**
     * The javascript library to use.
     * One of ENGINE_JQUERY, ENGINE_MOOTOOLS or ENGINE_PROTOTYPE
     *
     * @var int
     */
    protected $_jsEngine;

    /**
     * Array with keys from extra scripts to be included
     *
     * @var array
     */
    protected $_extraScripts = array();

    /**
     * Any configurations to use instead of the default ones
     *
     * @var array An array with same structure as the config.php file
     */
    protected $_confs = array();

    /**
     * Any theme to use instead of the default one
     * Basically, copy everything below the `import Highcharts from '../parts/Globals.js`';` statement
     * from example themes like the one at https://www.highcharts.com/demo/line-basic/dark-unica
     *
     * @var string
     */
    protected $theme = '';

    /**
     * Use the themes at https://www.highcharts.com/demo/line-basic/dark-unica
     *
     * @var string
     */
    protected $useDarkTheme = false;

    /**
     * Clone Highchart object
     */
    public function __clone()
    {
        foreach ($this->_options as $key => $value)
        {
            $this->_options[$key] = clone $value;
        }
    }

    /**
     * The Highchart constructor
     *
     * @param int $chartType The chart type (Either self::HIGHCHART or self::HIGHSTOCK)
     * @param int $jsEngine  The javascript library to use
     *                       (One of ENGINE_JQUERY, ENGINE_MOOTOOLS or ENGINE_PROTOTYPE)
     */
    public function __construct($chartType = self::HIGHCHART, $jsEngine = self::ENGINE_JQUERY)
    {
        $this->_chartType = is_null($chartType) ? self::HIGHCHART : $chartType;
        $this->_jsEngine = is_null($jsEngine) ? self::ENGINE_JQUERY : $jsEngine;
        //Load default configurations
        $this->setConfigurations();
    }

    /**
     * Override default configuration values with the ones provided.
     * The provided array should have the same structure as the config.php file.
     * If you wish to override a single value you would pass something like:
     *     $chart = new Highchart();
     *     $chart->setConfigurations(array('jQuery' => array('name' => 'newFile')));
     *
     * @param array $configurations The new configuration values
     */
    public function setConfigurations($configurations = array())
    {
        include __DIR__ . DIRECTORY_SEPARATOR . "config.php";
        $this->_confs = array_replace_recursive($jsFiles, $configurations);
    }

    /**
     * Render the chart options and returns the javascript that
     * represents them
     *
     * @return string The javascript code
     */
    public function renderOptions()
    {
        return HighchartOptionRenderer::render($this->_options);
    }

    /**
     * Render the chart and returns the javascript that
     * must be printed to the page to create the chart
     *
     * @param string $varName The javascript chart variable name
     * @param string $callback The function callback to pass
     *                         to the Highcharts.Chart method
     * @param boolean $withScriptTag It renders the javascript wrapped
     *                               in html script tags
     *
     * @return string The javascript code
     */
    public function render($varName = null, $callback = null, $withScriptTag = false)
    {
        $result = '';
        if (!is_null($varName)) {
            $result = "$varName = ";
        }

        $result .= 'new Highcharts.';
        if ($this->_chartType === self::HIGHCHART) {
            $result .= 'Chart(';
        } elseif ($this->_chartType === self::HIGHMAPS) {
            $result .= 'Map(';
        } else {
            $result .= 'StockChart(';
        }

        $result .= $this->renderOptions();
        $result .= is_null($callback) ? '' : ", $callback";
        $result .= ');';

        if ($withScriptTag) {
            $result = '<script type="text/javascript">' . $result . '</script>';
        }

        return $result;
    }

    /**
     * Finds the javascript files that need to be included on the page, based
     * on the chart type and js engine.
     * Uses the conf.php file to build the files path
     *
     * @return array The javascript files path
     */
    public function getScripts()
    {
        $scripts = array();
        switch ($this->_jsEngine) {
            case self::ENGINE_JQUERY:
                $scripts[] = $this->_confs['jQuery']['path'] . $this->_confs['jQuery']['name'];
                break;

            case self::ENGINE_MOOTOOLS:
                $scripts[] = $this->_confs['mootools']['path'] . $this->_confs['mootools']['name'];
                if ($this->_chartType === self::HIGHCHART) {
                    $scripts[] = $this->_confs['highchartsMootoolsAdapter']['path'] . $this->_confs['highchartsMootoolsAdapter']['name'];
                } else {
                    $scripts[] = $this->_confs['highstockMootoolsAdapter']['path'] . $this->_confs['highstockMootoolsAdapter']['name'];
                }
                break;

            case self::ENGINE_PROTOTYPE:
                $scripts[] = $this->_confs['prototype']['path'] . $this->_confs['prototype']['name'];
                if ($this->_chartType === self::HIGHCHART) {
                    $scripts[] = $this->_confs['highchartsPrototypeAdapter']['path'] . $this->_confs['highchartsPrototypeAdapter']['name'];
                } else {
                    $scripts[] = $this->_confs['highstockPrototypeAdapter']['path'] . $this->_confs['highstockPrototypeAdapter']['name'];
                }
                break;

        }

        switch ($this->_chartType) {
            case self::HIGHCHART:
                $scripts[] = $this->_confs['highcharts']['path'] . $this->_confs['highcharts']['name'];
                break;

            case self::HIGHSTOCK:
                $scripts[] = $this->_confs['highstock']['path'] . $this->_confs['highstock']['name'];
                break;

            case self::HIGHMAPS:
                $scripts[] = $this->_confs['highmaps']['path'] . $this->_confs['highmaps']['name'];
                break;
        }

        //Include scripts with keys given to be included via includeExtraScripts
        if (!empty($this->_extraScripts)) {
            foreach ($this->_extraScripts as $key) {
                $scripts[] = $this->_confs['extra'][$key]['path'] . $this->_confs['extra'][$key]['name'];
            }
        }

        return $scripts;
    }

    /**
     * Prints javascript script tags for all scripts that need to be included on page
     *
     * @param boolean $return if true it returns the scripts rather then echoing them
     */
    public function printScripts($return = false)
    {
        $scripts = '';
        foreach ($this->getScripts() as $script) {
            $scripts .= '<script type="text/javascript" src="' . $script . '"></script>';
        }

        if ($return) {
            return $scripts;
        }
        else {
            echo $scripts;
        }
    }

    /**
     * Manually adds an extra script to the extras
     *
     * @param string $key      key for the script in extra array
     * @param string $filepath path for the script file
     * @param string $filename filename for the script
     */
    public function addExtraScript($key, $filepath, $filename)
    {
        $this->_confs['extra'][$key] = array('name' => $filename, 'path' => $filepath);
    }

    /**
     * Signals which extra scripts are to be included given its keys
     *
     * @param array $keys extra scripts keys to be included
     */
    public function includeExtraScripts(array $keys = array())
    {
        $this->_extraScripts = empty($keys) ? array_keys($this->_confs['extra']) : $keys;
    }

    /**
     * Global options that don't apply to each chart like lang and global
     * must be set using the Highcharts.setOptions javascript method.
     * This method receives a set of HighchartOption and returns the
     * javascript string needed to set those options globally
     *
     * @param HighchartOption The options to create
     *
     * @return string The javascript needed to set the global options
     */
    public static function setOptions($options)
    {
        //TODO: Check encoding errors
        $option = json_encode($options->getValue());
        return "Highcharts.setOptions($option);";
    }

    /**
     * @return array
     */
    public function getOptions(){
        return $this->_options;
    }

    public function __set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->_options[$offset] = new HighchartOption($value);
    }

    public function offsetExists($offset)
    {
        return isset($this->_options[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_options[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->_options[$offset])) {
            $this->_options[$offset] = new HighchartOption();
        }
        return $this->_options[$offset];
    }

    public function getTitleText()
    {
        $title = $this->offsetGet('title');
        return $title->offsetGet('text')->getValue();
    }

    private function getRenderToElementId()
    {
        $chart = $this->offsetGet('chart');
        return $chart->offsetGet('renderTo')->getValue();
    }
    /**
     * Use the themes at https://www.highcharts.com/demo/line-basic/dark-unica
     *
     * @param bool $useDarkTheme
     */
    public function setUseDarkTheme($useDarkTheme = true){
        $this->useDarkTheme = $useDarkTheme;
    }
    /**
     * Any theme to use instead of the default one
     * Basically, copy everything below the `import Highcharts from '../parts/Globals.js`';` statement
     * from example themes like the one at https://www.highcharts.com/demo/line-basic/dark-unica
     *
     * @param string $theme
     */
    public function setTheme($theme){
        $this->theme = $theme;
    }

    protected function getTheme()
    {
        if(!empty($this->theme)){return $this->theme;}
        if($this->useDarkTheme){
            return $this->getDarkTheme();
        }
        return null;
    }

    public function getHtml()
    {
        $scripts = $this->printScripts();
        $rendered = $this->render();
        $id = $this->getRenderToElementId();
        $title = $this->getTitleText();
        $theme = $this->getTheme();
        return "
            <html>
                <head>
                    <title>$title</title>
                    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
                   $scripts
                </head>
                <body>
                <div id=\"$id\"></div>
                    $theme
                <script type=\"text/javascript\">
                    window.chart = $rendered;
                </script>
                </body>
            </html>
        ";
    }
    private function getDarkTheme()
    {
        return "
            <script type=\"text/javascript\">
                Highcharts.createElement('link', {
                    href: 'https://fonts.googleapis.com/css?family=Unica+One',
                    rel: 'stylesheet',
                    type: 'text/css'
                }, null, document.getElementsByTagName('head')[0]);
                Highcharts.theme = {
                    colors: ['#2b908f', '#90ee7e', '#f45b5b', '#7798BF', '#aaeeee', '#ff0066',
                        '#eeaaee', '#55BF3B', '#DF5353', '#7798BF', '#aaeeee'],
                    chart: {
                        backgroundColor: {
                            linearGradient: { x1: 0, y1: 0, x2: 1, y2: 1 },
                            stops: [
                                [0, '#2a2a2b'],
                                [1, '#3e3e40']
                            ]
                        },
                        style: {
                            fontFamily: '\'Unica One\', sans-serif'
                        },
                        plotBorderColor: '#606063'
                    },
                    title: {
                        style: {
                            color: '#E0E0E3',
                            textTransform: 'uppercase',
                            fontSize: '20px'
                        }
                    },
                    subtitle: {
                        style: {
                            color: '#E0E0E3',
                            textTransform: 'uppercase'
                        }
                    },
                    xAxis: {
                        gridLineColor: '#707073',
                        labels: {
                            style: {
                                color: '#E0E0E3'
                            }
                        },
                        lineColor: '#707073',
                        minorGridLineColor: '#505053',
                        tickColor: '#707073',
                        title: {
                            style: {
                                color: '#A0A0A3'
                            }
                        }
                    },
                    yAxis: {
                        gridLineColor: '#707073',
                        labels: {
                            style: {
                                color: '#E0E0E3'
                            }
                        },
                        lineColor: '#707073',
                        minorGridLineColor: '#505053',
                        tickColor: '#707073',
                        tickWidth: 1,
                        title: {
                            style: {
                                color: '#A0A0A3'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.85)',
                        style: {
                            color: '#F0F0F0'
                        }
                    },
                    plotOptions: {
                        series: {
                            dataLabels: {
                                color: '#F0F0F3',
                                style: {
                                    fontSize: '20px'
                                }
                            },
                            marker: {
                                lineColor: '#333'
                            }
                        },
                        boxplot: {
                            fillColor: '#505053'
                        },
                        candlestick: {
                            lineColor: 'white'
                        },
                        errorbar: {
                            color: 'white'
                        }
                    },
                    legend: {
                        backgroundColor: 'rgba(0, 0, 0, 0.5)',
                        itemStyle: {
                            color: '#E0E0E3'
                        },
                        itemHoverStyle: {
                            color: '#FFF'
                        },
                        itemHiddenStyle: {
                            color: '#606063'
                        },
                        title: {
                            style: {
                                color: '#C0C0C0'
                            }
                        }
                    },
                    credits: {
                        style: {
                            color: '#666'
                        }
                    },
                    labels: {
                        style: {
                            color: '#707073'
                        }
                    },
                    drilldown: {
                        activeAxisLabelStyle: {
                            color: '#F0F0F3'
                        },
                        activeDataLabelStyle: {
                            color: '#F0F0F3'
                        }
                    },
                    navigation: {
                        buttonOptions: {
                            symbolStroke: '#DDDDDD',
                            theme: {
                                fill: '#505053'
                            }
                        }
                    },
                    // scroll charts
                    rangeSelector: {
                        buttonTheme: {
                            fill: '#505053',
                            stroke: '#000000',
                            style: {
                                color: '#CCC'
                            },
                            states: {
                                hover: {
                                    fill: '#707073',
                                    stroke: '#000000',
                                    style: {
                                        color: 'white'
                                    }
                                },
                                select: {
                                    fill: '#000003',
                                    stroke: '#000000',
                                    style: {
                                        color: 'white'
                                    }
                                }
                            }
                        },
                        inputBoxBorderColor: '#505053',
                        inputStyle: {
                            backgroundColor: '#333',
                            color: 'silver'
                        },
                        labelStyle: {
                            color: 'silver'
                        }
                    },
                    navigator: {
                        handles: {
                            backgroundColor: '#666',
                            borderColor: '#AAA'
                        },
                        outlineColor: '#CCC',
                        maskFill: 'rgba(255,255,255,0.1)',
                        series: {
                            color: '#7798BF',
                            lineColor: '#A6C7ED'
                        },
                        xAxis: {
                            gridLineColor: '#505053'
                        }
                    },
                    scrollbar: {
                        barBackgroundColor: '#808083',
                        barBorderColor: '#808083',
                        buttonArrowColor: '#CCC',
                        buttonBackgroundColor: '#606063',
                        buttonBorderColor: '#606063',
                        rifleColor: '#FFF',
                        trackBackgroundColor: '#404043',
                        trackBorderColor: '#404043'
                    }
                };
                // Apply the theme
                Highcharts.setOptions(Highcharts.theme);
            </script>
        ";
    }
}
