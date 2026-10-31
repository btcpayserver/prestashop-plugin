<?php

namespace BTCPay\Tests\Controller;

use BTCPay\Form\Data\Server;
use BTCPay\Form\ServerFormDataProvider;
use BTCPayServer\Http\ClientInterface;
use BTCPayServer\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Request;

// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses, Generic.Files.OneClassPerFile.MultipleFound -- test fixtures for the controller flow tests

/**
 * Always submitted and valid, returning the configured server data.
 */
class FlowForm
{
	public function __construct(private Server $data)
	{
	}

	public function handleRequest(Request $request): void
	{
	}

	public function isSubmitted(): bool
	{
		return true;
	}

	public function isValid(): bool
	{
		return true;
	}

	public function getData(): Server
	{
		return $this->data;
	}
}

/**
 * Form handler that mirrors the real behaviour: the data provider writes the
 * submitted values to the configuration before any error is reported.
 */
class FlowFormHandler implements \PrestaShop\PrestaShop\Core\Form\FormHandlerInterface
{
	/**
	 * @var list<string> errors returned by save()
	 */
	public array $saveErrors = [];

	public ?\Throwable $saveException = null;

	public function __construct(private FlowForm $form)
	{
	}

	public function getForm(): FlowForm
	{
		return $this->form;
	}

	public function save(array $data): array
	{
		(new ServerFormDataProvider())->setData($data);

		if (null !== $this->saveException) {
			throw $this->saveException;
		}

		return $this->saveErrors;
	}
}

/**
 * Validator accepting everything, so the controller flow proceeds.
 */
class FlowValidator implements \Symfony\Component\Validator\Validator\ValidatorInterface
{
	/**
	 * @param mixed $value
	 * @param mixed $constraints
	 * @param mixed $groups
	 *
	 * @return list<mixed>
	 */
	public function validate($value, $constraints = null, $groups = null): array
	{
		return [];
	}
}

/**
 * Greenfield HTTP fake: routes are matched in order by method and URL substring,
 * unmatched requests answer 404 (e.g. a webhook that does not exist remotely).
 */
class FlowGreenfieldHttp implements ClientInterface
{
	/**
	 * @var list<array{method: string, url: string, body: string}>
	 */
	public array $requests = [];

	/**
	 * @var list<array{method: string, contains: string, status: int, body: string|callable}>
	 */
	public array $routes = [];

	public function on(string $method, string $contains, int $status, $body): self
	{
		$this->routes[] = ['method' => $method, 'contains' => $contains, 'status' => $status, 'body' => $body];

		return $this;
	}

	public function request(string $method, string $url, array $headers = [], string $body = ''): HttpResponse
	{
		$this->requests[] = ['method' => $method, 'url' => $url, 'body' => $body];

		foreach ($this->routes as $route) {
			if ($route['method'] === $method && \str_contains($url, $route['contains'])) {
				$payload = $route['body'];
				$content  = \is_callable($payload) ? $payload($body) : $payload;

				return new HttpResponse($route['status'], $content, []);
			}
		}

		return new HttpResponse(404, '{}', []);
	}
}
