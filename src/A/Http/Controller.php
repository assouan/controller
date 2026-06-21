<?php

declare(strict_types=1);

namespace A\Http;

use A\Http\Exception\MethodNotAllowedException;

class Controller
{
    public function handle(Request $request) : Response
    {
        $method = strtolower($request->method);

        if (!method_exists($this, $method))
        {
            $method = method_exists($this, 'any') ? 'any' : '';
        }

        if ($method === '')
        {
            throw new MethodNotAllowedException();
        }

        $args = [];
        $reflection = new \ReflectionMethod($this, $method);

        foreach ($reflection->getParameters() as $parameter)
        {
            $args[] = $this->resolve($request, $parameter);
        }

        $data = $this->$method(...$args);
        $template = $this->template($reflection);

        if ($template !== null && !$data instanceof Response)
        {
            return $template->resolve($data, $reflection, $this);
        }

        return $this->render($data);
    }

    public function render(mixed $data) : Response
    {
        if ($data instanceof Response)
        {
            return $data;
        }

        if ($data === null)
        {
            return new Response();
        }

        if (is_array($data) || is_object($data))
        {
            return new Response(
                headers: ['Content-Type' => 'application/json; charset=utf-8'],
                body: json_encode($data, flags: \JSON_THROW_ON_ERROR),
            );
        }

        return new Response(
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
            body: (string)$data,
        );
    }

    protected function resolve(Request $request, \ReflectionParameter $parameter) : mixed
    {
        $type = $parameter->getType();

        if ($type instanceof \ReflectionNamedType && $type->getName() === Request::class)
        {
            return $request;
        }

        $name = $parameter->getName();
        $input = $this->input($request);
        $value = $request->attribute($name, $input[$name] ?? null);

        if ($value === null)
        {
            if ($parameter->isDefaultValueAvailable())
            {
                return $parameter->getDefaultValue();
            }

            if ($type instanceof \ReflectionNamedType && $type->allowsNull())
            {
                return null;
            }
        }

        return match ($type instanceof \ReflectionNamedType ? $type->getName() : 'mixed') {
            'int' => (int)$value,
            'float' => (float)$value,
            'bool' => (bool)$value,
            'string' => (string)$value,
            default => $value,
        };
    }

    protected function input(Request $request) : array
    {
        $input = $request->query;
        $type = strtolower((string)$request->headers->value('content-type', ''));

        if ($request->body !== '' && str_starts_with($type, 'application/x-www-form-urlencoded'))
        {
            parse_str($request->body, $body);
            $input = $body + $input;
        }

        if ($request->body !== '' && str_starts_with($type, 'application/json'))
        {
            $body = json_decode($request->body, true);

            if (is_array($body))
            {
                $input = $body + $input;
            }
        }

        return $input;
    }

    protected function template(\ReflectionMethod $method) : ?Template
    {
        $attributes = $method->getAttributes(Template::class);

        if ($attributes !== [])
        {
            return $attributes[0]->newInstance();
        }

        $class = $method->getDeclaringClass();

        do
        {
            $attributes = $class->getAttributes(Template::class);

            if ($attributes !== [])
            {
                return $attributes[0]->newInstance();
            }
        } while ($class = $class->getParentClass());

        return null;
    }
}
