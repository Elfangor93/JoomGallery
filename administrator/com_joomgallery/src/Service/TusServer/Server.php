<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\TusServer;

// No direct access
defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Psr\Http\Message\ResponseInterface;

use Joomgallery\Component\Joomgallery\Administrator\Extension\ResponseTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\ServerInterface;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\FileToolsService;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\Abort;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\BadHeader;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\File;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\Max;
use Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Exception\Request;

/**
 * Tus-Server v1.0.0 implementation
 *
 * @version   1.0.0
 * @link      https://github.com/Orajo/zf2-tus-server
 * @author    Jaroslaw Wasilewski / @Orajo (orajo@windowslive.com)
 * @author    Simon Leblanc (contact@leblanc-simon.eu)
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Jaroslaw Wasilewski, modified by JoomGallery::ProjectTeam
 * @link      https://tus.io/protocols/resumable-upload.html
 * @package   ZfTusServer
 */
class Server implements ServerInterface
{
    use ResponseTrait;

    public const TIMEOUT = 30;
    public const TUS_VERSION = '1.0.0';

    /**
     * Array containing all relevant tus headers
     * 
     * @var array
     */
    private $specs;

    /**
     * Unique upload identifier
     * Identification of the upload
     * 
     * @var string
     */
    private   $uuid;

    /**
     * Directory to use for save the file
     * 
     * @var string
     */
    private   $directory = '';

    /**
     * Location of the TUS server - URI to reach the TUS server without domain
     * Info: With slash (/) in the beginning
     * Example: /index.php?target=tus
     * 
     * @var string
     */
    private $location = '/';

    /**
     * Name of the domain, on which the file upload is provided
     * Info: Without slash (/) at the end
     * Example: http://example.org
     * 
     * @var string 
     */
    private $domain = '';

    /**
     * Switch GET method.
     * GET method needed to download uploaded files.
     * 
     * @var bool
     */
    private $allowGetMethod = true;

    /**
     * TODO: handle this limit in patch method
     *
     * @var int
     */
    private $allowMaxSize = 2147483648; // 2GB

    /**
     * Storage to collect upload meta data
     * 
     * @var array
     */
    private $metaData;

    /**
     * Switches debug mode.
     * In this mode downloading info files is allowed (usefull for testing)
     *
     * @var bool
     */
    private $debugMode;

    /**
     * Filetype of the uploaded file
     * 
     * @var string
     */
    private $fileType  = '';

    /**
     * Name of the uploaded file
     * 
     * @var string
     */
    private   $realFileName = '';

    /**
     * Constructor
     *
     * @param  string   $directory   The directory to use for save the file
     * @param  string   $location    The uri to reach the TUS server
     * @param  bool     $debug       Switches debug mode - {@see Server::debugMode}
     *
     * @throws File
     * @access public
     */
    public function __construct(string $directory, string $location, bool $debug = false)
    {
        $this->setDirectory($directory);
        $this->setLocation($location);
        
        $this->app = Factory::getApplication();
        $this->debugMode = $debug;

        require JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/tusspecs.php';
        $this->specs = $tus_specs_array;
    }

