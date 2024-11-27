<?php
declare(strict_types=1);


namespace App\Form;

use App\Entity\PageContent;
use App\Entity\User;


class PageContentDto {
    public ?string $identifier;
    public string $content;

    public function toEntity(User $creator): PageContent {
        return new PageContent($creator, $this->identifier, $this->content);
    }

    public static function fromEntity(PageContent $pageContent): self {
        $dto = new self();
        $dto->identifier = $pageContent->getIdentifier();
        $dto->content = $pageContent->getContent();

        return $dto;
    }
}