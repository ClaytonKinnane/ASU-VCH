<?php

declare(strict_types=1);

require_once __DIR__ . '/OrganizationalStructureCreateUpdateTrait.php';
require_once __DIR__ . '/OrganizationalStructureNodeTrait.php';
require_once __DIR__ . '/OrganizationalStructureDocumentTrait.php';
require_once __DIR__ . '/OrganizationalStructureLifecycleTrait.php';
require_once __DIR__ . '/OrganizationalStructureSupportTrait.php';

final class OrganizationalStructureService
{
    use OrganizationalStructureCreateUpdateTrait;
    use OrganizationalStructureNodeTrait;
    use OrganizationalStructureDocumentTrait;
    use OrganizationalStructureLifecycleTrait;
    use OrganizationalStructureSupportTrait;

    public function __construct(private readonly PDO $pdo)
    {
    }
}