    /**
     * Process the client request
     *
     * @param   bool             $send    True to send the response, false to return the response
     *
     * @return  void|Response    void if send = true else Response object
     * 
     * @throws Exception\Request If the method isn't available
     * @throws BadHeader
     */
    public function process($send = true)
    {
        try
        {
            $method = $this->app->input->getMethod();

            $isOption = false;
            switch ($method)
            {
                case 'POST':
                    if (!$this->checkTusVersion())
                    {
                      throw new Request('The requested protocol version is not supported', 405);
                    }
                    $this->buildUuid();
                    $this->processPost();
                    break;

                case 'HEAD':
                    if (!$this->checkTusVersion())
                    {
                      throw new Request('The requested protocol version is not supported', 405);
                    }
                    $this->getUserUuid();
                    $this->processHead();
                    break;

                case 'PATCH':
                    if (!$this->checkTusVersion())
                    {
                      throw new Request('The requested protocol version is not supported', 405);
                    }
                    $this->getUserUuid();
                    $this->processPatch();
                    break;

                case 'OPTIONS':
                    $isOption = true;
                    $this->processOptions();
                    break;

                case 'GET':
                    $this->getUserUuid();
                    $this->processGet();
                    break;

                default:
                    throw new Request('The requested method ' . $method . ' is not allowed', 405);
            }

            $this->addCommonHeader($isOption);

            if($send === false)
            {
                return $this->getResponse();
            }

        }
        catch (BadHeader $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode(400);
            $this->addCommonHeader();

        }
        catch (Request $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode($exp->getCode());
            //$this->setContent($exp->getMessage());
            $this->addCommonHeader(true);
        }
        catch (\Exception $exp)
        {
            if($send === false)
            {
                throw $exp;
            }

            $this->setStatusCode(500);
            $this->setContent($exp->getMessage());
            $this->addCommonHeader();
        }

        $this->app->sendHeaders();
		    echo $this->app->getBody();

        // The process must only sent the HTTP headers and content: kill request after send
        exit;
    }

    /**
     * Process the POST request
     * 
     * @link https://tus.io/protocols/resumable-upload.html#post
     *
     * @throws  \Exception    If the uuid already exists
     * @throws  BadHeader     If the final length header isn't a positive integer
     * @throws  File          If the file already exists in the filesystem
     * @throws  File          If the creation of file failed
     * 
     * @return void
     */
    private function processPost(): void
    {
        if($this->existsInMetaData('ID') === true)
        {
            throw new \RuntimeException('The UUID already exists');
        }

        $headers = $this->extractHeaders(['Upload-Length', 'Upload-Metadata']);

        if(is_numeric($headers['Upload-Length']) === false || $headers['Upload-Length'] < 0)
        {
            throw new BadHeader('Upload-Length must be a positive integer');
        }

        $finalLength = (int)$headers['Upload-Length'];

        $this->setRealFileName($headers['Upload-Metadata']);

        $file = $this->directory . $this->getFilename();

        if(file_exists($file) === true)
        {
            throw new File('File already exists : ' . $file);
        }

        if (touch($file) === false)
        {
            throw new File('Impossible to touch ' . $file);
        }

        $this->setMetaDataValue('ID', $this->uuid);
        $this->saveMetaData($finalLength, 0, false, true);

        $this->setStatusCode(201);

        $location = $this->app->input->server->get('REQUEST_URI', $this->getLocation(), 'string');
        $domain   = $this->getDomain() ?: '';

        $this->addHeaderLine('Location', $domain . $location . '&uuid=' . $this->uuid);

        unset($path);
    }

    
    /**
     * Process the HEAD request
     *
     * @link http://tus.io/protocols/resumable-upload.html#head
     *
     * @throws \Exception If the uuid isn't know
     * 
     * @return void
     */
    private function processHead(): void
    {
        if ($this->existsInMetaData('ID') === false)
        {
            $this->setStatusCode(404);
            return;
        }

        // if file in storage does not exists
        if (!file_exists($this->directory . $this->getFilename()))
        {
            // allow new upload
            $this->removeFromMetaData($this->uuid);
            $this->setStatusCode(404);
            return;
        }

        $offset  = $this->getMetaDataValue('Offset');
        $this->addHeaderLine('Upload-Offset', $offset);

        $length = $this->getMetaDataValue('Size');
        $this->addHeaderLine('Upload-Length', $length);

        $this->addHeaderLine('Cache-Control', 'no-store');

        $this->setStatusCode(200);
    }

