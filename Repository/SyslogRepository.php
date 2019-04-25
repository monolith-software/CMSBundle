<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Smart\CoreBundle\Doctrine\RepositoryTrait;

class SyslogRepository extends EntityRepository
{
    use RepositoryTrait\FindByQuery;

}
