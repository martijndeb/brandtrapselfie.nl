<?php

    date_default_timezone_set("Europe/Amsterdam");
    setlocale(LC_ALL,'nl_NL') or setlocale(LC_ALL,'nld_NLD');

    require_once('lib/instagram.php');

    class BTSApp
    {

        protected $dayIndex = array(0,0,0,0,0,0,0);
        protected $index = array();
        protected $totalcount = 0;
        protected $dagdeel = array(
            "ochtend" => 0,
            "middag" => 0,
            "avond" => 0,
            "nacht" => 0
        );

        // Smerig maar locale staat niet goed
        protected $dayString = array('zondag','maandag','dinsdag','woensdag','donderdag','vrijdag','zaterdag');
        protected $monthString = array('januari','februari','maart','april','mei','juni','juli','augustus','september','oktober','november','december');

        function __construct()
        {
            $convertcmd = trim(`which convert`);

            $list = glob( Instagram::CACHEFOLDER . "*.json" );
            $oldCount = count($list);
            $ignoreList = array();

            if (file_exists(".DEV")) {
                $oldCount = 0;
            }

            if (file_exists(".IGNORE")) {
                $tmp = trim(file_get_contents(".IGNORE"));
                $tmp2 = explode("\n", $tmp);
                foreach ($tmp2 as $ignore) {
                    $ignore = trim($ignore);
                    if (!empty($ignore)) {
                        $ignoreList[] = $ignore;
                    }
                }
            }

            $i = new Instagram;
            $i  ->fetchJSON( )
                ->parse( $ignoreList );

            $rsscontent = file_get_contents("tpl/rss.tpl");
            $feeditems = "";

            $list = glob( Instagram::CACHEFOLDER . "*.json" );

            if ( $oldCount <> count( $list ) ) {
                $nodes = array();

                foreach( $list as $fn )
                {
                    $n = json_decode( file_get_contents( $fn ) );
                    if ( isset( $n->code ) && null !== @m($n->date,$n->taken_at_timestamp) ) {
                        $nodes[] = $n;
                    }
                }

                usort( $nodes, function( $a, $b ) {

                    return (float) @m($a->date,$a->taken_at_timestamp) != (float) @m($b->date,$b->taken_at_timestamp)
                            ? (float) @m($a->date,$a->taken_at_timestamp) > (float) @m($b->date,$b->taken_at_timestamp)
                                ? -1 : 1
                            : 0 ;

                } );

                $template = file_get_contents( 'tpl/front.tpl' );
                $btemplate = file_get_contents( 'tpl/block.tpl' );
                $rtemplate = file_get_contents( 'tpl/row.tpl' );
                $blocks = "";
                $rows = "";
                $table = array(
                );

                foreach( $nodes as $node )
                {
                    if (in_array($node->code, $ignoreList)) {
                        continue;
                    }

                    $block = $btemplate;
                    $block = str_replace("{IMAGE}", "data/" . $node->code . "_320.jpg", $block);
                    $block = str_replace("{CAPTION}", @m($node->caption,$node->edge_media_to_caption->edges[0]->node->text), $block);
                    $block = str_replace("{CODE}", $node->code, $block);
                    $block = str_replace("{LIKES}", @m($node->likes->count,$node->edge_liked_by->count), $block);
                    $block = str_replace("{LIKESSTRING}", @m($node->likes->count,$node->edge_liked_by->count) > 0 ? "<span style='color: #FF0000;'>" . @m($node->likes->count,$node->edge_liked_by->count) . " &#9829;</span> " : "", $block);
                    $bwidth = 320; $bheight = 320;

                    if (!file_exists("data/" . $node->code . "_320.jpg")) {
                        shell_exec( $convertcmd . " -strip -filter Lanczos -interlace Plane -sampling-factor 4:2:0 -define jpeg:dct-method=float -quality 85% -geometry 320x www/data/{$node->code}.jpg www/data/{$node->code}_320.jpg" );
                    }

                    list($width, $height, $type, $attr) = getimagesize("www/data/{$node->code}_320.jpg");
                    $bheight = $height;

                    $block = str_replace("{WIDTH}", $width, $block);
                    $block = str_replace("{HEIGHT}", $height, $block);


                    if (isset($node->date) && is_numeric(@m($node->date,$node->taken_at_timestamp))) {
                        $date = new DateTime();
                        $date->setTimestamp(@m($node->date,$node->taken_at_timestamp));

                        $day = $date->format('w');
                        $month = $date->format('n') - 1;
                        $year = $date->format('Y');
                        $dagdeel = "nacht";
                        $hour = $date->format('G');

                        if ($hour >= 7) {
                            $dagdeel = "ochtend";
                        }

                        if ($hour >= 12) {
                            $dagdeel = "middag";
                        }

                        if ($hour >= 17) {
                            $dagdeel = "avond";
                        }

                        $this->dagdeel[$dagdeel]++;

                        $block = str_replace("{DAYPART}", $dagdeel, $block);
                        $block = str_replace("{DAY}", $this->dayString[$day], $block);

                        $this->dayIndex[$day]++;

                        if (!isset($this->index[$year])) {
                            $this->index[$year] = array();
                            for ($dl = 0; $dl < 12; $dl++) {
                                $this->index[$year][$dl] = array();
                            }
                        }

                        if (!isset($this->index[$year][$month])) {
                            $this->index[$year][$month] = array();
                            for ($dl = 0; $dl < $date->format('t'); $dl++) {
                                $this->index[$year][$month][$dl] = array();
                            }
                        }

                        $this->index[$year][$month][$day][] = $date;

                    $blocks .= $block;


                    }

                    $this->totalcount++;

                    $feeditems .= "<item>
    <title>" . $node->code . "</title>
    <link>http://brandtrapselfie.nl/data/". $node->code .".jpg</link>
    <description>" . @m($node->caption,$node->edge_media_to_caption->edges[0]->node->text) . "</description>
    <enclosure url='http://brandtrapselfie.nl/data/". $node->code .".jpg' type='image/jpeg' />
  </item>";
                }

                $prev = 0;
                $mostactive = 0;

                for ($i = 0; $i < 7; $i++) {
                    $cur = $this->dayIndex[$i];
                    if ($cur > $prev) {
                        $mostactive = $i;
                    }
                    $prev = $cur;
                }

                $table["<b>Meest actieve dag</b>"] = $this->dayString[$mostactive];
                for ($i = 0; $i < 7; $i++) {
                    $check = '<input type="checkbox" id="dag-' . $i . '" checked data-filter="dag" data-value="' . $this->dayString[$i] . '"><label for="dag-' . $i . '"> ';
                    $table[" - " . $this->dayString[$i]] = $check . $this->dayIndex[$i] . "</label>";
                }

                $prev = 0;
                $mostactive = 0;
                $vals = array_values($this->dagdeel);
                $keys = array_keys($this->dagdeel);

                for ($i = 0; $i < 4; $i++) {
                    $cur = $vals[$i];
                    if ($cur > $prev) {
                        $mostactive = $i;
                    }
                    $prev = $cur;
                }

                $table["<b>Meest actieve dagdeel</b>"] = $keys[$mostactive];

                for ($i = 0; $i < 4; $i++) {
                    $check = '<input type="checkbox" id="dagdeel-' . $i . '" checked data-filter="dagdeel" data-value="' . $keys[$i] . '"><label for="dagdeel-' . $i . '"> ';
                    $table[" - " . $keys[$i]] = $check . $vals[$i] . "</label>";
                }

                foreach ($table as $k=>$v){
                    $row = $rtemplate;
                    $row = str_replace("{KEY}", $k, $row);
                    $row = str_replace("{VALUE}", $v, $row);
                    $rows .= $row;
                }

                $template = str_replace('{ROWS}', $rows, $template);
                $template = str_replace("{TOTALCOUNT}", $this->totalcount, $template);
                $template = str_replace('{BLOCKS}', $blocks, $template);
                $template = str_replace("{TAGMANAGERS}", file_get_contents(".tagmanagers"), $template);
                $rsscontent = str_replace("{FEEDITEMS}", $feeditems, $rsscontent);
                file_put_contents( 'www/index.html' , $template );
                file_put_contents( 'www/feed.rss', $rsscontent );

            }

        }

    }

    function m(...$args) {
        foreach($args as $arg) {
            if (null !== $arg && @!empty($arg)) {
                return $arg;
            }
        }
    }

    new BTSApp;

?>