    /**
     * Process the PATCH request
     * 
     * @link http://tus.io/protocols/resumable-upload.html#patch
     *
     * @throws \Exception If the uuid isn't know
     * @throws BadHeader If the Upload-Offset header isn't a positive integer
     * @throws BadHeader If the Content-Length header isn't a positive integer
     * @throws BadHeader If the Content-Type header isn't "application/offset+octet-stream"
     * @throws BadHeader If the Upload-Offset header and session offset are not equal
     * @throws File If it's impossible to open php://input
     * @throws File If it's impossible to open the destination file
     * @throws File If it's impossible to set the position in the destination file
     * 
     * @return void
     */
    private function processPatch()
    {
        // Check the uuid
        if ($this->existsInMetaData('ID') === false)
        {
            throw new \RuntimeException('The UUID doesn\'t exists');
        }

        // Check HTTP headers
        $headers = $this->extractHeaders(['Upload-Offset', 'Content-Length', 'Content-Type']);

        if(is_numeric($headers['Upload-Offset']) === false || $headers['Upload-Offset'] < 0)
        {
            throw new BadHeader('Upload-Offset must be a positive integer');
        }

        if(isset($headers['Content-Length']) && (is_numeric($headers['Content-Length']) === false || $headers['Content-Length'] < 0))
        {
            throw new BadHeader('Content-Length must be a positive integer');
        }

        if(is_string($headers['Content-Type']) === false || $headers['Content-Type'] !== 'application/offset+octet-stream')
        {
            throw new BadHeader('Content-Type must be "application/offset+octet-stream"');
        }

        // Offset of current PATCH request
        $offsetHeader = (int)$headers['Upload-Offset'];
        // Length of data of the current PATCH request
        $contentLength = isset($headers['Content-Length']) ? (int)$headers['Content-Length'] : null;
        // Last offset, taken from session
        $offsetSession = (int)$this->getMetaDataValue('Offset');
        // Total length of file (expected data)
        $lengthSession = (int)$this->getMetaDataValue('Size');

        $this->setRealFileName($this->getMetaDataValue('FileName'));

        // Check consistency (user vars vs session vars)
        if($offsetSession === null || $offsetSession !== $offsetHeader)
        {
            $this->setStatusCode(409);
            $this->addHeaderLine('Upload-Offset', $offsetSession);
            return;
        }

        // Check if the file is already entirely write
        if($offsetSession === $lengthSession || $lengthSession === 0)
        {
            // the whole file was uploaded
            $this->setStatusCode(204);
            $this->addHeaderLine('Upload-Offset', $offsetSession);
            return;
        }

        // Read / Write data
        $handleInput = fopen('php://input', 'rb');
        if($handleInput === false)
        {
            throw new File('Impossible to open php://input');
        }

        $file = $this->directory . $this->getFilename();
        $handleOutput = fopen($file, 'ab');
        if ($handleOutput === false)
        {
            throw new File('Impossible to open file to write into');
        }

        if (fseek($handleOutput, $offsetSession) === false)
        {
            throw new File('Impossible to move pointer in the good position');
        }

        ignore_user_abort(false);

        /* @var $currentSize Int Total received data lenght, including all chunks */
        $currentSize = $offsetSession;
        /* @var $totalWrite Int Length of saved data in current PATCH request */
        $totalWrite = 0;

        $returnCode = 204;
        $returnMsg  = 'No Content';

        try {
            while (true)
            {
                set_time_limit(self::TIMEOUT);

                // Manage user abort
                // according to comments on PHP Manual page (http://php.net/manual/en/function.connection-aborted.php)
                // this method doesn't work, but we cannot send 0 to browser, because it's not compatible with TUS.
                // But maybe some day (some PHP version) it starts working. Thath's why I leave it here.
                
                // echo "\n";
                // ob_flush();
                // flush();

                if(connection_status() !== CONNECTION_NORMAL)
                {
                    throw new Abort('User abort connexion');
                }

                $data = fread($handleInput, 8192);
                if($data === false)
                {
                    throw new File('Impossible to read the datas');
                }

                $sizeRead = strlen($data);

                // If user sent 0 bytes and we do not write all data yet, abort
                if($sizeRead === 0)
                {
                    if($contentLength !== null && $totalWrite < $contentLength)
                    {
                        throw new Abort('Stream unexpectedly ended. Maybe user aborted?');
                    }

                    // end of stream
                    break;
                }

                // If user sent more datas than expected (by POST Final-Length), abort
                if($contentLength !== null && ($sizeRead + $currentSize > $lengthSession))
                {
                    throw new Max('Size sent is greather than max length expected');
                }

                // If user sent more datas than expected (by PATCH Content-Length), abort
                if($contentLength !== null && ($sizeRead + $totalWrite > $contentLength))
                {
                    throw new Max('Size sent is greather than max length expected');
                }

                // Write datas
                $sizeWrite = fwrite($handleOutput, $data);
                if($sizeWrite === false)
                {
                    throw new File('Unable to write data');
                }

                $currentSize += $sizeWrite;
                $totalWrite += $sizeWrite;
                $this->setMetaDataValue('Offset', $currentSize);

                if($currentSize === $lengthSession)
                {
                    $this->saveMetaData($lengthSession, $currentSize, true, false);
                    break;
                }

                $this->saveMetaData($lengthSession, $currentSize, false, true);
            }

            $this->addHeaderLine('Upload-Offset', $currentSize);

        }
        catch (Max $exp)
        {
            $returnCode = 400;
            $returnMsg  = $exp->getMessage();
        }
        catch (File $exp)
        {
            $returnCode = 500;
            $returnMsg  = $exp->getMessage();
        }
        catch (Abort $exp)
        {
            $returnCode = 100;
            $returnMsg  = $exp->getMessage();
        }
        catch (\Exception $exp)
        {
            $returnCode = 500;
            $returnMsg  = $exp->getMessage();
        }
        finally
        {
            fclose($handleInput);
            fclose($handleOutput);
        }

        $this->setStatusCode($returnCode);
        $this->setContent($returnMsg);
    }

