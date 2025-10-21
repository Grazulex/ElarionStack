<?php

declare(strict_types=1);

namespace Elarion\OpenAPI\Schema;

use JsonSerializable;

/**
 * OpenAPI Path Item Object
 *
 * Describes the operations available on a single path.
 * @see https://spec.openapis.org/oas/v3.1.0#path-item-object
 */
final class PathItem implements JsonSerializable
{
    /**
     * @param string|null $summary Short summary
     * @param string|null $description Long description
     * @param Operation|null $get GET operation
     * @param Operation|null $post POST operation
     * @param Operation|null $put PUT operation
     * @param Operation|null $patch PATCH operation
     * @param Operation|null $delete DELETE operation
     * @param Operation|null $options OPTIONS operation
     * @param Operation|null $head HEAD operation
     */
    public function __construct(
        private ?string $summary = null,
        private ?string $description = null,
        private ?Operation $get = null,
        private ?Operation $post = null,
        private ?Operation $put = null,
        private ?Operation $patch = null,
        private ?Operation $delete = null,
        private ?Operation $options = null,
        private ?Operation $head = null
    ) {
    }

    /**
     * Set GET operation
     */
    public function setGet(Operation $operation): self
    {
        $this->get = $operation;

        return $this;
    }

    /**
     * Set POST operation
     */
    public function setPost(Operation $operation): self
    {
        $this->post = $operation;

        return $this;
    }

    /**
     * Set PUT operation
     */
    public function setPut(Operation $operation): self
    {
        $this->put = $operation;

        return $this;
    }

    /**
     * Set PATCH operation
     */
    public function setPatch(Operation $operation): self
    {
        $this->patch = $operation;

        return $this;
    }

    /**
     * Set DELETE operation
     */
    public function setDelete(Operation $operation): self
    {
        $this->delete = $operation;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        $data = [];

        if ($this->summary !== null) {
            $data['summary'] = $this->summary;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->get !== null) {
            $data['get'] = $this->get;
        }

        if ($this->post !== null) {
            $data['post'] = $this->post;
        }

        if ($this->put !== null) {
            $data['put'] = $this->put;
        }

        if ($this->patch !== null) {
            $data['patch'] = $this->patch;
        }

        if ($this->delete !== null) {
            $data['delete'] = $this->delete;
        }

        if ($this->options !== null) {
            $data['options'] = $this->options;
        }

        if ($this->head !== null) {
            $data['head'] = $this->head;
        }

        return $data;
    }
}
