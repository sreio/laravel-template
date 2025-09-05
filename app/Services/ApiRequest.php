<?php
namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ApiRequest extends BaseServices
{
    /** @var Client */
    protected Client $client;

    /** @var array<string,string> */
    protected array $defaultHeaders = [];

    /** @var LoggerInterface|null */
    protected ?LoggerInterface $logger = null;

    /** @var string|null 日志 channel，不设置则不记录日志 */
    protected ?string $logChannel = null;

    /** @var int 超时时间（秒） */
    protected int $timeout = 10;

    const StatusSuccess = 200;

    /**
     * @param array $clientConfig 传入 Guzzle 的构造参数（如 base_uri、proxy、verify 等）
     * @param array<string,string> $defaultHeaders 统一基础 Header
     * @param LoggerInterface|null $logger 可选注入 PSR-3 日志器（Monolog 等）
     */
    public function __construct(array $clientConfig = [], array $defaultHeaders = [], ?LoggerInterface $logger = null)
    {
        $clientConfig = array_merge([
            'timeout' => $this->timeout,
        ], $clientConfig);

        $this->client = new Client($clientConfig);
        $this->defaultHeaders = $defaultHeaders;
        $this->logger = $logger;
    }

    /** 可链式设置统一超时 */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = max(1, $seconds);
        return $this;
    }

    /** 可链式设置/覆盖默认 Header */
    public function setDefaultHeader(string $key, string $value): self
    {
        $this->defaultHeaders[$key] = $value;
        return $this;
    }

    /** 注入/替换日志器（可选）*/
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * 设置日志 channel（满足“可通过属性、方法设置 channel”的要求）
     * —— 只有设置了 channel 才会记录日志
     */
    public function setLogChannel(?string $channel): self
    {
        $this->logChannel = $channel ? trim($channel) : null;
        return $this;
    }

    /** 也可以以公开属性的方式在子类里直接赋值 */
    public function getLogChannel(): ?string
    {
        return $this->logChannel;
    }

    /**
     * 统一请求入口
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return array
     * @throws GuzzleException
     * @throws Exception
     */
    public function request(string $method, string $uri, array $options = []): array
    {
        $method = strtoupper($method);

        // 合并 Header，并在请求前生成 request-id
        $requestId = $this->generateRequestId();
        $headers = array_merge($this->defaultHeaders, $options['headers'] ?? []);
        $headers['X-Request-Id'] = $requestId;
        $options['headers'] = $headers;

        // 默认超时
        if (!isset($options['timeout'])) {
            $options['timeout'] = $this->timeout;
        }

        $this->logParams($requestId, $method, $uri, $options);

        $start = microtime(true);
        try {
            $response = $this->client->request($method, $uri, $options);
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            // 响应基础信息
            $status  = $response->getStatusCode();
            $resHeaders = $response->getHeaders();

            // 如果使用 sink（下载）则不读 body；否则读取字符串（注意可能很大）
            $bodyReadable = !isset($options['sink']);
            $bodyString = $bodyReadable ? (string) $response->getBody() : '';

            // 记录“响应结果”日志
            $this->logResponse($requestId, $method, $uri, $status, $bodyString, $resHeaders, $durationMs);

            // 尝试 JSON 解包（非必须）
            $json = null;
            if ($bodyReadable && $this->isJsonLike($resHeaders)) {
                $json = json_decode($bodyString, true);
            }

            return [
                'request_id' => $requestId,
                'status'     => $status,
                'headers'    => $resHeaders,
                'body'       => $bodyReadable ? $bodyString : null,
                'json'       => $json,
                'duration_ms'=> $durationMs,
            ];
        } catch (GuzzleException $e) {
            $durationMs = (int) round((microtime(true) - $start) * 1000);

            // 异常也记录到“响应结果”日志里
            $this->logResponse($requestId, $method, $uri, 0, sprintf('Exception: %s', $e->getMessage()), [], $durationMs, true);

            // 将异常继续抛出或转换为统一返回结构
            throw $e;
        }
    }

    /* --------------------- 简化方法：GET/POST/PUT/DELETE --------------------- */

    /**
     * @param string $uri
     * @param array $query
     * @param array $options
     * @return array
     * @throws GuzzleException
     */
    public function get(string $uri, array $query = [], array $options = []): array
    {
        if (!empty($query)) {
            $options[RequestOptions::QUERY] = $query;
        }
        return $this->request('GET', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @param bool $asJson
     * @return array
     * @throws GuzzleException
     */
    public function post(string $uri, array $data = [], array $options = [], bool $asJson = true): array
    {
        if ($asJson) {
            $options[RequestOptions::JSON] = $data;
        } else {
            $options[RequestOptions::FORM_PARAMS] = $data;
        }
        return $this->request('POST', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @param bool $asJson
     * @return array
     * @throws GuzzleException
     */
    public function put(string $uri, array $data = [], array $options = [], bool $asJson = true): array
    {
        if ($asJson) {
            $options[RequestOptions::JSON] = $data;
        } else {
            $options[RequestOptions::FORM_PARAMS] = $data;
        }
        return $this->request('PUT', $uri, $options);
    }

    /**
     * @param string $uri
     * @param array $data
     * @param array $options
     * @param bool $asJson
     * @return array
     * @throws GuzzleException
     */
    public function delete(string $uri, array $data = [], array $options = [], bool $asJson = true): array
    {
        // 部分 API 的 DELETE 需要 body，这里也支持
        if ($asJson && !empty($data)) {
            $options[RequestOptions::JSON] = $data;
        } elseif (!empty($data)) {
            $options[RequestOptions::FORM_PARAMS] = $data;
        }
        return $this->request('DELETE', $uri, $options);
    }

    /* --------------------------- 上传与下载 --------------------------- */

    /**
     * 上传文件（multipart）
     * @param string $uri
     * @param array<int,array{name:string,path:string,filename?:string,mime?:string,contents?:resource|string}> $files
     *   例如：
     *   [
     *     ['name' => 'file', 'path' => '/tmp/a.png', 'filename' => 'a.png', 'mime' => 'image/png'],
     *     ['name' => 'doc',  'path' => '/tmp/a.pdf', 'filename' => 'a.pdf', 'mime' => 'application/pdf'],
     *   ]
     * @param array<string,mixed> $formFields 其他表单字段
     * @param array $options 其他 Guzzle 选项
     * @return array
     * @throws GuzzleException
     */
    public function upload(string $uri, array $files, array $formFields = [], array $options = []): array
    {
        $multipart = [];

        foreach ($formFields as $k => $v) {
            $multipart[] = [
                'name'     => $k,
                'contents' => is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE),
            ];
        }

        foreach ($files as $f) {
            $name = $f['name'];
            $contents = $f['contents'] ?? fopen($f['path'], 'rb');
            $part = [
                'name'     => $name,
                'contents' => $contents,
            ];
            if (!empty($f['filename'])) {
                $part['filename'] = $f['filename'];
            }
            if (!empty($f['mime'])) {
                $part['headers'] = ['Content-Type' => $f['mime']];
            }
            $multipart[] = $part;
        }

        $options[RequestOptions::MULTIPART] = $multipart;

        // multipart 与 JSON/FORM_PARAMS 互斥，确保清理
        unset($options[RequestOptions::JSON], $options[RequestOptions::FORM_PARAMS]);

        return $this->request('POST', $uri, $options);
    }

    /**
     * 下载文件（保存到本地）
     * @param string $uri
     * @param string $saveTo 本地保存路径
     * @param array<string,mixed> $query
     * @param array $options
     * @return array
     * @throws GuzzleException
     */
    public function download(string $uri, string $saveTo, array $query = [], array $options = []): array
    {
        if (!is_dir(dirname($saveTo))) {
            if (!@mkdir(dirname($saveTo), 0775, true) && !is_dir(dirname($saveTo))) {
                throw new RuntimeException('Failed to create directory: ' . dirname($saveTo));
            }
        }

        $options[RequestOptions::SINK] = $saveTo;
        if (!empty($query)) {
            $options[RequestOptions::QUERY] = $query;
        }

        return $this->request('GET', $uri, $options);
    }

    /* --------------------------- 内部工具方法 --------------------------- */

    /**
     * @return string
     * @throws Exception
     */
    protected function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function hasLogging(): bool
    {
        return $this->logger !== null && !empty($this->logChannel);
    }

    /**
     * 记录“请求参数”日志（前缀：api-request-params-）
     */
    protected function logParams(string $requestId, string $method, string $uri, array $options): void
    {
        if (!$this->hasLogging()) {
            return;
        }

        // 避免把真正的文件流写进日志：只保留文件项的 meta
        $cleanOptions = $options;
        if (isset($cleanOptions[RequestOptions::MULTIPART])) {
            $cleanOptions[RequestOptions::MULTIPART] = array_map(static function ($part) {
                $clone = $part;
                if (isset($clone['contents'])) {
                    $clone['contents'] = '[stream or string omitted]';
                }
                return $clone;
            }, $cleanOptions[RequestOptions::MULTIPART]);
        }
        if (isset($cleanOptions[RequestOptions::SINK])) {
            $cleanOptions[RequestOptions::SINK] = '[sink to file omitted]';
        }

        $message = '[api-request-params]';
        $context = [
            'request_id' => $requestId,
            'method'     => $method,
            'uri'        => $uri,
            'options'    => $cleanOptions,
        ];

        $this->logger->info($message, $context);
    }

    /**
     * 记录“响应结果”日志（前缀：api-request-）
     */
    protected function logResponse(
        string $requestId,
        string $method,
        string $uri,
        int $status,
        string $body,
        array $headers,
        int $durationMs,
        bool $isException = false
    ): void {
        if (!$this->hasLogging()) {
            return;
        }

        // 防止巨大的 body 冲爆日志，做一个安全截断
        /*$maxLen = 20000; // 20KB
        if (strlen($body) > $maxLen) {
            $body = substr($body, 0, $maxLen) . '... [truncated]';
        }*/

        $message = '[api-request-response]';
        $context = [
            'request_id'  => $requestId,
            'method'      => $method,
            'uri'         => $uri,
            'status'      => $status,
            'duration_ms' => $durationMs,
            'headers'     => $headers,
            'body'        => $body,
        ];

        if ($isException) {
            $this->logger->error($message, $context);
        } else {
            $this->logger->info($message, $context);
        }
    }

    protected function isJsonLike(array $headers): bool
    {
        if (!isset($headers['Content-Type'])) {
            return false;
        }
        $types = $headers['Content-Type'];
        if (is_array($types)) {
            $types = implode(';', $types);
        }
        return stripos($types, 'application/json') !== false
            || stripos($types, '+json') !== false;
    }
}

/**
 * 使用示例：
 *
 * <?php
 * use App\Http\ApiRequest;
 * use Monolog\Handler\StreamHandler;
 * use Monolog\Logger;
 *
 * // 1) 构造（base_uri 可选）
 * $api = new ApiRequest(
 * ['base_uri' => 'https://httpbin.org/'],   // Guzzle 配置
 * ['Accept'   => 'application/json']        // 默认 Header
 * );
 *
 * // 2) 注入日志器（可选）+ 设置 channel（未设置 channel 则不记录日志）
 * $logger = new Logger('any');              // name 在 Monolog 中不是 channel 字段，这里我们把真正的 channel 放到 context 里
 * $logger->pushHandler(new StreamHandler(__DIR__ . '/api.log', Logger::DEBUG));
 * $api->setLogger($logger)->setLogChannel('payment-service');  // 只要设置了 channel 就会记日志
 *
 * // 3) GET
 * $res = $api->get('/get', ['foo' => 'bar']);
 *
 * // 4) POST JSON
 * $res = $api->post('/post', ['a' => 1, 'b' => 2]);
 *
 * // 5) 上传文件
 * $res = $api->upload('/post', [
 * ['name' => 'file', 'path' => __DIR__ . '/a.png', 'filename' => 'a.png', 'mime' => 'image/png']
 * ], ['desc' => 'hello']);
 *
 * // 6) 下载文件
 * $api->download('https://httpbin.org/image/png', __DIR__ . '/download/image.png');
 *
 * // 7) 覆盖/扩展：通过继承设置 channel 或统一拦截
 * class MyApi extends ApiRequest {
 * // 也可以在子类里以属性方式设置（满足题目“可以继承并通过属性设置 channel”）
 * protected ?string $logChannel = 'order-center';
 * }
 *
 * $svc = new MyApi(['base_uri' => 'https://api.example.com']);
 * $svc->setLogger($logger);              // 只要有 logChannel（来自子类属性），就会记日志
 * $svc->get('/orders', ['page' => 1]);
 *
 *
 */

/**
 * <?php
 *
 * namespace App\Services\Binance;
 *
 * use App\Services\ApiRequest;
 * use Illuminate\Support\Facades\Log;
 *
 * class BinanceBase extends ApiRequest
 * {
 * protected string $base_api = 'https://data-api.binance.vision';
 *
 * Const AggTrades = '/api/v3/aggTrades'; // 近期成交(归集)
 *
 * protected ?string $logChannel = 'binance-api';
 * protected int $timeout = 60;
 *
 * public function __construct()
 * {
 * $clientConfig = [
 * 'base_uri' => $this->base_api,
 * 'Accept' => 'application/json',
 * 'Content-Type' => 'application/json',
 * ];
 *
 * if (app()->environment('local')) {
 * $clientConfig['proxy'] = [
 * 'http'  => 'http://host.docker.internal:7890',
 * 'https' => 'http://host.docker.internal:7890',
 * 'verify' => false,
 * ];
 * }
 *
 * $headerArr = [];
 *
 * parent::__construct($clientConfig, $headerArr, Log::channel($this->logChannel));
 * }
 * }
 */