    /**
     * Process the OPTIONS request
     * 
     * @link http://tus.io/protocols/resumable-upload.html#options
     *
     * @return void
     */
    private function processOptions(): ResponseInterface
    {
        $this->uuid = null;

        $this->setStatusCode(204);
    }

    /**
     * Process the GET request
     *
     * @return void
     */
    private function processGet(): void
    {
        if (!$this->allowGetMethod)
        {
            throw new Request('The requested method Get is not allowed', 405);
        }

        $file = $this->directory . $this->getFilename();
        if(!file_exists($file))
        {
            throw new Request('The file ' . $this->uuid . ' doesn\'t exist', 404);
        }

        if(!is_readable($file))
        {
            throw new Request('The file ' . $this->uuid . ' is unaccessible', 403);
        }

        if(!file_exists($file . '.info') || !is_readable($file . '.info'))
        {
            throw new Request('The file ' . $this->uuid . ' has no metadata', 500);
        }

        $fileName = $this->getMetaDataValue('FileName');

        if ($this->debugMode)
        {
            $isInfo = $this->app->get('info', -1, 'integer');
            if($isInfo !== -1)
            {
                FileToolsService::downloadFile($file . '.info', $fileName . '.info');
            }
            else
            {
                $mime = FileToolsService::detectMimeType($file);
                FileToolsService::downloadFile($file, $fileName, $mime);
            }
        }
        else
        {
            $mime = FileToolsService::detectMimeType($file);
            FileToolsService::downloadFile($file, $fileName, $mime);
        }

        exit;
    }

    ///////////////////////////////////////////
    ///////////////////////////////////////////

    /**
     * Checks compatibility with requested Tus protocol
     *
     * @return boolean
     */
    private function checkTusVersion(): bool
    {
        $tusVersion = $this->app->input->server->get($this->specs['Headers']['Tus-Resumable']['Name'], $this->specs['Headers']['Tus-Resumable']['Default'], $this->specs['Headers']['Tus-Resumable']['Type']);

        if($tusVersion === self::TUS_VERSION)
        {
          return true;
        }
        else
        {
          return false;
        }
    }

