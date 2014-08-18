#!/usr/bin/php
<?php
error_reporting(E_ALL);
ini_set('display_errors', true);
spl_autoload_register();

/**
 *
 * @author  Kushal Halder
 */


/**
 * Normalize query: remove variable data and replace it with {}
 *
 * @param string $q
 *
 * @return string
 */
function normalize ($q)
{
    $query = $q;
    $query = preg_replace("/\\/\\*.*\\*\\//sU", '', $query);                       // remove multiline comments
    $query = preg_replace("/([\"'])(?:\\\\.|\"\"|''|.)*\\1/sU", "{}", $query);     // remove quoted strings
    $query = preg_replace("/(\\W)(?:-?\\d+(?:\\.\\d+)?)/", "\\1{}", $query);       // remove numbers
    $query = preg_replace("/(\\W)null(?:\\Wnull)*(\\W|\$)/i", "\\1{}\\2", $query); // remove nulls
    $query = str_replace(array("\\n", "\\t", "\\0"), ' ', $query);                 // replace escaped linebreaks
    $query = preg_replace("/\\s+/", ' ', $query);                                  // remove multiple spaces
    $query = preg_replace("/ (\\W)/", "\\1", $query);                              // remove spaces bordering with non-characters
    $query = preg_replace("/(\\W) /", "\\1", $query);                              // --,--
    $query = preg_replace("/\\{\\}(?:,?\\{\\})+/", "{}", $query);                  // repetitive {},{} to single {}
    $query = preg_replace("/\\(\\{\\}\\)(?:,\\(\\{\\}\\))+/", "({})", $query);     // repetitive ({}),({}) to single ({})
    $query = strtolower(trim($query, " \t\n)("));                                  // trim spaces and strtolower
    return $query;
}

/**
 * Output program usage doc and die
 *
 * @param string $msg - describing message
 */
function doc ($msg = null)
{
    file_put_contents('php://stderr', (!is_null($msg) ? ($msg . "\n\n") : '') .

        'MyProfi: mysql log profiler and analyzer

Usage: php run.php [OPTIONS] INPUTFILE

Options:
-top N
	Output only N top queries
-type "query types"
	Output only statistics for the queries of given query types.
	Query types are comma separated words that queries may begin with
-html
	Output statistics in html format
-sample
	Output one sample query per each query pattern to be able to use it
	with EXPLAIN query to analyze its performance
-csv
	Considers an input file to be in csv format
	Note, that if the input file extension is .csv, it is also considered as csv
-slow
	Treats an input file as a slow query log
-sort <CRITERIA>
	Sort output statistics by given <CRITERIA>.
	Works only for slow query log format.
	Possible values of <CRITERIA>: qt_total | qt_avg | qt_max | lt_total | lt_avg | lt_max | rs_total
	 rs_avg | rs_max | re_total | re_avg | re_max,
	 where two-letter prefix stands for "Query time", "Lock time", "Rows sent", "Rows executed"
	 values taken from data provided by slow query log respectively.
	 Suffix after _ character tells MyProfi to take total, maximum or average
	 calculated values.

Example:
	php parser.php -csv -top 10 -type "select, update" general_log.csv
');
    exit;
}

// for debug purposes
if (!isset($argv)) {
    $argv = array(
        __FILE__,
//		'-slow',
        '-sort',
        'qt_total',
        '-top',
        '10',
        'queries.log',
    );
}

$fields = array(
    'qt_total',
    'qt_avg',
    'qt_max',
    'lt_total',
    'lt_avg',
    'lt_max',
    'rs_total',
    'rs_avg',
    'rs_max',
    're_total',
    're_avg',
    're_max',
);

// the last argument always must be an input filename
if (isset($argv[1]))
    $file = array_pop($argv);
else
{
    doc('Error: no input file specified');
}

// get rid of program filename ($argvs[0])
array_shift($argv);

// initialize an object
$myprofi = new myprofi();

$sample = false;

$sort = false;

$html = false;

// iterating through command line options
while (null !== ($com = array_shift($argv)))
{
    switch ($com)
    {
        case '-top':
            if (is_null($top = array_shift($argv)))
                doc('Error: must specify the number of top queries to output');

            if (!($top = (int)$top))
                doc('Error: top number must be integer value');
            $myprofi->top($top);
            break;

        case '-type':
            if (is_null($prefx = array_shift($argv)))
                doc('Error: must specify coma separated list of query types to output');
            $myprofi->types($prefx);
            break;

        case '-sample':
            $myprofi->sample(true);
            $sample = true;
            break;

        case '-html':
            $html = true;
            break;

        case '-csv':
            $myprofi->csv(true);
            break;

        case '-slow':
            $myprofi->slow(true);
            break;

        case '-sort':
            if (is_null($sort = array_shift($argv)))
                doc('Error: must specify criteria to sort by');
            elseif (false === array_search($sort, $fields))
                doc('Unknown sorting field "' . $sort . '"');
            $myprofi->sortby($sort);
            break;
    }
}
if (!$myprofi->slow() && $sort) {
    $sort = false;
    $myprofi->sortby(false);
}

$myprofi->set_input_file($file);
$myprofi->process_queries();

$i = $myprofi->total();
$j = 1;

$tmpl = ($html ? new html_template() : new plain_template());

$tmpl->miniheader();

foreach ($myprofi->get_types_stat() as $type => $num)
{
    $tmpl->minirow($type, $num, 100 * $num / $i);
}

$tmpl->minifooter($i);

$tmpl->mainheader();

while (list($num, $query, $smpl, $stats) = $myprofi->get_pattern_stats())
{
    if ($sort) {
        $n = $stats[$sort];
        $tmpl->mainrow($j, $num, $n, $query, true, $smpl);
    }
    else
    {
        $tmpl->mainrow($j, $num, 100 * $num / $i, $query, false, $smpl);
    }

    $j++;
}

$tmpl->mainfooter(--$j);
