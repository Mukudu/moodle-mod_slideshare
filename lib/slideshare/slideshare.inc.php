 <?php

/*
    slideshare php library

    libary to communicate with slideshare via api.php and index.php
    to allow php scripts to obtain slideshare pages

    object orientated??

    using xml format due to protability - php formats proving unreliable - especially serialisation


*/

class slideshare {

    private $debugging = false;

    // defaults
    private $useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.9) Gecko/20071025 Firefox/2.0.0.9';
    private $http_proxy = '';
    private $wiki_api_url ='http://en.slideshare.org/w/api.php';
    private $rawoutput = false;

    // content stuff
    private $content = '';
    private $wiki_content_url = '';
    private $rawtitle = '';
    private $title = '';
    private $pageid = 0;
    private $snippets = array();         // including infobox, preamble, #sections
    private $toc = '';

    public $url = '';
    public $redirected = false;         //has this been redirected
    public $error = '';

    // create a slideshare snippet object
    // pass in the slideshare url
    function __construct() {
        //do nothing we should be set up OK.

    }

    function __destruct() {
        //do I need this???
    }

    //set a different user agent - if no setting - current remains in force
    public function setUserAgent($useragent) {
        if ($useragent) {
            if ($this->debugging) error_log('Useragent is being reset to ' . $useragent);
            $this->useragent = $useragent;
        }
    }

    // set a proxy for curl requests - if empty proxy is unset
    public function setProxy($proxy) {
        if ($this->debugging && $proxy) error_log("Proxy being set to '$proxy'");
        $this->http_proxy = $proxy;     //if empty proxy is unset
    }

    //set the wiki API url - if not set current stays in force
    
// TODO

    public function setWikiAPI_URL($wiki_api) {
        if ($wiki_api) {
            if ($this->debugging) error_log("Wiki API URL set to '$wiki_api'");
            $this->wiki_api_url = $wiki_api;
        }
    }

    //function to create an XML object from slideshare xml responses
    private function _response2XML($xml) {

        if ($this->debugging) error_log("XML recasting in progress");

        if ($xml) {
            libxml_use_internal_errors(true);                                   //we want to catch any errors

            if (!$xml = simplexml_load_string($xml)) {                          //turn into xml object
                $xml = '';                      //redefine it
                if ($xml_errs=libxml_get_errors()) {
                    $this->_raiseError(count($xml_errs) . " XML Parsing errors, view error log for details");
                    error_log(print_r($xml_errs,true));
                }else{
                    $this->_raiseError('Undefined error parsing response');
                }
            }
        }

        if ($this->debugging) error_log("XML being returned");

        return $xml;
    }


    // function to raise Errors
    private function _raiseError($errmsg) {
        $this->error=$errmsg;
        if ($this->debugging) error_log($errmsg);
    }

    //
    //  default with no params is true - otherwise true or false - anyother params turns it off i.e. false
    //
    public function setdebugging($on_off=true) {
        if (($on_off === false) || ($on_off === true)) {
            $this->debugging = $on_off;
        }else{
            $this->debugging=false;
        }
    }

    //
    //  function to get the raw content from slideshare
    //
    private function _getPagefromWikimedia($url,$postFields='') {
        if ($this->debugging) error_log("Making cURL HTTP Request");
        $session = curl_init($url);

        curl_setopt( $session, CURLOPT_URL, $url );
        curl_setopt( $session, CURLOPT_USERAGENT, $this->useragent);               //slideshare insists on a useragent
        curl_setopt( $session, CURLOPT_HEADER, false );

        //if we need to set a proxy.check in the environment
        if ($this->http_proxy) {
            curl_setopt( $session, CURLOPT_PROXY, $this->http_proxy);
        }

        curl_setopt( $session, CURLOPT_RETURNTRANSFER, 1 );
        if (!empty($postFields)) {
            curl_setopt( $session, CURLOPT_HTTPHEADER, array('Expect:'));           //workaround for error caused by a slideshare squid being a HTTP1.0 device -
                                                                                    //http://serverfault.com/questions/107813/why-does-squid-reject-this-multipart-form-data-post-from-curl
            curl_setopt( $session, CURLOPT_POST, 1);
            curl_setopt( $session, CURLOPT_POSTFIELDS, $postFields );
        }

        if ($this->debugging) error_log("Starting request");

        $result = curl_exec( $session );
        if ($err = curl_error($session)) {
            $err = "HTTP request error - $err";
            if ($this->debugging) error_log($err);
            $this->error = $err;
        }

        if ($this->debugging){
            $info = curl_getinfo($session);
            error_log('cURL Info: '. print_r($info, true));     //write to logs
        }

        curl_close( $session );

        return $result;
    }
}

?>