    /**
     * Build a new UUID (use in the POST request)
     *
     * @return void
     */
    private function buildUuid(): void
    {
        $this->uuid = hash('md5', uniqid(mt_rand() . php_uname(), true));
    }

    /**
     * Get the UUID of the request (use for HEAD and PATCH request)
     *
     * @return  string  The UUID of the request
     * 
     * @throws \InvalidArgumentException If the UUID is empty
     * @access private
     */
    private function getUserUuid(): string
    {
        if($this->uuid === null)
        {
            // $path = Uri::current();
            // $uuid = substr($path, strrpos($path, '/') + 1);
            $uuid = $this->app->input->get('uuid', '', 'string');

            if(strlen($uuid) === 32 && preg_match('/[a-z0-9]/', $uuid))
            {
                $this->uuid = $uuid;
            }
            else
            {
                throw new \InvalidArgumentException('The uuid cannot be empty.');
            }
        }

        return $this->uuid;
    }

    /**
     * Check if $key an $id exists in the session
     *
     * @param $key
     *
     * @return bool  True if the id exists, false else
     * @access private
     */
    private function existsInMetaData($key): bool
    {
        $data = $this->getMetaData();

        return isset($data[$key]) && !empty($data[$key]);
    }

    /**
     * Get the session info
     * 
     * @return array
     */
    private function getMetaData(): array
    {
        if($this->metaData === null)
        {
            $this->metaData = $this->readMetaData($this->getUserUuid());
        }

        return $this->metaData;
    }

    /**
     * Reads or initialize metadata about file.
     *
     * @param string $name
     *
     * @return array
     */
    private function readMetaData($name): array
    {
        $refData = [
            'ID' => '',
            'Size' => 0,
            'Offset' => 0,
            'Extension' => '',
            'FileName' => '',
            'MimeType' => '',
            'IsPartial' => true,
            'IsFinal' => false,
            'PartialUploads' => null, // unused
        ];

        $storageFileName = $this->directory . $name . '.info';

        if(file_exists($storageFileName))
        {
            $json = file_get_contents($storageFileName);
            $data = \json_decode($json, true);

            if(is_array($data))
            {
                return array_merge($refData, $data);
            }
        }

        return $refData;
    }

    /**
     * Set a value in the session
     *
     * @param  string  $key    The key for wich you want set the value
     * @param  mixed   $value  The value for the id-key to save
     *
     * @return void
     * 
     * @throws \Exception
     * @access  private
     */
    private function setMetaDataValue($key, $value): void
    {
        $data = $this->getMetaData();

        if(isset($data[$key]))
        {
            $data[$key] = $value;
        }
        else
        {
            throw new \RuntimeException($key . ' is not defined in medatada');
        }
    }

    /**
     * Saves metadata about uploaded file.
     * Metadata are saved into a file with name mask 'uuid'.info
     *
     * @param  int   $size
     * @param  int   $offset
     * @param  bool  $isFinal
     * @param  bool  $isPartial
     *
     * @throws \Exception
     */
    private function saveMetaData(int $size, int $offset = 0, bool $isFinal = false, bool $isPartial = false): void
    {
        $this->setMetaDataValue('ID', $this->getUserUuid());
        $this->metaData['ID'] = $this->getUserUuid();
        $this->metaData['Offset'] = $offset;
        $this->metaData['IsPartial'] = $isPartial;
        $this->metaData['IsFinal'] = $isFinal;

        if($this->metaData['Size'] === 0)
        {
            $this->metaData['Size'] = $size;
        }

        if(empty($this->metaData['FileName']))
        {
            $this->metaData['FileName'] = $this->getRealFileName();
            $info = new \SplFileInfo($this->getRealFileName());
            $ext = $info->getExtension();
            $this->metaData['Extension'] = $ext;
        }

        if($isFinal)
        {
            if(!$this->fileType)
            {
                $this->fileType = FileToolsService::detectMimeType(
                    $this->directory . $this->getUserUuid(),
                    $this->getRealFileName()
                );
            }
            $this->metaData['MimeType'] = $this->fileType;
        }

        $json = \json_encode($this->metaData);

        file_put_contents($this->directory . $this->getUserUuid() . '.info', $json);
    }

