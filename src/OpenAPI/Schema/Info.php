<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Info Object
 *
 * Provides metadata about the API.
 * @see https://spec.openapis.org/oas/v3.1.0#info-object
 */
final class Info implements JsonSerializable
{
    /**
     * @param string $title The title of the API
     * @param string $version The version of the OpenAPI document
     * @param string|null $description A description of the API
     * @param string|null $termsOfService A URL to the Terms of Service
     * @param Contact|null $contact Contact information
     * @param License|null $license License information
     */
    public function __construct(
        private string $title,
        private string $version,
        private ?string $description = null,
        private ?string $termsOfService = null,
        private ?Contact $contact = null,
        private ?License $license = null
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'title' => $this->title,
            'version' => $this->version,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->termsOfService !== null) {
            $data['termsOfService'] = $this->termsOfService;
        }

        if ($this->contact !== null) {
            $data['contact'] = $this->contact;
        }

        if ($this->license !== null) {
            $data['license'] = $this->license;
        }

        return $data;
    }
}

/**
 * Contact Information
 */
final class Contact implements JsonSerializable
{
    public function __construct(
        private ?string $name = null,
        private ?string $url = null,
        private ?string $email = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        return $data;
    }
}

/**
 * License Information
 */
final class License implements JsonSerializable
{
    public function __construct(
        private string $name,
        private ?string $url = null
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = ['name' => $this->name];

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        return $data;
    }
}
