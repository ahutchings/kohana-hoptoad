<?php defined('SYSPATH') or die('No direct script access.');

class Hoptoad_Core
{
    // Hoptoad Notifier API endpoint
    const ENDPOINT = 'http://hoptoadapp.com/notifier_api/v2/notices';

    // The version of the API being used
    const API_VERSION = '2.0';

    // The version number of the notifier client submitting the request
    const NOTIFIER_VERSION = 'v0.0.1';

    // The name of the notifier client submitting the request
    const NOTIFIER_NAME = 'kohana-hoptoad';

    // A URL at which more information can be obtained concerning the notifier client
    const NOTIFIER_URL = 'https://github.com/ahutchings/kohana-hoptoad';

    // Hoptoad instance
    protected static $_instance;

    /**
     * Singleton pattern
     *
     * @return  Hoptoad
     */
    public static function instance()
    {
        if ( ! isset(Hoptoad::$_instance))
        {
            // Load the configuration for this type
            $config = Kohana::config('hoptoad');

            // Create a new session instance
            Hoptoad::$_instance = new Hoptoad($config);
        }

        return Hoptoad::$_instance;
    }

    /**
     * @var  Config
     */
    protected $_config;

    /**
     * Ensures singleton pattern is observed
     *
     * @param  array  configuration
     */
    public function __construct($config = array())
    {
        $this->_config = $config;
    }

    /**
     * Sets the exception.
     *
     * @return  Hoptoad
     */
    public function exception($e)
    {
        $this->_exception = $e;

        return $this;
    }

    /**
     * Renders the XML notice.
     *
     * @return  string
     */
    public function notice()
    {
        $xml = new SimpleXMLElement('<notice />');
        $xml->addAttribute('version', self::API_VERSION);

        // Add api-key
        $xml->addChild('api-key', $this->_config['api_key']);

        // Build notifier subelement
        $notifier = $xml->addChild('notifier');
        $notifier->addChild('name', self::NOTIFIER_NAME);
        $notifier->addChild('version', self::NOTIFIER_VERSION);
        $notifier->addChild('url', self::NOTIFIER_URL);

        // Build error subelement
        $error = $xml->addChild('error');
        $error->addChild('class', get_class($this->_exception));
        $error->addChild('message', $this->_exception->getMessage());
        $this->addXmlBacktrace($error);

        // Build request subelement
        $request = $xml->addChild('request');
        $request->addChild('url', Request::detect_uri());
        $request->addChild('component', NULL);
        $request->addChild('action', NULL);

        if (isset($_REQUEST)) $this->addXmlVars($request, 'params', $_REQUEST);
        if (isset($_SESSION)) $this->addXmlVars($request, 'session', $_SESSION);

        if (isset($_SERVER))
        {
            $cgi_data = (isset($_ENV) AND ! empty($_ENV))
                ? array_merge($_SERVER, $_ENV)
                : $_SERVER;

            $this->addXmlVars($request, 'cgi-data', $cgi_data);
        }

        // Build server-environment subelement
        $server = $xml->addChild('server-environment');
        $server->addChild('project-root', DOCROOT);
        $server->addChild('environment-name', Kohana::$environment);

        return $xml->asXML();
    }

    /**
     * Sends the notification XML to Hoptoad.
     *
     * @return  void
     */
    public function notify()
    {
        Request::factory(self::ENDPOINT)
            ->method('POST')
            ->headers('Content-Type', 'text/xml; charset=utf-8')
            ->body($this->notice())
            ->execute();
    }

    /**
     * Add a Hoptoad backtrace to the XML
     * @return void
     * @author Rich Cavanaugh
     */
    public function addXmlBacktrace($parent)
    {
        $backtrace = $parent->addChild('backtrace');
        $line_node = $backtrace->addChild('line');
        $line_node->addAttribute('file', $this->_exception->getFile());
        $line_node->addAttribute('number', $this->_exception->getLine());

        foreach ($this->_exception->getTrace() as $entry)
        {
            if (isset($entry['class']) AND $entry['class'] === 'Hoptoad')
                continue;

            $line_node = $backtrace->addChild('line');
            $line_node->addAttribute('file', $entry['file']);
            $line_node->addAttribute('number', $entry['line']);
            $line_node->addAttribute('method', $entry['function']);
        }
    }

    /**
     * Add a Hoptoad var block to the XML
     * @return void
     * @author Rich Cavanaugh
     */
    function addXmlVars($parent, $key, $source)
    {
        if (empty($source)) return;

        $node = $parent->addChild($key);

        foreach ($source as $key => $val)
        {
            $var_node = $node->addChild('var', $val);
            $var_node->addAttribute('key', $key);
        }
    }
}
