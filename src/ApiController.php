<?php

declare(strict_types=1);

namespace Jamosaur\Foundation;

use Illuminate\Contracts\Pagination\CursorPaginator;
// use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Jamosaur\Foundation\Contracts\TransformerContract;
use Jamosaur\Foundation\Exceptions\TransformerMissingException;
use Jamosaur\Foundation\Serializers\DefaultSerializer;
use League\Fractal\Serializer\Serializer;

use function count;

class ApiController extends Controller
{
    protected array $body = [];

    protected ?array $pagination = null;

    private ?int $statusCode = null;

    protected ?Serializer $serializer = null;

    protected ?TransformerContract $transformer = null;

    protected string $transformerNamespace = '\\App\\Transformers\\';

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
        if (is_object($data) && method_exists($data, 'total')) {
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
        return $this->statusCode ?? \Symfony\Component\HttpFoundation\Response::HTTP_OK;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
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

    /**
     * @throws TransformerMissingException
     */
    public function getDefaultTransformer(): TransformerContract
    {
        $controller = Request::instance()->attributes->get('_controller');

        $class = $this->transformerNamespace.ucfirst($controller).'Transformer';

        if (! class_exists($class)) {
            throw new TransformerMissingException("Transformer class {$class} does not exist");
        }

        $transformer = new $class;

        if (! ($transformer instanceof TransformerContract)) {
            throw new TransformerMissingException("Transformer class {$class} does not implement TransformerContract");
        }

        return $transformer;
    }

    public function setTransformer(TransformerContract $transformer): self
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * @throws TransformerMissingException
     */
    public function getTransformer(): TransformerContract
    {
        return $this->transformer ??= $this->getDefaultTransformer();
    }

    public function setTransformerNamespace(string $namespace): self
    {
        $this->transformerNamespace = $namespace;

        return $this;
    }

    private function appendPagination(array $data): self
    {
        $this->pagination = $data;

        return $this;
    }

    private function getDefaultSerializer(): DefaultSerializer
    {
        return new DefaultSerializer;
    }
}
