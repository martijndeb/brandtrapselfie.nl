<?php

    class Instagram
    {
        const URL = 'https://www.instagram.com/explore/tags/brandtrapselfie/?max_id=1307195269398286176'; // Append ?max_id=1096373026222059918 for paging (input highest id for a page).
        const CACHEFOLDER = 'cache/';
        const CACHEFILE = 'cache/brandtrapselfie.json';
        const CACHETIMEOUT = 900;
        const DATAFOLDER = 'www/data/';

        public $unsortedNodes = array();
        protected $data;

        function __construct ()
        {

        }

        public function fetchJSON( )
        {

            if  (   is_file( self::CACHEFILE ) &&
                    (
                        filemtime( self::CACHEFILE ) >
                        (
                            time() - self::CACHETIMEOUT
                        )
                    )
                )
            {
                $this->data = json_decode( file_get_contents( self::CACHEFILE ) );
            }
            else
            {
                $html = file_get_contents( self::URL );
                $doc = new DOMDocument();
                libxml_use_internal_errors( true );
                $doc->loadHTML( $html );
                libxml_use_internal_errors( false );

                $xpath = new DOMXPath( $doc );
                $tags = $xpath->query( '//script' );

                $jsString = false;
                foreach( $tags as $tag )
                {
                    $nodeVal = (string) $tag->nodeValue;
                    $scriptPos = strpos( $nodeVal, 'window._sharedData' );

                    if ( $scriptPos !== false && strlen($nodeVal) > 100 ) {
                        $jsString = $nodeVal;
                        continue;
                    }
                }

                if ( !is_string($jsString) ) {
                    echo "window._sharedData not found";
                    exit;
                }

                $jsString = str_replace( 'window._sharedData = ', '', $jsString );
                $jsString = substr_replace( $jsString, '', -1, 1 );

                file_put_contents( self::CACHEFILE, $jsString );

                $this->data = json_decode( $jsString );

            }

            return $this;

        }

        public function parse($ignoreList)
        {
            foreach( $this->data->entry_data->TagPage[0]->graphql->hashtag->edge_hashtag_to_media->edges as $nodeContainer )
            {
                if ( isset($nodeContainer->node) ) {
                    $node = $nodeContainer->node;
                    $node->code = $node->shortcode;
                    if ( $node->is_video === false && !in_array($node->code, $ignoreList) )
                    {
                        if ( !file_exists( self::CACHEFOLDER . $node->code . ".json" ) )
                        {
                            file_put_contents( self::CACHEFOLDER . $node->code . ".json", (string) json_encode( $node ) );
                        }

                        $this->unsortedNodes[] = $node;

                        if ( !file_exists( self::DATAFOLDER . $node->code . ".jpg" ) ) {
                            $img = file_get_contents( $node->display_url );
                            file_put_contents( self::DATAFOLDER . $node->code . ".jpg", $img );
                        }

                        if ( !file_exists( self::DATAFOLDER . $node->code . "_thumb.jpg" ) ) {
                            $img = file_get_contents( $node->thumbnail_src );
                            file_put_contents( self::DATAFOLDER . $node->code . "_thumb.jpg", $img );
                        }

                    }
                }
            }

            return $this;

        }

    }

?>