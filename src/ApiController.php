<?php

declare(strict_types=1);

namespace Jamosaur\Foundation;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use League\Fractal\Serializer\Serializer;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Jamosaur\Foundation\Serializers\DefaultSerializer;
use Jamosaur\Foundation\Contracts\TransformerContract;

use function count;

class ApiController extends Controller
{
    protected array $body = [];
    protected ?array $pagination = null;
    private ?int $statusCode;
    protected ?Serializer $serializer = null;
    protected ?TransformerContract $transformer;
    private string $statusDescription;
    private array $statusDescriptionDefaults = [
        Response::HTTP_OK => 'OK', // 200
        Response::HTTP_PAYMENT_REQUIRED => 'Payment is required to complete this action', // 402
        Response::HTTP_FORBIDDEN => 'Permission Denied', // 403
        Response::HTTP_NOT_FOUND => 'The requested item was not found', // 404
        Response::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed', // 405
        Response::HTTP_UNPROCESSABLE_ENTITY => 'Invalid Request', // 422
        Response::HTTP_INTERNAL_SERVER_ERROR => 'There was a problem with your request', // 500
    ];

    public function __construct(public Request $request)
    {
    }

    public function respond(): JsonResponse
    {
        $responseBody = [
            'data' => $this->body,
        ];

        if ($this->pagination) {
            $responseBody['pagination'] = $this->pagination;
        }

        return new JsonResponse($responseBody, $this->getStatusCode());
    }

    public function appendBody(string $key, mixed $data): self
    {
        $this->body[$key] = $data;

        return $this;
    }

    public function withPagination(mixed $data): self
    {
        if (method_exists($data, 'total')) {
            return $this->appendPagination([
                'total' => (int) $data->total(),
                'per_page' => (int) $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
            ]);
        }

        return $this->appendPagination([
            'total' => count($data),
            'per_page' => 0,
            'current_page' => 1,
            'last_page' => 1,
        ]);
    }

    public function withCursorPagination(CursorPaginator $paginator): self
    {
        return $this->appendPagination([
            'has_more' => $paginator->hasMorePages(),
            'next_cursor' => $paginator->nextCursor()?->encode(),
            'previous_cursor' => $paginator->previousCursor()?->encode(),
        ]);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode ?? Response::HTTP_OK;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getStatusDescription(): string
    {
        $statusCode = $this->getStatusCode();

        return $this->statusDescription ??
            $this->statusDescriptionDefaults[$statusCode] ??
            $this->statusDescriptionDefaults[Response::HTTP_INTERNAL_SERVER_ERROR];
    }

    public function appendError(string $message, int $responseCode = Response::HTTP_INTERNAL_SERVER_ERROR): self
    {
        return $this->appendBody('error', $message)
            ->setStatusCode($responseCode);
    }

    public function transformItem(string $key, mixed $data, mixed $includes = null): self
    {
        $item = fractal()
            ->item($data, $this->getTransformer())
            ->serializeWith($this->getSerializer())
            ->parseIncludes(Arr::wrap($includes))
            ->toArray();

        return $this->appendBody($key, $item);
    }

    public function transformCollection(string $key, mixed $data, $includes = null): self
    {
        $collection = fractal()
            ->collection($data, $this->getTransformer())
            ->serializeWith($this->getSerializer())
            ->parseIncludes(Arr::wrap($includes))
            ->toArray();

        return $this->appendBody($key, $collection);
    }

    public function setSerializer(Serializer $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    public function getSerializer(): Serializer
    {
        return $this->serializer ?? $this->getDefaultSerializer();
    }

    public function getDefaultTransformer(): TransformerContract
    {
        $namespace = '\\App\\Transformers\\';

        $class = $namespace . ucfirst($this->request->get('_controller')) . 'Transformer';

        return new $class();
    }

    public function setTransformer(TransformerContract $transformer): self
    {
        $this->transformer = $transformer;

        return $this;
    }

    public function getTransformer(): TransformerContract
    {
        return $this->transformer ?? $this->getDefaultTransformer();
    }

    private function appendPagination(array $data): self
    {
        $this->pagination = $data;

        return $this;
    }

    private function getDefaultSerializer(): DefaultSerializer
    {
        return new DefaultSerializer();
    }
}
