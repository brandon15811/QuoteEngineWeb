<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
/*$twig = new Twig_Environment($loader, array(
    'debug' => true,
    // ...
));
$twig->addExtension(new Twig_Extension_Debug());*/

if (empty($_GET['channel']) || !in_array($_GET['channel'], $channels)) {
    $channel = $channels['0'];
} else {
    $channel = $_GET['channel'];
}

$filter = null;
if (!empty($_GET['filter'])) {
    #TODO: Filter search terms
    $filter = $_GET['filter'];
}

$options = array(
    'options' => array(
        'min_range' => 1,
        'max_range' => ($sdb->count_quotes($channel, $filter) / 100) + 1,
        'default' => 1
    )
);

$currentPage = 1;
if (!empty($_GET['page'])) {
    $currentPage = filter_var($_GET['page'], FILTER_VALIDATE_INT, $options);
}

$quotes = $sdb->get_quotes($channel, $filter, $currentPage, $quotesPerPage);
$quote_count = $sdb->count_quotes($channel, $filter);

$stats = array(
    'quoted' => array(),
    'quoters' => array()
);

$processedQuotes = array();
foreach ($quotes as $quote) {
    if (empty($stats['quoters'][$quote['nick']])) {
        $stats['quoters'][$quote['nick']] = 1;
    } else {
        $stats['quoters'][$quote['nick']]++;
    }

    //Strip colors (from http://stackoverflow.com/a/970723 )
    $newQuotes = preg_replace("/\x08|\x1f|\x02|\x12|\x0f|\x16|\x03(?:\d{1,2}(?:,\d{1,2})?)?/u", "", $quote['quote']);

    //Based off of http://stackoverflow.com/a/5163309
    $nickRegex = '\@?\+?([a-zA-Z\[\]\\`_\^\{\|\}][a-zA-Z0-9\[\]\\`_\^\{\|\}-]{1,31})';
    $baseMessageRegex = '< ?'.$nickRegex.'>';
    $messageRegex = '/^ {0,3}'.$baseMessageRegex.'/';
    $baseActionRegex = '\* {1,2}'.$nickRegex;
    $actionRegex = '/^'.$baseActionRegex.'/i';

    $newQuotes = preg_replace("/^[0-9:]{0,5}/", "", $newQuotes, 1);
    $newQuotes = preg_replace("/ \| {0,3}[0-9:]{0,5} $baseMessageRegex/", "\n<\$1>", $newQuotes);
    $newQuotes = preg_replace("/ \| $baseActionRegex/", "\n\$1 ", $newQuotes);
    $tempQuotes = array(
        'quote' => array()
    );
    foreach(explode("\n", $newQuotes) as $newQuote) {
        $tempQuote = array();
        if (preg_match($messageRegex, $newQuote, $matches) === 1 ||
            preg_match($actionRegex, $newQuote, $matches) === 1) {
                //prevar_dump($matches);
                $tempQuote['nick'] = $matches[0];
                //Only replace first occurrence
                $tempQuote['message'] = implode("", explode($tempQuote['nick'], $newQuote, 2));
                if (empty($stats['quoted'][$matches[1]])) {
                    $stats['quoted'][$matches[1]] = 1;
                } else {
                    $stats['quoted'][$matches[1]]++;

                }
        } else {
                $tempQuote['message'] = $newQuote;
                //prevar_dump($tempQuote);
        }
        $tempQuotes['quote'][] = $tempQuote;
    }
    $tempQuotes = array_merge($quote, $tempQuotes);
    $processedQuotes[] = $tempQuotes;
}

//prevar_dump($processedQuotes);

arsort($stats['quoted']);
arsort($stats['quoters']);
$stats['quoted'] = array_slice($stats['quoted'], 0, 5);
$stats['quoters'] = array_slice($stats['quoters'], 0, 5);

$page = array(
  'quotes' => $processedQuotes,
  'channels' => $channels,
  'activeChannel' => $channel,
  'stats' => $stats,
  'quoteCount' => $quote_count,
  'filter' => $filter,
  'currentPage' => $currentPage
);

echo $twig->render('index.html', $page);

?>
