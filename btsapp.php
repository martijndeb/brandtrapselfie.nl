<?php

    require_once('lib/instagram.php');

    class BTSApp
    {

        function __construct()
        {
            $list = glob( Instagram::CACHEFOLDER . "*.json" );
            $oldCount = count($list);

            if (file_exists(".DEV")) {
                $oldCount = 0;
            }

            $i = new Instagram;
            $i  ->fetchJSON( )
                ->parse();

            $list = glob( Instagram::CACHEFOLDER . "*.json" );

            if ( $oldCount <> count( $list ) ) {
                $nodes = array();

                foreach( $list as $fn )
                {
                    $n = json_decode( file_get_contents( $fn ) );
                    if ( isset( $n->code ) && isset( $n->date ) ) {
                        $nodes[] = $n;
                    }
                }

                usort( $nodes, function( $a, $b ) {

                    return (float) $a->date != (float) $b->date
                            ? (float) $a->date > (float) $b->date
                                ? -1 : 1
                            : 0 ;

                } );

                $template = file_get_contents( 'tpl/front.tpl' );
                $btemplate = file_get_contents( 'tpl/block.tpl' );
                $blocks = "";

                foreach( $nodes as $node )
                {
                    $block = $btemplate;
                    $block = str_replace("{IMAGE}", "data/" . $node->code . "_320.jpg", $block);
                    $block = str_replace("{CAPTION}", $node->caption, $block);
                    $block = str_replace("{CODE}", $node->code, $block);
                    $block = str_replace("{TAGMANAGER}", file_get_contents(".tagmanagers"), $block);

                    if (!file_exists("data/" . $node->code . "_320.jpg")) {
                        `convert -strip -filter Lanczos -interlace Plane -sampling-factor 4:2:0 -define jpeg:dct-method=float -quality 75% -geometry 320x www/data/{$node->code}.jpg www/data/{$node->code}_320.jpg`;
                    }

                    $blocks .= $block;
                }

                $template = str_replace('{BLOCKS}', $blocks, $template);

                file_put_contents( 'www/index.html' , $template );

            }

        }

    }

    new BTSApp;

?>