    /**
     * Remove selected $id from database
     *
     * @param string $id The id to test
     *
     * @return bool
     * @access private
     */
    private function removeFromMetaData($id): bool
    {
        $storageFileName = $this->directory . $id . '.info';

        if (file_exists($storageFileName) && is_writable($storageFileName))
        {
            unset($storageFileName);
            return true;
        }

        return false;
    }

    /**
     * Get a value from session
     *
     * @param string $key The key for wich you want value
     *
     * @return mixed The value for the id-key
     * @throws \Exception key is not defined in medatada
     * @access private
     */
    private function getMetaDataValue($key)
    {
        $data = $this->getMetaData();
        if (isset($data[$key]))
        {
            return $data[$key];
        }

        throw new \RuntimeException($key . ' is not defined in medatada');
    }

    /**
     * Sets real file name
     *
     * @param  string  $value   plain or base64 encoded file name
     *
     * @return Server  object
     * @access private
     */
    private function setRealFileName($value): Server
    {
        $parts = explode(',', $value);

        foreach ($parts as $part) {
            if (($namePos = strpos($part, 'filename ')) !== false) {
                $value = substr($part, $namePos + 9); // 9 - length of 'filename '
                $this->realFileName = base64_decode($value);
            }
            elseif(($namePos = strpos($part, 'filetype ')) !== false) {
                $value = substr($part, $namePos + 9); // 9 - length of 'filetype '
                $this->fileType = base64_decode($value);
            }
            else {
                $this->realFileName = $value;
            }
        }

        return $this;
    }

    /**
     * Get real name of transfered file
     *
     * @return string  Real name of file
     * 
     * @access public
     */
    public function getRealFileName(): string
    {
        return $this->realFileName;
    }

    /**
     * Get the filename to use when save the uploaded file
     *
     * @return  string  The filename to use
     * 
     * @throws \DomainException If the uuid isn't define
     * @access private
     */
    private function getFilename(): string
    {
        if($this->uuid === null)
        {
            throw new \DomainException('Uuid can\'t be null when call ' . __METHOD__);
        }

        return $this->uuid;
    }

    /**
     * Extract a list of headers in the HTTP headers
     *
     * @param   array  $headers   A list of header name to extract
     *
     * @return  array  A list if header ([header name => header value])
     * 
     * @throws BadHeader
     * @access private
     */
    private function extractHeaders($headers): array
    {
        if(is_array($headers) === false)
        {
            throw new \InvalidArgumentException('Headers must be an array');
        }

        $headersValues = [];
        foreach ($headers as $headerName)
        {
            $value = $this->app->input->server->get($this->specs['Headers'][$headerName]['Name'], $this->specs['Headers'][$headerName]['Default'], $this->specs['Headers'][$headerName]['Type']);
            
            if($this->specs['Headers'][$headerName]['Type'] == 'string' && trim($value) === '')
            {
                throw new BadHeader($headerName . ' can\'t be empty');
            }

            $headersValues[$headerName] = $value;                
        }

        return $headersValues;
    }

