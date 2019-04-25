<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Smart\CoreBundle\Doctrine\RepositoryTrait;

class FolderRepository extends EntityRepository
{
    use RepositoryTrait\FindDeleted;
    use RepositoryTrait\FindByQuery;

    /**
     * @param Folder|null $parent_folder
     *
     * @return Folder[]
     */
    public function findByParent(?Folder $parent_folder = null): array
    {
        $criteria = [
            'parent_folder' => $parent_folder,
            'deleted_at' => null,
        ];

        return $this->getFindByQuery($criteria, ['position' => 'ASC'])->getResult();
    }
}