    /**
     * Add the commons headers to the HTTP response
     *
     * @param bool $isOption Is OPTION request
     *
     * @access private
     */
    private function addCommonHeader($isOption = false): void
    {
        $this->addHeaderLine('Tus-Resumable', self::TUS_VERSION);
        $this->addHeaderLine('Access-Control-Allow-Origin', '*');
        $this->addHeaderLine('Access-Control-Expose-Headers', 'Upload-Offset, Location, Upload-Length, Tus-Version, Tus-Resumable, Tus-Max-Size, Tus-Extension, Upload-Metadata');

        if($isOption)
        {
            $allowedMethods = 'OPTIONS,HEAD,POST,PATCH';

            if($this->getAllowGetMethod())
            {
                $allowedMethods .= ',GET';
            }

            $this->addHeaderLine('Tus-Version', self::TUS_VERSION);
            $this->addHeaderLine('Tus-Extension', 'creation');
            $this->addHeaderLine('Allow', $allowedMethods);
            $this->addHeaderLine('Access-Control-Allow-Methods', $allowedMethods);
            $this->addHeaderLine('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Final-Length, Upload-Offset, Upload-Length, Tus-Resumable, Upload-Metadata');

            if($this->allowMaxSize > 0)
            {
                $this->addHeaderLine('Tus-Max-Size', $this->allowMaxSize);
            }
        }

        return;
    }

    /**
     * Is GET method allowed
     *
     * @return bool
     */
    public function getAllowGetMethod(): bool
    {
        return $this->allowGetMethod;
    }

    /**
     * Allows GET method (it means allow download uploded files)
     *
     * @param bool $allow
     *
     * @return void
     */
    public function setAllowGetMethod($allow)
    {
        $this->allowGetMethod = (bool)$allow;

        return $this;
    }

    /**
     * Sets upload size limit
     *
     * @param int $value
     *
     * @return void
     * @throws \BadMethodCallException
     */
    public function setAllowMaxSize(int $value)
    {
        if ($value > 0) {
            $this->allowMaxSize = $value;
        }
        else {
            throw new \BadMethodCallException('given $value must be integer, greater them 0');
        }

        return $this;
    }

    /**
     * Set the directory where the file will be store
     *
     * @param   string   $directory   The directory where the file are stored
     *
     * @return  Server
     * 
     * @throws File
     * @throws \InvalidArgumentException
     */
    private function setDirectory(string $directory): Server
    {
        if(is_dir($directory) === false || is_writable($directory) === false)
        {
            throw new File($directory . ' doesn\'t exist or isn\'t writable');
        }

        $this->directory = $directory . (substr($directory, -1) !== DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : '');

        return $this;
    }

    /**
     * Get the location (uri) of the TUS server
     * 
     * @return string
     */
    private function getLocation(): string
    {
      if(\substr($this->location, 0, 1) != '/')
      {
        // location should always starts with a slash (/)
        return '/' . $this->location;
      }

      return $this->location;
    }

    /**
     * Sets the location (uri) of the TUS server
     * 
     * @param string $location
     *
     * @return void
     * 
     * @throws \Exception
     */
    private function setLocation(string $location)
    {
      if(\strpos($location, 'http') !== false || \strpos($location, '://') !== false || \strpos($location, 'www.') !== false)
      {
        // looks like $location contains the domain
        throw new \Exception('Location should not contain the domain. Please provide the domain seperately using setDomain() method.', 1);        
      }

      if(\substr($location, 0, 1) != '/')
      {
        // location should always starts with a slash (/)
        $location = '/' . $location;
      }

      $this->location = $location;

      return;
    }

    /**
     * Get the domain of the server
     * 
     * @return string
     */
    public function getDomain(): string
    {
      if(\substr($this->domain, -1) == '/')
      {
        // domain should never ends with a slash (/)
        return \substr($this->domain, 0, -1);
      }
      
      return $this->domain;
    }

    /**
     * Sets the domain of the server
     * 
     * @param string $domain
     *
     * @return void
     */
    public function setDomain(string $domain)
    {
      if(\substr($domain, -1) == '/')
      {
        // domain should never ends with a slash (/)
        $domain = \substr($domain, 0, -1);
      }

      $this->domain = $domain;

      return;
    }
}
